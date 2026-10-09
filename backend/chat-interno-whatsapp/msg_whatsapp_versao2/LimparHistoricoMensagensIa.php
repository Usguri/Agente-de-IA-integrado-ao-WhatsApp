<?php

$diretorio = __DIR__ . '/' . 'conversas-cliente';
$arquivos = scandir($diretorio);

foreach ($arquivos as $arquivo) {
    if ($arquivo !== '.' && $arquivo !== '..') {

        $caminho_arquivo = __DIR__ . '/' . 'conversas-cliente/' . $arquivo;
        unlink($caminho_arquivo);
    }
}
