<?php

// ENVIA MENSAGEM COM O CLIENTE, FUNÇÃO PARA O ENVIO \\

function funcaoenviomsgcliente($numero, $mensagem, $tipomsg, $userID, $typedep)
{

    $dados = SalvarJsonERedis($numero, $mensagem, $userID, $typedep, $tipomsg);

    SalvaHistoricoMensagemInteligencia($numero, $mensagem, $typedep);

    if ($tipomsg == 0) {
        return clbconversacomclienteEIntelig($numero, $mensagem, $typedep);
    } else if ($tipomsg == 2) {
        return enviaimagemwhatsapp($numero, $mensagem, $dados[0], $dados[1], $typedep);
    } else if ($tipomsg == 3) {
        return enviaarquivopdfmwhatsapp($numero, $mensagem, $dados[0], $dados[1], $typedep);
    }
}

function SalvaHistoricoMensagemInteligencia($numero, $mensagem, $typedep)
{
    $ajustenumero = substr($numero, 0, strpos($numero, '9')) . substr($numero, strpos($numero, '9') + 1);
    $path =  __DIR__ . '/' . 'conversas-cliente' . '/' . $typedep . $ajustenumero . '.json';

    if (file_exists($path)) {
        $jsonString = file_get_contents($path);
        $data = json_decode($jsonString, true);
    } else {
        $data = [];
    }

    // Nova mensagem a ser adicionada
    $novaInformacao = [
        "role" => "user",
        "parts" => [$mensagem]
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

function SalvarJsonERedis($numero, $mensagem, $userID, $typedep, $tipomsg = 0)
{
    $ClientExistRedis = substr($numero, 0, strpos($numero, '9')) . substr($numero, strpos($numero, '9') + 1);

    $redis = ConexaoRedis();
    $chaveRegCli = $typedep . strval($ClientExistRedis);
    $existChaveRegCli = $redis->hgetall($chaveRegCli);

    $contador = intval($existChaveRegCli['contador']) + 1;
    $existChaveRegCli['contador'] = strval($contador);

    $path = SaveFileInPath($chaveRegCli);

    if (file_exists($path)) {
        $conteudo_existente = file_get_contents($path);
        $data = json_decode($conteudo_existente, true);
    } else {
        $data = [
            'cliente' => [],
            'colaborador' => []
        ];
    }

    $sql = str_replace([':IDUSER:'], [$userID], "SELECT h.cc, h.filial, h.numerocm FROM z_user_pessoa u INNER JOIN z_hierarquias h ON h.numerocm = u.numerocm WHERE u.zuser = ':IDUSER:'");
    $dadosClb = selectdadosbancocinco($sql);
    $matricula = $dadosClb[0]['NUMEROCM'];


    $novosDados = [
        'contador' => $contador,
        'mensagem' => $mensagem,
        'datahora' => date('d/m/Y H:i:s', time()),
        'status' => '',
        'usuario' => $matricula,
        'tipomsg' => $tipomsg
    ];

    $data['colaborador'][] = $novosDados;
    $json_atualizado = json_encode($data);

    $arquivo = fopen($path, "w");


    if ($arquivo) {
        fwrite($arquivo, $json_atualizado);
        fclose($arquivo);
        $redis->hmset($chaveRegCli, $existChaveRegCli);

        $dadosReturno = [];

        array_push($dadosReturno, $existChaveRegCli['id'], $existChaveRegCli['contador']);

        return $dadosReturno;
    } else {
        $retorno['erro'] = '0012';
        $retorno['mensagem'] = 'Erro interno ao abrir o arquivo';
        return $retorno;
    }
}

function SalvarDadosJsonEmBanco($idcliente, $keyredis)
{
    return ChamaFuncaoSalvarFileInBank($idcliente, $keyredis);
}

function SaveFileInPath($arq)
{
    $diretorioScript = __DIR__;
    $path = 'historico-mensagens/' . $arq . '.json';
    return $diretorioScript . '/' . $path;
}

function AtualizarColaborador($userid, $dados)
{

    $matricula = null;
    $sql = str_replace([':IDUSER:'], [$userid], "SELECT h.cc, h.filial, h.numerocm FROM z_user_pessoa u INNER JOIN z_hierarquias h ON h.numerocm = u.numerocm WHERE u.zuser = ':IDUSER:'");
    $dadosClb = selectdadosbancocinco($sql);
    $matricula = $dadosClb[0]['NUMEROCM'];
    $cc = $dadosClb[0]['CC'];


    $redis = ConexaoRedis();
    $chaveRegCli = $dados['dep'] . strval($dados['contato']);
    $existChaveRegCli = $redis->hgetall($chaveRegCli);

    $existChaveRegCli['controle_ia'] = false;
    $redis->hmset($chaveRegCli, $existChaveRegCli);
    $redis->quit();


    $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET USUARIO = $matricula, DEPARTAMENTO = '$cc', CONVSATV = 'S' WHERE IDCONTATO = " . $dados['id'];
    return InserDadosBancocinco($sql);
}

function alterarstatuscliente($status, $idcontato, $contato, $userID, $redisdep)
{

    $sqlexitclb = 'SELECT USUARIO FROM W_HISTORICO_MSG_WHATSAPP_PRACA where IDCONTATO = ' . $idcontato;
    $exist = selectdadosbancocinco($sqlexitclb);

    $retorno = ['erro' => 3, 'mensagem' => 'Para mudar o status o colaborador deve assumir a conversa'];

    if ($exist[0]['USUARIO'] == null) {
        return json_encode($retorno);
    }

    $sql = '';

    if ($status == 8) {
        $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET STATUSCONV = '$status', CONVSATV = 'N'  WHERE IDCONTATO = " . $idcontato;
    } else {
        $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET STATUSCONV = '$status' WHERE IDCONTATO = " . $idcontato;
    }

    InserDadosBancocinco($sql);

    if ($status == 3 || $status == 4 || $status == 6) {
        $retorno = ['erro' => 2, 'msg' => 'ok'];
        return json_encode($retorno);
    } else {
        $mensagem = statusConversaCliente($status);
        SalvarJsonERedis($contato, $mensagem, $userID, $redisdep);
        return enviamensagemalterarstatus($mensagem, $contato, $redisdep);
    }
}


function statusConversaCliente($key)
{
    switch ($key) {
        case 2:
            return 'Seu pedido foi confirmado';
        case 3:
            return 'Aguardando Pagamento';
        case 4:
            return 'Pedido pago';
        case 5:
            return 'Seu pedido esta em produção';
        case 6:
            return 'Seu pedido esta aguardando retirada';
        case 7:
            return 'Seu pedido saiu para entrega';
        case 8:
            return 'Pedido entregue ao cliente';
        default:
            return '';
    }
}

function finalizarConversa($dados)
{

    $data = explode(',', $dados['contato']);
    $idcontato = $data[0];

    ChamaFuncaoSalvarFileInBank($idcontato);
    $date = date('d/m/Y H:i:s', time());

    $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET STATUSCONV = 8, DATAHORAULTMSG = TO_DATE('$date', 'DD/MM/YYYY HH24:MI:SS'), CONVSATV = 'N' WHERE IDCONTATO = " . $idcontato;
    InserDadosBancocinco($sql);
}

function inteligenciaAssumeConversa($dados)
{

    $dep = null;

    if ($dados['dep'] == 'Mercado') {
        $dep = 'climercado:';
    } else {
        $dep = 'clipraca:';
    }

    $redis = ConexaoRedis();
    $chaveRegCli = $dep . strval($dados['contato']);
    $existChaveRegCli = $redis->hgetall($chaveRegCli);

    $existChaveRegCli['controle_ia'] = true;
    $redis->hmset($chaveRegCli, $existChaveRegCli);
    $redis->quit();

    $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET USUARIO = NULL WHERE IDCONTATO = " . $dados['idcontato'];
    $retorno = InserDadosBancocinco($sql);

    print_r($retorno);
}


// ----------------------------------- ENVIA PDF E ARQUIVOS ----------------------------------- \\

function uploadimgsendwhatsapp($imagem)
{
    $response = ['erro' => 0, 'mensagem' => ''];

    if ($imagem->getError() === UPLOAD_ERR_OK) {
        $pasta = __DIR__ . '/' . 'img-enviadas-clb/';
        $nomeArquivo = $imagem->getClientFilename();
        $arquivoDestino = $pasta . basename($nomeArquivo);
        $imagem->moveTo($arquivoDestino);

        $response['mensagem'] = "Arquivo " . htmlspecialchars($nomeArquivo) . " enviado com sucesso.";
    } else {
        $response['mensagem'] = "Erro ao enviar o arquivo. Código do erro: " . $imagem->getError();
        $response['mensagem'] = 1;
    }
    return json_encode($response);
}

function uploadiarqpdfendwhatsapp($arqpdf)
{
    $response = ['erro' => 0, 'mensagem' => ''];

    if ($arqpdf->getError() === UPLOAD_ERR_OK) {
        // Defina o diretório onde a arqpdf será salva
        $pasta = __DIR__ . '/' . 'arq-enviados-clb/';
        $nomeArquivo = $arqpdf->getClientFilename();
        $arquivoDestino = $pasta . basename($nomeArquivo);

        // Mova o arquivo para o diretório de destino
        $arqpdf->moveTo($arquivoDestino);

        $response['mensagem'] = "Arquivo " . htmlspecialchars($nomeArquivo) . " enviado com sucesso.";
    } else {
        $response['mensagem'] = "Erro ao enviar o arquivo. Código do erro: " . $arqpdf->getError();
        $response['mensagem'] = 1;
    }
    return json_encode($response);
}

function enviaimagemwhatsapp($numero, $mensagem, $id, $contator, $typedep)
{

    $caminho = __DIR__ . '/' . 'img-enviadas-clb/' . $mensagem;
    salvaArquivosEnviados($numero, $mensagem, $caminho, $id, $contator);

    $idimagem = uploadarquivosenviawhatsapp($caminho, $typedep);
    $data = json_decode($idimagem);

    $retorno = enviaimagem($numero, $data->id, $typedep);
    $retornoimg = json_decode(deletaimgsalvaempasta($mensagem), true);

    if ($retornoimg['erro'] != 0) {
        return json_decode($retornoimg);
    }

    return json_encode($retorno);
}

function enviaarquivopdfmwhatsapp($numero, $mensagem, $id, $contador, $typedep)
{
    $caminho = __DIR__ . '/' . 'arq-enviados-clb/' . $mensagem;
    salvaArquivosEnviados($numero, $mensagem, $caminho, $id, $contador);

    $idimagem = uploadarquivosenviawhatsapp($caminho, $typedep);
    $data = json_decode($idimagem);

    $retorno = enviaarquivopdf($numero, $mensagem, $data->id, $typedep);
    $retornoimg = json_decode(deletaiarqpdfalvaempasta($mensagem), true);

    if ($retornoimg['erro'] != 0) {
        return json_decode($retornoimg);
    }
    return json_encode($retorno);
}


function deletaiarqpdfalvaempasta($arqpdf)
{
    $caminhoarqpdf = __DIR__ . '/' . 'arq-enviados-clb/' . $arqpdf;

    $response = ['erro' => 0, 'mensagem' => ''];

    if (file_exists($caminhoarqpdf)) {
        if (unlink($caminhoarqpdf)) {
            $response['mensagem'] = "Arquivo " . htmlspecialchars($arqpdf) . " deletado com sucesso.";
        } else {
            $response['mensagem'] = "Erro ao deletar o arquivo " . htmlspecialchars($arqpdf) . ".";
            $response['erro'] = 1;
        }
    } else {
        $response['mensagem'] = "Arquivo " . htmlspecialchars($arqpdf) . " não encontrado.";
        $response['erro'] = 2;
    }

    return json_encode($response);
}

function deletaimgsalvaempasta($nomeimg)
{
    $diretorioScript = __DIR__;
    $caminhoimg = $diretorioScript . '/' . 'img-enviadas-clb/' . $nomeimg;

    $response = ['erro' => 0, 'mensagem' => ''];

    if (file_exists($caminhoimg)) {
        if (unlink($caminhoimg)) {
            $response['mensagem'] = "Arquivo " . htmlspecialchars($nomeimg) . " deletado com sucesso.";
        } else {
            $response['mensagem'] = "Erro ao deletar o arquivo " . htmlspecialchars($nomeimg) . ".";
            $response['erro'] = 1;
        }
    } else {
        $response['mensagem'] = "Arquivo " . htmlspecialchars($nomeimg) . " não encontrado.";
        $response['erro'] = 2;
    }

    return json_encode($response);
}


function salvaArquivosEnviados($numero, $mensagem, $caminho, $idcontato, $contator)
{
    $nome_arq = $numero . '_' . $mensagem;

    $nextid = getproxarqid();
    $sqlinserirarq = str_replace(
        [':ID:', ':TIPO:', ':CONTADOR:', ':DATAHORA:', ':NOMEARQ:', ':IDCONTATO:'],
        [$nextid, 'send', $contator, date('d/m/Y H:i:s', time()), $nome_arq, $idcontato],
        SQL_SALVA_ARQUIVOS_SEND_BY_CLB
    );
    $retorno = InserDadosBanco($sqlinserirarq);

    $sqlInsert = str_replace([':ID:'], [$nextid], SQL_SALVA_BLOB_SEND_BY_CLB);
    $retorno = inserirarquivotipoblob($sqlInsert, $caminho);
    $retorno = json_decode($retorno, true);
}

function getproxarqid()
{
    require_once '/funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');

    $conn->connect();
    $id = (int) $conn->query('SELECT W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA_SEQ.NEXTVAL IDCONTATO FROM DUAL')[0]['IDCONTATO'];
    $conn->desconnect();

    return $id;
}


// ------------------------------------------------------------------------------------------------- \\

function conexaobancocinco()
{
    require_once '/funcoes.php';
    $conn = Database\create_DB('db', 'bancodedados');
    return $conn;
}

function selectdadosbancocinco($sql)
{
    $conexao = conexaobancocinco();
    $conexao->connect();
    $json = $conexao->query($sql);
    $conexao->desconnect();
    return $json;
}

function InserDadosBancocinco($sql)
{
    $conexao = conexaobancocinco();
    $conexao->connect();
    $conexao->non_query($sql, [], false);

    if (empty($conexao->get_error())) {
        $conexao->commit();
        $response['dados'] = ['erro' => 0, 'mensagem' => 'Erro 0'];
    } else {
        $conexao->rollback();
        $response['dados'] = ['erro' => 1, 'mensagem' => 'Erro 1'];
    }

    $conexao->desconnect();
    return json_encode($response);
}

function InserirAssNotficacoes($num, $mensagem, $array, $matricula)
{

    $sqlsavecadnum = str_replace(
        [':CONTATO', ':MSG', ':NUMDOC', ':ESTAB', ':TIPODOC', ':NUMEROCM'],
        [$num, $mensagem, $array['codigodoc'], $array['empresa'], $array['tipodocid'], $matricula],
        SQL_C_ASSDIG_NOTIFICACOES
    );
    return InserDadosBancocinco($sqlsavecadnum);
}
