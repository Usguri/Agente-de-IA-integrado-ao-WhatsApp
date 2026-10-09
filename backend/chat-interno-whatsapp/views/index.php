<?php
?>

<!DOCTYPE html>
<html>

<head>

    <link rel="stylesheet" href="<?= CSS_RESET ?>">
    <?= DEFAULT_TAREFAS ?>

    <!--COMEÇA IMPORT POLIPOP -->
    <script src="<?= POLIPOP1JS ?>"></script>
    <link href="<?= POLIPOP1CSSCORE ?>" rel="stylesheet" type="text/css" />
    <link href="<?= POLIPOP1CSSTHEME ?>" rel="stylesheet" type="text/css" />

    <link href="<?= ICONES ?>css/all.min.css" type="text/css" rel="stylesheet">
    <link rel="stylesheet" href="<?= ICONES ?>css/all.min.css">
    <link rel="stylesheet" href="<?= JQUERY ?>datatables/datatables.min.css" />
    <link rel="stylesheet" href="<?= JQUERY ?>datatables/Responsive-2.2.2/css/responsive.bootstrap4.min.css" />

    <script src="<?= JQUERY ?>datatables/datatables.min.js"></script>
    <script src="<?= JQUERY ?>datatables/Responsive-2.2.2/js/responsive.bootstrap4.min.js"></script>
    <script src="<?= JQUERY ?>jquery.mask.js"></script>
    <script src="../../frontend/chat-interno-whatsapp/js/main.js?v=<?= time() ?>"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <title>Chat cotribá</title>
</head>

<style>
    .div-filho {
        border-radius: 4px;
        background-color: brown;
        height: 700px;
    }

    .card-dialog {
        height: 100%;
    }

    .caixaDialog {
        position: absolute;
        top: 300px;
        /* left: 1400px; */
        left: 75%;
        cursor: move;
        width: 22%;
        float: right;
        height: 60%;
        border-color: black 1px solid;
        display: none;
    }

    .lista-conversas-cliente {
        border: 0.5px solid black;
        border-radius: 4px;
        max-height: 530px;
        overflow: auto;
        padding-left: 18px;
        padding-right: 16px;
        padding-top: 4px;
    }

    .mensagem-azul,
    .mensagem-vermelha {
        margin: 0 auto;
        width: 80%;
    }

    .dialogcomcliente {
        margin-top: 36px;
        border: 0.1px solid black;
        border-radius: 4px;
    }

    .flex-container {
        display: flex;
        justify-content: space-between;
    }

    #caixaDialog {
        display: none;
    }

    .message-container {
        display: flex;
        margin-bottom: 10px;
    }

    .chat-message {
        display: inline-block;
        border-radius: 15px;
        padding: 10px;
        margin: 5px;
        max-width: 60%;
        word-wrap: break-word;
        color: white;
        font-weight: 200;
    }

    .message-container.left {
        justify-content: flex-start;
    }

    .message-container.right {
        justify-content: flex-end;
    }

    .chat-message.left {
        background-color: #646463;
        color: white;
    }

    .chat-message.right {
        background-color: #00913f;
        color: white;
    }

    .icon-with-number {
        position: relative;
        display: inline-block;
    }

    .icon-with-number .icon-number {
        position: absolute;
        top: 45%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: white;
        font-size: 16px;
        font-weight: bold;
    }

    .borda-inferior {
        border: none;
        border-bottom: 1px solid #000;
        padding: 5px;
        outline: none;
        width: 100%;
        font-size: 16px;
        color: #999;
    }

    .borda-inferior:focus {
        border-bottom-color: #00913f;
        background-color: white;
        color: #000;
    }

    .borda-inferior.disabled {
        pointer-events: none;
        color: #aaa;
    }

    .progress {
        position: relative;
    }

    .progress-bar {
        height: 30px;
    }

    .icon {
        position: absolute;
        top: 17px;
        color: black;
    }

    .progress {
        height: 15px;
    }

    .progress-bar {
        height: 15px;
        position: relative;
    }

    .progress-bar i {
        position: absolute;
        right: 2px;
        top: 30%;
        transform: translateY(-50%);
        margin-top: 2.5px;
        font-size: 15px;
        color: black;
    }
</style>

