<?php
require_once 'conexaobancodedadoscinco.php';

function inserirarquivotipoblob()
{
    $sql = "UPDATE W_HISTORICO_DOCUMENTS_MSG_WHATSAPP_PRACA SET ARQUIVO = empty_blob() WHERE ID = '72' RETURN ARQUIVO INTO :ARQUIVO";

    // Defina o caminho para o arquivo
    $rota = __DIR__ . '/img-recebidas-cli/imagem_5555912.jpg';
    $conteudoArquivo = file_get_contents($rota);
    // Verifica se o arquivo pode ser lido
    if ($conteudoArquivo === false) {
        echo "Erro ao ler o arquivo: " . $rota;
        return;
    }

    // Conecta ao banco de dados
    $conexao = conexaobanco();


    // Imprime o conteúdo do arquivo para depuração
    echo "Conteúdo do Arquivo: ";
    var_dump($conteudoArquivo);  // Imprime o conteúdo binário da imagem

    // Prepare o BLOB
    $conexao->prepareBlob(':ARQUIVO');

    // Imprime a consulta SQL para depuração
    echo "SQL a ser executado: ";
    var_dump($sql);  // Imprime a consulta SQL

    // Executa a consulta para salvar o BLOB
    $conexao->non_query($sql, [':ARQUIVO' => $conteudoArquivo], false);

    // Verifica se houve erro na execução da consulta
    if (empty($conexao->get_error(true))) {
        $conexao->commit();
        $response['dados'] = ['erro' => 0];
    } else {
        $conexao->rollback();
        $response['dados'] = ['erro' => 1];
    }

    // Desconecta do banco de dados
    $conexao->desconnect();

    // Imprime a resposta
    print_r($response);
}

inserirarquivotipoblob();
