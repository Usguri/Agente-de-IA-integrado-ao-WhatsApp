<?php

global $chaves;
$chaves = require 'keys.php';

function AvisoClbAssumeConversa($numero, $nomeclb) // Informa ao cliente quem será o nome do CLB 
{
    global $chaves;
    $retorno = [];

    $url = $chaves['URL_API_WHATSAPP_PRACA'];
    $token = $chaves['GRAPH_API_TOKEN'];

    $mensagem = '[ *' . $nomeclb . '* ]';

    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $numero,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $mensagem
        ]
    ];

    $jsonData = json_encode($data);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);


    if (curl_errno($ch)) {
        $retorno['mensagem'] = 'Erro ao enviar a mensagem: ' . curl_error($ch);
        $retorno['erro'] = '1';
    } else {
        // Verificar o status da resposta
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            $retorno['mensagem'] = 'Você assumiu a conversa com o cliente!';
            $retorno['erro'] = '2';
        } else {

            $retorno['mensagem'] = 'Falha ao enviar a mensagem. Código de status HTTP: ' . $httpCode . PHP_EOL . $response;
            $retorno['erro'] = '3';
        }
    }

    curl_close($ch);
    return $retorno;
}

function clbconversacomclienteEIntelig($num, $msg, $typedep) // Mensagem enviada pelo colaborador
{
    global $chaves;

    $retorno = [];
    $url = '';
    $token = '';

    if ($typedep == 'climercado:') {
        $url = $chaves['URL_API_WHATSAPP_MERCADO'];
        $token = $chaves['GRAPH_API_TOKEN_MERCADO'];
    } else if ($typedep == 'clipraca:') {

        $url = $chaves['URL_API_WHATSAPP_PRACA'];
        $token = $chaves['GRAPH_API_TOKEN_PRACA'];
    }


    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $num,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $msg
        ]
    ];

    $jsonData = json_encode($data);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json', 'Authorization: Bearer ' . $token]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $retorno['mensagem'] = 'Erro ao enviar a mensagem: ' . curl_error($ch);
        $retorno['erro'] = '1';
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {

            $retorno['mensagem'] = 'Mensagem enviada com sucesso!';
            $retorno['erro'] = '2';
        } else {
            $retorno['mensagem'] = 'Falha ao enviar a mensagem. Código de status HTTP: ' . $httpCode . PHP_EOL . ' Resposta do servidor: ' . $response;
            $retorno['erro'] = '3';
        }
    }

    curl_close($ch);
    return json_encode($retorno);
}

function uploadarquivosenviawhatsapp($pathimagem, $typedep) // sobre arquivo para o whatsapp
{
    global $chaves;

    $urlmedia = '';
    $token = '';

    if ($typedep == 'climercado:') {
        $urlmedia = $chaves['URL_MEDIA_API_WHATSAPP_MERCADO'];
        $token = $chaves['GRAPH_API_TOKEN_MERCADO'];
    } else if ($typedep == 'clipraca:') {
        $urlmedia = $chaves['URL_MEDIA_API_WHATSAPP_PRACA'];
        $token = $chaves['GRAPH_API_TOKEN_PRACA'];
    }

    $filePath = $pathimagem;
    $fileMimeType = mime_content_type($filePath);

    $curlFile = new CURLFILE($filePath, $fileMimeType);

    $curl = curl_init();

    curl_setopt_array($curl, array(
        CURLOPT_URL => $urlmedia,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => array(
            'messaging_product' => 'whatsapp',
            'file' => $curlFile
        ),
        CURLOPT_HTTPHEADER => array(
            'Authorization: Bearer ' . $token
        ),
    ));

    $response = curl_exec($curl);

    if (curl_errno($curl)) {
        $error_msg = curl_error($curl);
        echo "Erro cURL: " . $error_msg;
    } else {
        return $response;
    }

    curl_close($curl);
}

