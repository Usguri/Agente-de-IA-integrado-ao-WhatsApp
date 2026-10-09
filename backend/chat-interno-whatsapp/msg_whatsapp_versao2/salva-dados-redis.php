<?php

define('REDIS', '/predis/autoload.php');
require_once REDIS;



// ------------------------------ CONEXÃO COM O BANCO REDIS ------------------------------ \\

function ConexaoRedis()
{
    return new \Predis\Client([
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ]);
}

function selectbuscadadoscinco($sql)
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->connect();
    $data = $conn->query($sql);
    $conn->desconnect();
    return $data;
}

// ---------------------------------------------------------------------------------------- \\







// --------------------------------- RECEPTOR DE DADOS SENDO RECEBIDOS -------------------------------------------- \\

function RecebeStatusMsg($dados) // QUANDO A IA OU O COLABORADOR ENVIA MENSAGEM VAI CAIR AQUI, PARA SALVAR OS STATUS
{
    $redis = ConexaoRedis();

    $chaveRegCli = $dados[3];
    $existChaveRegCli = $redis->hgetall($chaveRegCli);


    if ($existChaveRegCli['numero'] == $dados[0] && $existChaveRegCli['status'] == 'sent' && $dados[1] == 'delivered') {

        $existChaveRegCli['status'] = $dados[1];
        $redis->hmset($chaveRegCli, $existChaveRegCli);
        $redis->quit();

        SalvaStatusDeEnvioMensagemCliente($existChaveRegCli, $dados);
    } else if ($existChaveRegCli['numero'] == $dados[0] && $dados[1] == 'sent') {

        $existChaveRegCli['status'] = $dados[1];
        $redis->hmset($chaveRegCli, $existChaveRegCli);
        $redis->quit();

        SalvaStatusDeEnvioMensagemCliente($existChaveRegCli, $dados);
    }
}

function RecebeMsgCliente($dados, $valida) // QUANDO CLIENTE ENIVA MENSAGEM, VAI CAIR AQUI
{

    require 'exec.php';

    $redis = ConexaoRedis();
    $chaveChtClb = $dados[7];
    $existClienteChatClb = $redis->hgetall($chaveChtClb);

    $necessitaAtualizacao = ($existClienteChatClb['idmessage'] != $dados[1] && $existClienteChatClb['datahora'] != $dados[2]);

    if ($necessitaAtualizacao) {
        if ($valida == 1) {
            SalvaDadosCincoCli($dados);
        } else if ($valida == 2) {
            AtlBancoPos24Horas($existClienteChatClb['id'], $dados);
        } else {
            UpdateNovasMensagens($existClienteChatClb['id'], $dados);
        }



        $sql = str_replace([':NUMEROAGENTE', ':DATA'], [$dados[8], date("j/n/Y")], SQL_BUSCA_TREINAMENTO); // BUSCA TREINAMENTO
        $data = selectbuscadadoscinco($sql);
        $validaIsTrue = false;

        if (empty($data)) {
            $validaIsTrue = true;
            verificavalidadetreinamento(1, $existClienteChatClb['id']);
        } else {
            verificavalidadetreinamento(0, $existClienteChatClb['id']);
        }



        SalvaArqMsgRecCli($dados, $dados[4]);
        salvaNumClienteArq($dados[7]);
        executar();

        if ($existClienteChatClb['controle_ia'] || $valida == 1) {
            busca_treinamento_ia($dados, $data, $validaIsTrue);
        } else {
            salvarmensagemcliente($dados);
        }
    }

    $redis->quit();
}

// ---------------------------------------------------------------------------------------------------------------- \\



// -----------------------FUNÇÕES DE LOGICAS DE EXECUÇÃO------------------------------------ \\

