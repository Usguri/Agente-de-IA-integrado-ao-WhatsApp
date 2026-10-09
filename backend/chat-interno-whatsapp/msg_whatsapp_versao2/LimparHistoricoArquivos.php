<?php

define('REDIS', 'libs/predis/autoload.php');
require_once REDIS;

define("SQL_GET_MENSAGENS_CLIENTE", "select");
define('SQL_ATUALIZA_MENSAGENS_CLIENTE', "update");

function conexaobancoredis()
{
    return new \Predis\Client([
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ]);
}

function criaconexaobancocinco()
{
    $conn = Database\create_DB('db', 'bancodedados');
    return $conn;
}

function selectbancodedados($sql)
{
    $conexao = criaconexaobancocinco();
    $conexao->connect();
    $json = $conexao->query($sql);
    $conexao->desconnect();
    return $json;
}

function caminhoparasalvararquivo($arq)
{
    $diretorioScript = __DIR__;
    $path = 'historico-mensagens/' . $arq . '.json';
    return $diretorioScript . '/' . $path;
}

function inserirarquivoblobbanco($sql, $rota)
{
    $conexao = criaconexaobancocinco();
    $conexao->prepareBlob(':HISTORICOMENSAGEM');
    $conexao->non_query($sql, [':HISTORICOMENSAGEM' => file_get_contents($rota)], false);

    if (empty($conexao->get_error(true))) {
        $conexao->commit();
        $response['dados'] = ['erro' => 0];
    } else {
        $conexao->rollback();
        $response['dados'] = ['erro' => 1];
    }

    $conexao->desconnect();

    return json_encode($response);
}

function ChamaFuncaoSalvarFileInBank($idcont, $keyredis)
{
    $retornafront = [];

    $sql = str_replace([':IDCONTATO:'], [$idcont], SQL_GET_MENSAGENS_CLIENTE);
    $dados = selectbancodedados($sql);

    if (isset($dados[0])) {
        $contato = substr($dados[0]['CONTATO'], 0, strpos($dados[0]['CONTATO'], '9')) . substr($dados[0]['CONTATO'], strpos($dados[0]['CONTATO'], '9') + 1);
        $IDCliente = $dados[0]['IDCONTATO'];

        if (isset($dados[0]['HISTORICOMENSAGEM'])) {
            $blob = $dados[0]['HISTORICOMENSAGEM'];
            $jsonDadosCliente = json_decode($blob, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $jsonDadosCliente = ['cliente' => [], 'colaborador' => []];
            }
        } else {
            $jsonDadosCliente = ['cliente' => [], 'colaborador' => []];
        }

        $redis = conexaobancoredis();
        $chaveRegCli = $keyredis . $contato;
        $existeChave = $redis->exists($chaveRegCli);

        if ($existeChave) {
            $path = caminhoparasalvararquivo($chaveRegCli);

            if (file_exists($path)) {
                $conteudo_existente = file_get_contents($path);
                $data = json_decode($conteudo_existente, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $data = ['cliente' => [], 'colaborador' => []];
                }

                $ClbFile = $data['cliente'];
                $ClienteFile = $data['colaborador'];

                foreach ($ClbFile as $cli) {
                    $jsonDadosCliente['cliente'][] = $cli;
                }

                foreach ($ClienteFile as $clb) {
                    $jsonDadosCliente['colaborador'][] = $clb;
                }

                $json_atualizado = json_encode($jsonDadosCliente);
                $arquivo = fopen($path, "w");

                if ($arquivo) {
                    fwrite($arquivo, $json_atualizado);
                    fclose($arquivo);
                }

                $sqlInsert = str_replace([':IDCONTATO:'], [$IDCliente], SQL_ATUALIZA_MENSAGENS_CLIENTE);
                $retorno = inserirarquivoblobbanco($sqlInsert, $path);
                $retorno = json_decode($retorno, true);

                if (isset($retorno['dados']['erro']) && $retorno['dados']['erro'] == 0) {
                    if (unlink($path)) {
                        $retornafront['erro'] = '2';
                        $retornafront['mensagem'] = 'Arquivo deletado com sucesso!';
                    } else {
                        $retornafront['erro'] = '3';
                        $retornafront['mensagem'] = 'Erro ao deletar o arquivo!';
                    }
                }
            } else {
                $retornafront['erro'] = '1';
                $retornafront['mensagem'] = 'Arquivo não existe!';
            }
        }
    } else {
        $retornafront['erro'] = '1';
        $retornafront['mensagem'] = 'Dados não encontrados! ' . $sql;
    }

    return $retornafront;
}
