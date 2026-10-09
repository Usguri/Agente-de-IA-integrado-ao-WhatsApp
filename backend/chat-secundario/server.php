<?php

use Swoole\WebSocket\Server;

$ws = new Server("0.0.0.0", 5001);

$ws->on("open", function ($ws, $request) {
    echo "Cliente {$request->fd} conectado.\n";
});

$ws->on("message", function ($ws, $frame) {
    echo "\nRecebida mensagem: {$frame->data}\n";

    // Enviar a mensagem recebida para todos os clientes conectados
    foreach ($ws->connections as $fd) {
        $ws->push($fd, $frame->data);
    }
});

$ws->on("close", function ($ws, $fd) {
    echo "Cliente {$fd} desconectado.\n";
});

$ws->start();
