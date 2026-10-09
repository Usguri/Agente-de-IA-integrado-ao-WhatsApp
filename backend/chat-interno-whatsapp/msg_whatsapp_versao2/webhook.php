<?php

$mytoken = require 'keys.php';
$mercado = $mytoken['GRAPH_API_TOKEN_MERCADO'];
$praca = $mytoken['GRAPH_API_TOKEN_PRACA'];

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['hub_challenge']) && isset($_GET['hub_verify_token']) && ($_GET['hub_verify_token'] == $mercado || $_GET['hub_verify_token'] == $praca)) {
    echo $_GET['hub_challenge'];
    exit;
}

$dadosRecebidos = file_get_contents('php://input');
$requestData = json_decode($dadosRecebidos, true);

class RequestHandler
{
    public function handleRequest($requestData)
    {
        require_once 'salva-dados-redis.php';
        $dadosNotificacoes = [];

        if ($requestData != null) {


            // STATUS RECEBIDO AO ENVIAR A MENSAGEM PARA O CLIENTE \\

            if (isset($requestData['entry'][0]['changes'][0]['value']['statuses'])) {

                $recipientId = $requestData['entry'][0]['changes'][0]['value']['statuses'][0]['recipient_id'];
                $status = $requestData['entry'][0]['changes'][0]['value']['statuses'][0]['status'];
                $timestamp = $requestData['entry'][0]['changes'][0]['value']['statuses'][0]['timestamp'];
                $numdep = $requestData['entry'][0]['changes'][0]['value']['metadata']['display_phone_number'];
                $idclidepredis = null;

                if ($numdep == '15556069760') {
                    $numdep = 'Praça';
                    $idclidepredis = 'clipraca:' . strval($recipientId);
                } else if ($numdep == '15556049784') {
                    $numdep = 'Mercado';
                    $idclidepredis = 'climercado:' . strval($recipientId);
                }

                array_push($dadosNotificacoes, $recipientId, $status, $timestamp, $idclidepredis);
                RecebeStatusMsg($dadosNotificacoes);
            }
            // MENSAGEM RECEBIDO DO CLIENTE \\

            else if (isset($requestData['entry'][0]['changes'][0]['value']['messages'])) {

                $from = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['from'];
                $idmessage = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['id'];
                $timestamp = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['timestamp'];
                $numdep = $requestData['entry'][0]['changes'][0]['value']['metadata']['display_phone_number'];
                $nomearq = null;
                $idclidepredis = null;
                $numerodep = null;

                if ($requestData['entry'][0]['changes'][0]['value']['messages'][0]['text']) {
                    $message = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['text']['body'];
                    $tipo = 0;
                } else if ($requestData['entry'][0]['changes'][0]['value']['messages'][0]['audio']) {
                    $message = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['audio']['id'];
                    $nomearq = $from . '_' . 'audio';
                    $tipo = 1;
                } else if ($requestData['entry'][0]['changes'][0]['value']['messages'][0]['image']) {
                    $message = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['image']['id'];
                    $nomearq = $from . '_' . 'imagem';
                    $tipo = 2;
                } else if ($requestData['entry'][0]['changes'][0]['value']['messages'][0]['document']) {
                    $message = $requestData['entry'][0]['changes'][0]['value']['messages'][0]['document']['id'];
                    $nomearq =  $from . '_' . $requestData['entry'][0]['changes'][0]['value']['messages'][0]['document']['filename'];
                    $tipo = 3;
                }


                if ($numdep == '15556069760') {
                    $numerodep = $numdep;
                    $numdep = 'Praça';
                    $idclidepredis = 'clipraca:';
                } else if ($numdep == '15556049784') {
                    $numerodep = $numdep;
                    $numdep = 'Mercado';
                    $idclidepredis = 'climercado:';
                }


                $valida = clienteexistnoredisrecebemsg($idclidepredis . $from);
                array_push($dadosNotificacoes, $from, $idmessage, $timestamp, $message, $tipo, $nomearq, $numdep, $idclidepredis . $from, $numerodep, $idclidepredis);
                RecebeMsgCliente($dadosNotificacoes, $valida);
            }
        }
    }
}

$requestHandler = new RequestHandler();
$requestHandler->handleRequest($requestData);