function salvaNumClienteArq($from) // CRIA UM ARQUIVO JSON COM OS NUMEROS DO CLIENTE QUANDO CHEGA AS MENSAGENS
{

    $file_path = '../servidor/idclientes_temp.json';

    if (file_exists($file_path)) {
        $existing_data = file_get_contents($file_path);
        $data_array = json_decode($existing_data, true);

        if ($data_array === null) {
            $data_array = [];
        }
    } else {
        $data_array = [];
    }

    $data_array[] = $from;
    $json_data = json_encode($data_array);
    file_put_contents($file_path, $json_data);
}

function busca_treinamento_ia($dados, $data, $validaIsTrue) // EXECUTA O TREINAMENTO DA IA PARA ELA RETORNAR O QUESTIONAMENTO
{

    salvarmensagemcliente($dados);

    if (!$validaIsTrue) {

        // DADOS A SEREM ENVIADOS VIA PROP PARA O PYTHON \\

        $caminho = __DIR__ . '/arquivo-exec-IA';

        putenv("CONHECIMENTO=" . $data[0]['DADOSUPLOADARQ']);
        putenv("IDENTIDADE=" . $data[0]['IDENTIDADE_DA_IA']);
        putenv("HISTCONVERSA=" . $dados[7] . '.json');
        putenv("MESSAGE=" . $dados[3]);
        putenv("CAMINHO=" . $caminho);
        $command = escapeshellcmd("./arquivo-exec-IA/executar-whatsapp");
        $output = shell_exec($command . " 2>&1");
        inteligenciaRetornaCliente($output, $dados);
    }
}

function isEmoji($char) // NO PROCESSAMENTO VERIFICA SE DETERMINADA PALAVRA É UM EMOJI
{
    return preg_match('/[\x{1F600}-\x{1F64F}]/u', $char)
        || preg_match('/[\x{1F300}-\x{1F5FF}]/u', $char)
        || preg_match('/[\x{1F680}-\x{1F6FF}]/u', $char)
        || preg_match('/[\x{1F700}-\x{1F77F}]/u', $char)
        || preg_match('/[\x{1F780}-\x{1F7FF}]/u', $char)
        || preg_match('/[\x{1F800}-\x{1F8FF}]/u', $char)
        || preg_match('/[\x{2600}-\x{26FF}]/u', $char)
        || preg_match('/[\x{2700}-\x{27BF}]/u', $char);
}

function inteligenciaRetornaCliente($output, $dados) // PROCESSA A MENSAGEM VINDA DA IA
{
    require_once 'envia-mensagem-whatsapp.php';

    $converte = json_decode($output, true);
    // $converte['candidates_token_count'];
    // $converte['total_token_count'];
    // $converte['text'];

    $numeroCliente = ConverteNumero($dados[0]);

    $eliminarespacos = preg_replace('/\s+/', ' ', trim($converte['text']));
    $palavras = explode(' ', $eliminarespacos);
    $encontrou = false;
    $pos = null;


    foreach ($palavras as $key => $palavra) {

        $valida = isEmoji($palavra);

        if (!$valida) {
            if (mb_strlen($palavra, 'UTF-8') > 5) { // verifica se contem mais de 5 caracteres
                if (strpos($palavra, '@$#!&') !== false) { // contem os 5 prefixos
                    $encontrou = true;
                    $pos = $key;
                    break;
                }
            }
        }
    }

    $nomearquivo_salvar = null;

    if ($encontrou) {

        $keyimagem = trim($palavras[$pos]);

        unset($palavras[$pos]);
        $mensagemajustada = implode(" ", $palavras);

        $sql = str_replace([':KEYARQUIVO'], [$keyimagem], SQL_BUSCA_NOME_ARQUIVO_SALVO);
        $nomearquivo_salvar = selectbuscadadoscinco($sql);

        $sql = str_replace([':KEYARQUIVO'], [$keyimagem], SQL_BUSCA_IMAGEM);
        $data = selectbuscadadoscinco($sql);
    } else {
        $mensagemajustada = $converte['text'];
    }


    SalvaMensagemEnviadaByInteligencia($dados[0], $mensagemajustada, $dados[9], $dados[4]);
    clbconversacomclienteEIntelig($numeroCliente, $mensagemajustada, $dados[9]);

    if ($encontrou) {

        $caminho = __DIR__ . '/' . 'img-enviadas-clb/' . $nomearquivo_salvar[0]['NOMEARQUIVO'];
        file_put_contents($caminho, $data[0]['ARQUIVO']);

        $dados[4] = 2;
        SalvaMensagemEnviadaByInteligencia($dados[0], $nomearquivo_salvar[0]['NOMEARQUIVO'], $dados[9], $dados[4], $caminho);
        $idimagem = uploadarquivosenviawhatsapp($caminho, $dados[9]);
        $data = json_decode($idimagem);

        $retorno = enviaimagem($numeroCliente, $data->id, $dados[9]);
        $retornoimg = json_decode(deletaimgsalvaempasta($nomearquivo_salvar[0]['NOMEARQUIVO']), true);
    }
}