function enviaimagem($numero, $id, $typedep) // envia mensagem em formato de imagem
{

    global $chaves;
    $retorno = [];

    $url = '';
    $token = '';

    if ($typedep == 'climercado:') {
        $url = $chaves['URL_API_WHATSAPP_MERCADO'];
        $token = $chaves['GRAPH_API_TOKEN_MERCADO'];
    } else if ($typedep == 'clipraca:') {

        $url = $chaves['URL_API_WHATSAPP_PRACA'];
        $token = $chaves['GRAPH_API_TOKEN_PRACA'];
    }

    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $numero,
        'type' => 'image',
        'image' => [
            'id' => $id
        ]
    ];

    $jsonData = json_encode($data);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);


    if (curl_errno($ch)) {
        $retorno['mensagem'] = 'Erro ao enviar a mensagem: ' . curl_error($ch);
        $retorno['erro'] = '1';
    } else {
        // Verificar o status da resposta
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            $retorno['mensagem'] = 'Imagem enviada!';
            $retorno['erro'] = '2';
        } else {

            $retorno['mensagem'] = 'Falha ao enviar a mensagem. Código de status HTTP: ' . $httpCode . PHP_EOL . $response;
            $retorno['erro'] = '3';
        }
    }

    curl_close($ch);
    return $retorno;
}

function enviaarquivopdf($numero, $nomearq, $id, $typedep) // envia mensagem em formato de pdf
{
    global $chaves;
    $retorno = [];

    $url = '';
    $token = '';

    if ($typedep == 'climercado:') {

        $url = $chaves['URL_API_WHATSAPP_MERCADO'];
        $token = $chaves['GRAPH_API_TOKEN_MERCADO'];
    } else if ($typedep == 'clipraca:') {

        $url = $chaves['URL_API_WHATSAPP_PRACA'];
        $token = $chaves['GRAPH_API_TOKEN_PRACA'];
    }

    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $numero,
        'type' => 'document',
        'document' => [
            'id' => $id,
            'filename' => $nomearq
        ]
    ];

    $jsonData = json_encode($data);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);

    $response = curl_exec($ch);


    if (curl_errno($ch)) {
        $retorno['mensagem'] = 'Erro ao enviar a mensagem: ' . curl_error($ch);
        $retorno['erro'] = '1';
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($httpCode == 200) {
            $retorno['mensagem'] = 'Arquivo PDF enviado!';
            $retorno['erro'] = '2';
        } else {

            $retorno['mensagem'] = 'Falha ao enviar a mensagem. Código de status HTTP: ' . $httpCode . PHP_EOL . $response;
            $retorno['erro'] = '3';
        }
    }

    curl_close($ch);
    return $retorno;
}

function enviamensagemalterarstatus($status, $numero, $typedep)
{

    global $chaves;
    $retorno = ['erro' => 0, 'msg' => ''];

    $url = '';
    $token = '';

    if ($typedep == 'climercado:') {
        $url = $chaves['URL_API_WHATSAPP_MERCADO'];
        $token = $chaves['GRAPH_API_TOKEN_MERCADO'];
    } else if ($typedep == 'clipraca:') {
        $url = $chaves['URL_API_WHATSAPP_PRACA'];
        $token = $chaves['GRAPH_API_TOKEN_PRACA'];
    }

    // Dados que serão enviados na requisição
    $data = [
        'messaging_product' => 'whatsapp',
        'recipient_type' => 'individual',
        'to' => $numero,
        'type' => 'text',
        'text' => [
            'preview_url' => false,
            'body' => $status
        ]
    ];

    $jsonData = json_encode($data);
    $ch = curl_init($url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $token
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $retorno['msg'] = 'Erro ao enviar a mensagem: ' . curl_error($ch);
        $retorno['erro'] = 1;
    } else {
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($httpCode == 200) {
            $retorno['msg'] = 'Mensagem enviada com sucesso!';
            $retorno['erro'] = 2;
        } else {
            $retorno['msg'] = 'Falha ao enviar a mensagem. Código de status HTTP: ' . $httpCode . PHP_EOL . $response;
            $retorno['erro'] = 3;
        }
    }

    curl_close($ch);
    return json_encode($retorno);
}
