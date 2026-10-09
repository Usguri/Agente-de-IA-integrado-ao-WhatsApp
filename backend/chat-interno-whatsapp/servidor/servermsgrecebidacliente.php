<?php

require "./../msg_whatsapp_versao2/load_dependencies.php";
require "./vendor/autoload.php";

use WebSocket\Client;

try {

    $client = new Client("wss://ip/retornowebsocket/");

    $existing_data = file_get_contents('idclientes_temp.json');
    $data_array = json_decode($existing_data, true);

    $keyredis = array_shift($data_array);

    $json_data = json_encode($data_array);
    file_put_contents('idclientes_temp.json', $json_data);


    $dados = PrintaDadosClienteRedis($keyredis);
    $client->send(json_encode($dados));
} catch (Exception $e) {
    echo "Erro ao estabelecer a conexão: " . $e->getMessage() . "\n";
}

$client->close();