function SalvaArqMsgRecCli($dados) // SALVA MENSAGENS RECEBIDAS PELO CLIENTE AQUI
{
    $redis = ConexaoRedis();
    $path = BuscaCaminhoArquivo($dados[7]);

    $chave = $dados[7];
    $existClienteChatClb = $redis->hgetall($chave);


    if (file_exists($path)) {
        $conteudo_existente = file_get_contents($path);
        $data = json_decode($conteudo_existente, true);
    } else {

        $data = [
            'cliente' => [],
            'colaborador' => []
        ];

        // $contador = $existClienteChatClb['contador'] ?? 1;
        // $existClienteChatClb['idmessage'] = $dados[1];
    }

    $contador = intval($existClienteChatClb['contador']) + 1;
    $existClienteChatClb['contador'] = $contador;
    $existClienteChatClb['idmessage'] = $dados[1];
    $existClienteChatClb['mensagem'] = $dados[3];
    $existClienteChatClb['datahora'] = time();
    $existClienteChatClb['nomearq'] = $dados[5];
    $existClienteChatClb['tipomsg'] = $dados[4];


    $novosDados = [
        'contador' => $contador,
        'mensagem' => $dados[3],
        'datahora' => date('d/m/Y H:i:s', $dados[2]),
        'tipomsg' => $dados[4],
        'nomearq' => $dados[5]
    ];

    $data['cliente'][] = $novosDados;
    $json_atualizado = json_encode($data);

    $arquivo = fopen($path, "w");
    fwrite($arquivo, $json_atualizado);
    fclose($arquivo);

    $redis->hmset($chave, $existClienteChatClb);
    $redis->quit();

    // SALVA DOCUMENTOS ENVIADOS PELO CLIENTE

    if ($dados[4] != 0) {

        require_once 'baixa-arquivos-recebidos.php';
        baixarmediawhatsapp($dados, $existClienteChatClb['contador'], $existClienteChatClb['id']);
    }
}

function ConverteNumero($telefone) // AJUSTE NUMERO DO CLIENTE PARA O ENVIO DE MENSAGEM
{
    $ddd = substr($telefone, 0, 4);
    $numero = substr($telefone, 4);
    $numeroCliente = $ddd . '9' . $numero;

    return $numeroCliente;
}

function BuscaCaminhoArquivo($arq) // BUSCA O CAMINHO DO ARQUIVO PARA SALVAR 
{

    $diretorioScript = __DIR__;
    $path = 'historico-mensagens/' . $arq . '.json';
    return $diretorioScript . '/' . $path;
}

