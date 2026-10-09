<?php

function conexaobanco()
{
    $conn = Database\create_DB('db', 'bancodedados');
    return $conn;
}

function selectdadosbanco($sql)
{
    $conexao = conexaobanco();
    $conexao->connect();
    $json = $conexao->query($sql);
    $conexao->desconnect();
    return $json;
}

function insertArqBlob($sql, $rota)
{
    $conexao = conexaobanco();
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

function inserirarquivotipoblob($sql, $rota)
{
    $conexao = conexaobanco();
    $conexao->prepareBlob(':ARQUIVO');
    $conexao->non_query($sql, [':ARQUIVO' => file_get_contents($rota)], false);

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

function updatedadosbanco($sql)
{
    $conexao = conexaobanco();
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

function InserDadosBanco($sql)
{
    $conexao = conexaobanco();
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
