<?php

function organizarTableConversa($userid = null)
{

    $restosql = '';

    if ($userid == null) {

        $sql = str_replace([':CONSULTA:'], [$restosql], SQL_LISTA_ALL_CHATS_WHATSAPP_);
        $data = selectdadosbanco($sql);

        $dados = [];

        foreach ($data as $l) {

            $dados[] = [
                'idcontato' => $l["IDCONTATO"],
                'contato' => $l["CONTATO"],
                'datahora' => $l["DATAHORAULTMSG"],
                'mensagens' => $l["MENSAGENS"],
                'usuario' => $l["USUARIO"],
                'statusenv' => $l["STATUSCONV"],
                'setor' => $l['NOMEDEP'],
                'nomecli' => $l["NOMECLI"]
            ];
        }

        print_r(json_encode($dados));
    } else {

        $sqlcc = str_replace([':USERID:'], [$userid], SQL_VERIFICA_SETOR_USUARIO);
        $dadosRetorno = selectdadosbanco($sqlcc);

        if ($dadosRetorno[0]['CC'] != '0121') {
            $restosql = 'WHERE whmwp.DEPARTAMENTO = ' . $dadosRetorno[0]['CC'];
        }

        $sql = str_replace([':CONSULTA:'], [$restosql], SQL_LISTA_ALL_CHATS_WHATSAPP_);
        $data = selectdadosbanco($sql);

        $dados = [];

        foreach ($data as $l) {

            $nomeusuario = ajustenome($l["NOME"]);

            $btns = [
                'visualizar' => '<button title="Visualizar conversa" class="visualizarConversa btn btn-success" data-id="' . $l["IDCONTATO"] . ',' . $l["CONTATO"] . ',' . $l["USUARIO"] . ', ' . $l['NOMEDEP'] . '"><i class="fas fa-eye" aria-hidden="true"></i></button>',
                'assumir' => '<button title="Assumir conversa" class="assumirConversa btn btn-warning" data-id="' . $l["IDCONTATO"] . ',' . $l["CONTATO"] . ',' . $l["USUARIO"] . ', ' . $l['NOMEDEP'] . '"><i class="fas fa-hand-paper" aria-hidden="true"></i></button>',
                'finalizarConversa' => '<button title="Finalizar conversa" class="finalizarConversa btn btn-danger" data-id="' . $l["IDCONTATO"] . ',' . $l["CONTATO"] . '"><i class="fas fa-comment-slash" aria-hidden="true"></i></button>',
                'roboassumeconversa' => '<button title="IA assume conversa" class="inteligenciaassumeconversa btn btn-info" data-id="' . $l["CONTATO"] . ', ' . $l['NOMEDEP'] . ',' . $l["IDCONTATO"] . '"><i class="fas fa-robot"></i></button>',
                'abrirnovaguia' => '<button title="Abrir chat segundario" class="abrirNovaGuia btn btn-secondary" data-id="' . $userid . '"><i class="fas fa-external-link-square-alt"></i></button>'
            ];


            $deldoisNum = substr($l["CONTATO"], 2);
            $firstTwo = substr($deldoisNum, 0, 2);
            $remaining = substr($deldoisNum, 2);
            $contato = sprintf('(%s) %s', $firstTwo, $remaining);

            $dados[] = [
                'contato' => $contato,
                'status' => statusConversa($l["STATUSCONV"], $l["IDCONTATO"], $l["CONTATO"], $l['NOMEDEP']),
                'dataHora' => $l["DATAHORAULTMSG"],
                'descricao' => $l["DESCRICAO"],
                'usuario' => $nomeusuario,
                'mensagens' => $l["MENSAGENS"],
                'buttons' => $btns,
                'nomecli' => $l["NOMECLI"],
                'idcontato' => $l['IDCONTATO'],
                'treinamento' => $l['TREINAMENTO'],
                'setor' => $l['NOMEDEP']
            ];
        }
        print_r(json_encode($dados));
    }
}