function SalvaArquivoAntesDoEnvio($numero, $mensagem, $caminho, $idcontato, $contator)
{
    $nome_arq = $numero . '_' . $mensagem;

    $nextid = getproxidtablearq();
    $sqlinserirarq = str_replace(
        [':ID:', ':TIPO:', ':CONTADOR:', ':DATAHORA:', ':NOMEARQ:', ':IDCONTATO:'],
        [$nextid, 'send', $contator, date('d/m/Y H:i:s', time()), $nome_arq, $idcontato],
        SQL_SALVA_ARQUIVOS_SEND_BY_IA
    );
    $retorno = InserAndUpdatBank($sqlinserirarq);

    $sqlInsert = str_replace([':ID:'], [$nextid], SQL_SALVA_BLOB_SEND_BY_IA);
    $retorno = adicionararquivoblob($sqlInsert, $caminho);
}

function SalvaMensagemEnviadaByInteligencia($numero, $mensagem, $typedep, $tipomsg, $caminho = null)
{
    $redis = ConexaoRedis();
    $chaveRegCli = $typedep . strval($numero);
    $existChaveRegCli = $redis->hgetall($chaveRegCli);

    $existChaveRegCli['id'];
    $contador = intval($existChaveRegCli['contador']) + 1;
    $existChaveRegCli['contador'] = strval($contador);


    if ($caminho != null) {
        SalvaArquivoAntesDoEnvio($numero, $mensagem, $caminho, $existChaveRegCli['id'], $existChaveRegCli['contador']);
    }

    $path = BuscaCaminhoArquivo($chaveRegCli);

    if (file_exists($path)) {
        $conteudo_existente = file_get_contents($path);
        $data = json_decode($conteudo_existente, true);
    } else {
        $data = [
            'cliente' => [],
            'colaborador' => []
        ];
    }

    $novosDados = [
        'contador' => $contador,
        'mensagem' => $mensagem,
        'datahora' => date('d/m/Y H:i:s', time()),
        'status' => '',
        'usuario' => 'IA',
        'tipomsg' => $tipomsg
    ];

    $data['colaborador'][] = $novosDados;
    $json_atualizado = json_encode($data);

    $arquivo = fopen($path, "w");


    if ($arquivo) {
        fwrite($arquivo, $json_atualizado);
        fclose($arquivo);
        $redis->hmset($chaveRegCli, $existChaveRegCli);
    } else {
        $retorno['erro'] = '0012';
        $retorno['mensagem'] = 'Erro interno ao abrir o arquivo';
    }
}

function getproxidtablearq()
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->connect();
    $id = (int) $conn->query('SELECT W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA_SEQ.NEXTVAL IDCONTATO FROM DUAL')[0]['IDCONTATO'];
    $conn->desconnect();

    return $id;
}

function adicionararquivoblob($sql, $rota)
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->prepareBlob(':ARQUIVO');
    $conn->non_query($sql, [':ARQUIVO' => file_get_contents($rota)], false);

    if (empty($conn->get_error(true))) {
        $conn->commit();
        $response['dados'] = ['erro' => 0];
    } else {
        $conn->rollback();
        $response['dados'] = ['erro' => 1];
    }

    $conn->desconnect();
}

function salvarmensagemcliente($dados) // CASO O COLABORADOR ASSUMIU A CONVERSA SALVA AS MENSAGENS NO ARQUIVO DO PYTHON
{
    $path =  __DIR__ . '/' . 'conversas-cliente' . '/' . $dados[7] . '.json';

    if (file_exists($path)) {
        $jsonString = file_get_contents($path);
        $data = json_decode($jsonString, true);
    } else {
        $data = [];
    }

    // Nova mensagem a ser adicionada
    $novaInformacao = [
        "role" => "model",
        "parts" => [$dados[3]]
    ];

    $data[] = $novaInformacao;

    $json_atualizado = json_encode($data);
    $arquivo = fopen($path, "w");

    if ($arquivo) {
        fwrite($arquivo, $json_atualizado);
        fclose($arquivo);
    } else {
        echo "Erro ao abrir o arquivo para escrita.";
    }
}

