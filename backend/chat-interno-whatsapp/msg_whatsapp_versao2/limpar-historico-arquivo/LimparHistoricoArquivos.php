<?php

require 'funcoes.php';
define('REDIS', 'libs/predis/autoload.php');
require_once REDIS;

define("SQL_W_MENSAGENS_WHATSAPP_AVISOS_SELECT_", "SELECT");

define('SQL_UPDATE_HISTORICOMENSAGEM_WHATSAPP_', "UPDATE");

function conexaobancoredis()
{
    return new \Predis\Client([
        'scheme' => 'tcp',
        'host' => '127.0.0.1',
        'port' => 6379,
        'database' => 1
    ]);
}

function conexaobancocinco()
{
    $conn = Database\create_DB('db', 'bancodedados');
    return $conn;
}

function selectdadosbanco($sql)
{
    $conexao = conexaobancocinco();
    $conexao->connect();
    $json = $conexao->query($sql);
    $conexao->desconnect();
    return $json;
}

function updatedadosbanco($sql)
{
    $conexao = conexaobancocinco();
    $conexao->connect();
    $conexao->non_query($sql, [], false);

    if (empty($conexao->get_error())) {
        $conexao->commit();
        $response['dados'] = ['erro' => 0];
    } else {
        $conexao->rollback();
        $response['dados'] = ['erro' => 1];
    }

    $conexao->desconnect();
    return json_encode($response);
}

function SaveFileInPath($arq)
{
    return '../historico-mensagens/' . $arq . '.json';
}

function insertArqBlob($sql, $rota)
{
    $conexao = conexaobancocinco();
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

function ChamaFuncaoSalvarFileInBank($idcont)
{

    $sql = str_replace([':IDCONTATO:'], [$idcont], SQL_W_MENSAGENS_WHATSAPP_AVISOS_SELECT_);
    $dados = selectdadosbanco($sql);

    $contato = substr($dados[0]['CONTATO'], 0, strpos($dados[0]['CONTATO'], '9')) . substr($dados[0]['CONTATO'], strpos($dados[0]['CONTATO'], '9') + 1);
    $IDCliente = $dados[0]['IDCONTATO'];



    if (isset($dados[0]['HISTORICOMENSAGEM'])) {
        $blob = $dados[0]['HISTORICOMENSAGEM'];
        $jsonDadosCliente = json_decode($blob, true);
    } else {
        $blob = [
            'cliente' => [],
            'colaborador' => []
        ];
        $jsonDadosCliente = $blob;
    }



    $redis = conexaobancoredis();
    $chaveRegCli = 'reg_chat_cli:' . $contato;
    $existeChave = $redis->exists($chaveRegCli);

    if ($existeChave) {

        $path = SaveFileInPath($chaveRegCli);

        if (file_exists($path)) {

            $conteudo_existente = file_get_contents($path);
            $data = json_decode($conteudo_existente, true);

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


            $sqlInsert = str_replace([':IDCONTATO:'], [$IDCliente], SQL_UPDATE_HISTORICOMENSAGEM_WHATSAPP_);

            $retorno = insertArqBlob($sqlInsert, $path);
            $retorno = json_decode($retorno, true);


            if ($retorno['dados']['erro'] == 0) {
                if (unlink($path)) {
                    print_r("Arquivo deletado com sucesso.");
                    echo '<br>';
                } else {
                    print_r("Erro ao deletar o arquivo.");
                    echo '<br>';
                }
            }
        }
    }
}