function ajustenome($nome)
{
    if ($nome != null) {
        $nomeCompleto = ucwords(strtolower($nome));

        // Separa o nome em partes
        $partesDoNome = explode(' ', $nomeCompleto);

        // Pega o primeiro e o último nome
        $primeiroNome = $partesDoNome[0];
        $ultimoNome = end($partesDoNome);

        // Junta o primeiro e o último nome
        $nomeFormatado = $primeiroNome . ' ' . $ultimoNome;

        return $nomeFormatado;
    } else {
        return 'IA assumiu conversa';
    }
}

function statusConversa($key, $id, $contato, $nomedep)
{
    switch ($key) {
        case 1:
            return '<div class="alert alert-danger mudarstatus" role="alert" style="border-color:red; cursor: pointer;" data-status="1,' . $id . ',' . $contato . ',' . $nomedep . '">Em atendimento</div>';
        case 2:
            return '<div class="alert alert-warning mudarstatus" role="alert" style="border-color:orange; cursor: pointer;" data-status="2,' . $id . ',' . $contato . ',' . $nomedep . '">Pedido confirmado</div>';
        case 3:
            return '<div class="alert alert-warning mudarstatus" role="alert" style="border-color:orange; cursor: pointer;" data-status="3,' . $id . ',' . $contato . ',' . $nomedep . '">Aguardando Pagamento</div>';
        case 4:
            return '<div class="alert alert-secondary mudarstatus" role="alert" style="border-color:darkgray; cursor: pointer;" data-status="4,' . $id . ',' . $contato . ',' . $nomedep . '">Pedido pago</div>';
        case 5:
            return '<div class="alert alert-info mudarstatus" role="alert" style="border-color:darkgray; cursor: pointer;" data-status="5,' . $id . ',' . $contato . ',' . $nomedep . '">Pedido em produção</div>';
        case 6:
            return '<div class="alert alert-primary mudarstatus" role="alert" style="border-color:blue; cursor: pointer;" data-status="6,' . $id . ',' . $contato . ',' . $nomedep . '">Aguardando retirada</div>';
        case 7:
            return '<div class="alert alert-success mudarstatus" role="alert" style="border-color:green;cursor: pointer;" data-status="7,' . $id . ',' . $contato . ',' . $nomedep . '">Saiu para entrega</div>';
        case 8:
            return '<div class="alert alert-success mudarstatus" role="alert" style="border-color:green;cursor: pointer;" data-status="8,' . $id . ',' . $contato . ',' . $nomedep . '">Entrega confirmada</div>';
        default:
            return '';
    }
}

function transferircolaboradorconversa($id, $clb)
{

    $sql = str_replace([':USUARIO:', ':IDCONTATO:'], [$clb, $id], SQL_UPDATE_USUARIO_WITH_WHATSAPP_);
    $setor = updatedadosbanco($sql);
    print_r($setor);
}

function atualizarnummensagens($id)
{
    $sql = str_replace([':IDCONTATO:'], [$id], SQL_UPDATE_NUM_MENSAGENS_WHATSAPP_);
    $setor = updatedadosbanco($sql);
}

function CarregaChats($id)
{
    $sql = str_replace([':IDCONTATO:'], [$id], SQL_BUSCA_CONVERSA_CLIENTE);
    $dadosRetorno = selectdadosbanco($sql);
    print_r(json_encode($dadosRetorno));
}

function buscararquivos($idcontato, $contator)
{
    $sql = str_replace([':IDCONTATO:', ':CONTADOR:'], [$idcontato, $contator], SQL_BUSCA_ARQUIVOS);
    $dadosRetorno = selectdadosbanco($sql);

    return $dadosRetorno[0]['ARQUIVO'];
}

function buscarnomearq($idcontato, $contator)
{
    $sql = str_replace([':IDCONTATO:', ':CONTADOR:'], [$idcontato, $contator], SQL_BUSCA_ARQUIVOS);
    $dadosRetorno = selectdadosbanco($sql);

    return $dadosRetorno[0]['NOMEARQ'];
}

function atualizarnomecliente($idcontato, $nome)
{
    $sql = "UPDATE W_HISTORICO_MSG_WHATSAPP_PRACA SET NOMECLI = '$nome' WHERE IDCONTATO = " . $idcontato;
    return updatedadosbanco($sql);
}