function verificavalidadetreinamento($value, $idcontato) // SALVA ATIVAÇÃO DO TREINAMENTO
{
    $sqlinserirarq = str_replace([':TREINAMENTO', ':IDCONTATO'], [$value, $idcontato], SQL_SALVA_TREINAMENTE_ATIVO);
    $retorno = InserAndUpdatBank($sqlinserirarq);
}

// ---------------------------------------------------------------------------------------- \\






// --------------------------------- FUNÇÕES DO REDIS ------------------------------------ \\

function clienteexistnoredisrecebemsg($clidepredis) // QUANDO CHEGAR QUALQUER STATUS OU MSG ACESSA ESTAS FUNÇÕES
{
    $redis = ConexaoRedis();
    $chaveRegCli = $clidepredis;

    if ($redis->exists($chaveRegCli)) {

        $existClienteChatClb = $redis->hgetall($chaveRegCli);
        $horaAtual = time();
        $ulthora = $existClienteChatClb['datahora'];

        $diferencaSegundos = $horaAtual - $ulthora;
        $segundosEmUmDia = 86400;

        if ($diferencaSegundos > $segundosEmUmDia) {
            return '2';  // Mais de 1 dia
        } else {
            return '3';  // Menos de 1 dia
        }
    } else {
        return '1';
    }
}

function CrioChaveValueRedisCliente($dadosCliente, $regbanccinco) // CRIA UM JSON COM OS COMPOS DO CLIENTE NO REDIS
{
    $redis = ConexaoRedis();
    $chave = $dadosCliente[7];

    $infoMensagem = array(
        'id' => $regbanccinco,
        'numero' => $dadosCliente[0],
        'datahora' => time(),
        'idmessage' => $dadosCliente[1],
        'mensagem' => '',
        'status' => '',
        'contador' => 1,
        'ativo' => false,
        'nomearq' => null,
        'tipomsg' => null,
        'controle_ia' => true
    );

    $redis->hmset($chave, $infoMensagem);
    $redis->quit();
}

function PrintaDadosClienteRedis($idcliente) // PRINTAR DADOS SALVOS NO REDIS UTILIZADO NA PASTA SERVIDOR
{
    $redis = ConexaoRedis();
    $chave = $idcliente;
    $result = $redis->hgetall($chave);
    $redis->quit();
    return $result;
}

// ---------------------------------------------------------------------------------------- \\






// ------------------------------------------------ SALVA DADOS DAS CONVERSAS E STATUS NO BANCO CINCO ------------------------------------------------ \\

// etapa de delete, inserte e update

// ---------------------------------------------------------------------------------------------------------------------------------------------------- \\







// -------------------------------------------------------------------- SELECTS, INSERTS E UPDATES EM BANCO DE DADOS --------------------------------------------------------- \\

function GetProxId() // BUSCA O PROXIMO ID PARA SALVAR UM REGISTRO DO CLIENTE
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->connect();
    $id = (int) $conn->query('SELECT W_HISTORICO_MSG_WHATSAPP_PRACA_SEQ.NEXTVAL IDCONTATO FROM DUAL')[0]['IDCONTATO'];
    $conn->desconnect();

    return $id;
}

function SalvaDadosCincoCli($dadosMsgCliente) // FUNÇÃO UTILIZADA QUANDO NÃO EXISTIR REGISTRO DO CLIENTE NO BANCO
{

    $id = GetProxId(); // pego o proximo ID

    $sql = str_replace(
        [':IDCONTATO:', ':CONTATO:', ':DATAHORAULTMSG:', ':NOMEDEP:'],
        [$id, ConverteNumero($dadosMsgCliente[0]), date('d/m/Y H:i:s', $dadosMsgCliente[2]), $dadosMsgCliente[6]],
        SQL_INSERT_W_HISTORICO_MSG_WHATSAPP_PRACA
    );

    InserAndUpdatBank($sql); // SALVA NO BANCO DE DADOS 
    CrioChaveValueRedisCliente($dadosMsgCliente, $id); // CRIA O ID REDIS PERMANENTE 
}

