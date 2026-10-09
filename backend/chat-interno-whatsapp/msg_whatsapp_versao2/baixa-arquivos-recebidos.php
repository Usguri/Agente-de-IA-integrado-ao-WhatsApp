<?php

global $chaveskey;
$chaveskey = require 'keys.php';

function baixarmediawhatsapp($dados, $contator, $id) // Apos chegar qualquer tipo de media enviado pelo cliente, deve acessar esta função
{

    global $chaveskey;
    $token = $chaveskey['GRAPH_API_TOKEN'];
    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => 'https://graph.facebook.com/v20.0/' . $dados[3],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'GET',
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        echo "Erro cURL: " . $error_msg;
    } else {
        $mime_type = null;
        $data = json_decode($response, true);

        switch ($dados[4]) {
            case '1':
                $arq_exec = 'baixaraudio.py';
                break;
            case '2':
                $mime_type = $data['mime_type'];
                $arq_exec = 'baixarimagem.py';
                break;
            case '3':
                $arq_exec = 'baixararquivopdf.py';
                break;
        }

        salvarmediawhatsapp($data['url'], $arq_exec, $dados[5], $mime_type, $dados[4], $contator, $id);
    }

    curl_close($curl);
}

function salvarmediawhatsapp($url, $aqrexec, $nome_arq, $tipo, $tipoarq, $contator, $idcontator) // salvar arquivo audio,foto ou pdf em pasta
{

    global $chaveskey;
    $token = $chaveskey['GRAPH_API_TOKEN'];
    $nome = $nome_arq;


    // Caminho absoluto para o executável compilado
    $executable = __DIR__ . '/exec-python' . '/' . $aqrexec;

    putenv("TOKEN=$token");
    putenv("URL=$url");
    putenv("NOME=$nome");
    putenv("TIPOARQ=$tipo");


    $command = escapeshellcmd("python $executable");
    $output = shell_exec($command);

    Salvardocrecebidos($idcontator, $tipoarq, $contator, $nome_arq, 'received');
}

define('SQL_SALVA_ARQUIVOS_RECEBIDOS_CLIENTE', "INSERT INTO CINCO.W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA (ID,TIPO,CONTADOR,DATAHORA,NOMEARQ,IDCONTATO) VALUES(':ID:',':TIPO:',':CONTADOR:', TO_DATE(':DATAHORA:', 'DD/MM/YYYY HH24:MI:SS'),':NOMEARQ:',':IDCONTATO:')");
define('SQL_SALVA_BLOB', "UPDATE CINCO.W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA SET ARQUIVO = empty_blob() WHERE ID = ':ID:' RETURN ARQUIVO INTO :ARQUIVO");

function Salvardocrecebidos($idcontato, $idarq, $contator, $nome_arq, $received)
{
    require_once 'conexaobancodedadoscinco.php';


    $caminho = '';
    if ($idarq == 1) {
        $caminho = __DIR__ . '/' . 'aud-recebidos-cli/' . $nome_arq . '.ogg';
        $nome_arq = $nome_arq . '.ogg';
    } else if ($idarq == 2) {
        $pathjpg = __DIR__ . '/' . 'img-recebidas-cli/' . $nome_arq . '.jpg';
        $pathpng = __DIR__ . '/' . 'img-recebidas-cli/' . $nome_arq . '.png';
        $pathgif = __DIR__ . '/' . 'img-recebidas-cli/' . $nome_arq . '.gif';

        if (file_exists($pathjpg)) {
            $caminho = $pathjpg;
            $nome_arq = $nome_arq . '.jpg';
        } else if (file_exists($pathpng)) {
            $caminho = $pathpng;
            $nome_arq = $nome_arq . '.png';
        } else if (file_exists($pathgif)) {
            $caminho = $pathgif;
            $nome_arq = $nome_arq . '.gif';
        }
    } else if ($idarq == 3) {
        $caminho = __DIR__ . '/' . 'arq-recebidos-cli/' . $nome_arq;
    }



    $nextid = GetproximoId();
    $sqlinserirarq = str_replace(
        [':ID:', ':TIPO:', ':CONTADOR:', ':DATAHORA:', ':NOMEARQ:', ':IDCONTATO:'],
        [$nextid, $received, $contator, date('d/m/Y H:i:s', time()), $nome_arq, $idcontato],
        SQL_SALVA_ARQUIVOS_RECEBIDOS_CLIENTE
    );
    $retorno = InserDadosBanco($sqlinserirarq);

    $sqlInsert = str_replace([':ID:'], [$nextid], SQL_SALVA_BLOB);
    $retorno = inserirarquivotipoblob($sqlInsert, $caminho);
    $retorno = json_decode($retorno, true);
}

function GetproximoId()
{
    require_once 'funcoes.php';
    $conn = Database\create_DB('oracle', 'bancocinco');

    $conn->connect();
    $id = (int) $conn->query('SELECT CINCO.W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA_SEQ.NEXTVAL IDCONTATO FROM DUAL')[0]['IDCONTATO'];
    $conn->desconnect();

    return $id;
}