<body>
    <div class="container-fluid">
        <br>
        <div class="col-12 text-center">
            <?= sprintf('<h3 id="legenda">Lista de Conversas do Chatbot</h3><br>', ''); ?>
        </div>

        <div class="modal fade" id="ProgStatus" tabindex="-1" aria-labelledby="modalprogress" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header" style="border-bottom: 0px; text-align: center; width: 100%;">
                        <div style="flex: 1;">
                            <h5 class="modal-title" id="modalprogress">Status de conversa do cliente</h5>
                        </div>
                        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal" aria-label="Close">x</button>
                    </div>
                    <div class="modal-body" style="height: 100px; display: flex;">
                        <div class="container">
                            <div class="progress flex-grow-1 me-2">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" id="progressstatus" data-value="1" style="width: 7%; background-color: red; border-radius: 4px;" aria-valuenow="5" aria-valuemin="0" aria-valuemax="100">
                                    <i class="fas fa-hamburger"></i>
                                </div>
                            </div>

                            <div class="row justify-content-between" style="margin-top: -10px;">
                                <div class="col text-center">
                                    <i class="fas fa-headset icon" style="font-size: 16px;" title="Em atendimento"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-check icon" style="font-size: 16px;" title="Pedido confirmado"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-money-bill-wave icon" style="font-size: 16px;" title="Aguardando pagamento"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-check-circle icon" style="font-size: 16px;" title="Pedido pago"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-spinner fa-spin icon" style="font-size: 16px;" title="Pedido em produção"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-concierge-bell icon" style="font-size: 16px;" title="Pedido aguardando retirada"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-truck icon" style="font-size: 16px;" title="Pedido saiu para entrega"></i>
                                </div>
                                <div class="col text-center">
                                    <i class="fas fa-utensils icon" style="font-size: 16px;" title="Pedido entregue ao cliente"></i>
                                </div>
                            </div>
                        </div>
                        <div>
                            <button type="button" class="btn btn-success" id="Nextstatus"><i class="fas fa-arrow-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div id="mainColumn" class="col-sm-12">
                <table id="all_conversas" class="tabela_responsiva">
                    <thead>
                        <th>Nome Cliente</th>
                        <th>Contato</th>
                        <th>Status</th>
                        <th>Última interação</th>
                        <th>Setor</th>
                        <th>Colaborador</th>
                        <th>Mensagens</th>
                        <th>Agente Ativo</th>
                        <th>Opções</th>
                    </thead>
                    <tbody id="listatabela">
                    </tbody>
                </table>
            </div>
            <div id="caixaDialog" class="col-3 mb-2">
                <div class="dialogcomcliente">
                    <div class="card card-dialog">
                        <div class="card-header" style="text-align: center;"><b>Chat com o Cliente</b> <button type="button" class="btn btn-danger" style="float: right;" id="closeCard">x</button></div>
                        <div class="card-body">
                            <div class="lista-conversas-cliente">
                                <div id="mensagens"></div>
                                <div id="mascara" style="display: none;"></div>
                            </div>
                            <div id="assumiuconversa">
                                <div class="input-group" style="padding-top: 2px;">
                                    <textarea class="form-control" placeholder="Envie uma mensagem para o Cliente" style="resize: none;" id="mensagemenviacliente"></textarea>

                                    <div class="col" style="display: none;" id="mostraArq">
                                        <input id="arquivo" type="file" accept=".pdf" style="float: left; margin-top: 4px;">
                                        <button title="Deletar arquivo" class="btn btn-outline-danger btn-sm" id="delArquivo" style="float: right; border-color: white;">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                    <div class="col" style="display: none;" id="mostraImg">
                                        <input id="imagem" type="file" accept=".jpg, .jpeg, .png" style="float: left; margin-top: 4px;">
                                        <button title="Deletar Imagem" class="btn btn-outline-danger btn-sm" id="delImagem" style="float: right; border-color: white;">
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </div>

                                </div>
                                <div class="row" style="padding-top: 4px;">
                                    <div class="col">
                                        <button title="Enviar Arquivo" class="btn btn-dark" id="enviarArquivo">
                                            <i class="far fa-file" aria-hidden="true" style="font-size: 18px;"></i>
                                        </button>
                                        <button title="Enviar imagem" class="btn btn-dark" id="enviarImagem">
                                            <i class="far fa-images" aria-hidden="true" style="font-size: 18px;"></i>
                                        </button>
                                    </div>
                                    <div class="col">
                                        <button type="button" style="float: right;" class="btn btn-success" id="enviamensagemparacliente"><i class="fas fa-paper-plane" style="font-size: 18px;"></i></button>
                                    </div>
                                </div>
                            </div>
                            <div id="visualizaConversa" style="display: none;">
                                <button title="Assumir conversa" class="btn btn-warning" id="assumeconvposvisualizar">
                                    <i class="fas fa-hand-paper" aria-hidden="true"></i> Assumir Conversa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DIV DA MASCARA -->
            <div id="mascara" style="display: none;"></div>

            <div id="alertavel" class="modal modal-danger" role="alert">
                <div class="modal-dialog " role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><b style="color: red;">Alerta</b></h5>
                            <button class="close" type="button" aria-hidden="true" data-dismiss="modal"><span>×</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-body-alert"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="alertavelSucesso" class="modal modal-danger" role="alert">

                <div class="modal-dialog " role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><b style="color: green;">Sucesso</b></h5>
                            <button class="close" type="button" aria-hidden="true" data-dismiss="modal"><span>x</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-body-sucess"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>

            <div id="itens-agrupadores" class="modal modal-danger" role="alert">

                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><b>Itens do Agrupador</b></h5>
                            <button class="close" type="button" aria-hidden="true" data-dismiss="modal"><span>x</span></button>
                        </div>
                        <div class="modal-body">
                            <div id="modal-body-agrupador"></div>
                        </div>
                        <div class="modal-footer">
                            <button class="btn btn-secondary" type="button" data-dismiss="modal">OK</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


</body>

</html>