function UpdateNovasMensagens($idcliente, $dadosMsgCliente) // ATUALIZA NOVAS MENSAGENS ENVIADAS PELO CLIENTE
{

    $status = buscastatusconversa($idcliente);

    $sql = str_replace(
        [':DATAHORAULTMSG:', ':IDCONTATO:', ':STATUSCONVERSA:'],
        [date('d/m/Y H:i:s', $dadosMsgCliente[2]), $idcliente, $status],
        SQL_UPDATE_W_HISTORICO_MSG_WHATSAPP_PRACA
    );

    InserAndUpdatBank($sql);
}

function buscastatusconversa($idcliente) // BUSCA STATUS DA CONVERSA DO CLIENTE
{

    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->connect();
    $id = (int) $conn->query("select STATUSCONV from W_HISTORICO_MSG_WHATSAPP_PRACA where IDCONTATO = $idcliente")[0]['STATUSCONV'];
    $conn->desconnect();
    return $id;
}

function AtlBancoPos24Horas($idcliente, $dadosMsgCliente) // APOS 24 HORAS ATUALIZA DADOS DO BANCO 
{
    $sql = str_replace(
        [':DATAHORAULTMSG:', ':IDCONTATO:'],
        [date('d/m/Y H:i:s', $dadosMsgCliente[2]), $idcliente],
        SQL_UPDATE_W_HISTORICO_MSG_WHATSAPP_PRACA_POS_24
    );

    InserAndUpdatBank($sql);
}

function InserAndUpdatBank($sql) // FUNÇÃO PARA SALVAR INSERIR OU ATUALIZAR DADOS NO BANCO
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->non_query($sql, [], false);

    if (empty($conn->get_error())) {
        $conn->commit();
    } else {
        $conn->rollback();
    }
    $conn->desconnect();
}

function SalvaStatusDeEnvioMensagemCliente($dadosRedis, $dadostempredis) // SALVA TODO OS DADOS DE STATUS DA MENSAGEM ENVIADA PARA O CLIENTE
{

    $path = BuscaCaminhoArquivo($dadostempredis[3]);

    if (file_exists($path)) {
        $conteudo_existente = file_get_contents($path);
        $data = json_decode($conteudo_existente, true);

        $lastIndex = count($data['colaborador']) - 1;

        if ($dadostempredis[1] == 'delivered') {

            for ($i = 0; $i < count($data['colaborador']); $i++) {

                if (isset($data['colaborador'][$i]['status'])) {
                    if ($data['colaborador'][$i]['status'] == '') {
                        $data['colaborador'][$i]['status'] = $dadostempredis[1];
                    }
                }
            }
            $data['colaborador'][$lastIndex]['status'] = $dadostempredis[1];
        } else if ($data['colaborador']) {
            if (count($data['colaborador']) == 1) {
                $data['colaborador'][$lastIndex]['status'] = $dadostempredis[1];
            } else if ($data['colaborador'][$lastIndex]['status'] == '') {
                $data['colaborador'][$lastIndex]['status'] = $dadostempredis[1];
            }
        }


        $updateJson = json_encode($data);
        $arquivo = fopen($path, "w");
        fwrite($arquivo, $updateJson);
        fclose($arquivo);

        $sql = str_replace(
            [':DATAHORAULTMSG:', ':IDCONTATO:'],
            [date('d/m/Y H:i:s', $dadostempredis[2]), $dadosRedis['id']],
            SQL_UPDATE_DATAHORA_ULT_MSG_WHATSAPP_ENVIADA_CLIENTE_TAREFA2
        );


        InserAndUpdatBank($sql);
    }
}

// ---------------------------------------------------------------------------------------------------------------------------------------------------------------------------- \\







function teste($infoMensagem)
{
    $redis = ConexaoRedis();
    $chave = 'teste';
    $redis->set($chave, $infoMensagem);
    $redis->quit();
}
