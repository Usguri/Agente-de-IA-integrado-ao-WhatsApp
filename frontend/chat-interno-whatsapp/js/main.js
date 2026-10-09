
$(document).ready(function () {

    let cliente = null
    let dadoscliente = null
    let nomeimagemupload = null
    let nomeipdfupload = null
    let userIdClb = null
    let formudastatus = null



    var table = $("#all_conversas").DataTable({
        "pageLength": 9,
        lengthChange: false,
        "ordering": false,
        "autoWidth": false,
        "columnDefs": [
            {
                "targets": "_all",
                "className": "dt-head-center dt-body-center",
                "createdCell": function (td) {
                    $(td).css('padding', '2px'); // Ajuste o valor conforme necessário
                }
            }
        ]
    })

    function OpenCardConversation() {


        if (!$('#caixaDialog').is(':visible')) {
            $('#caixaDialog').toggle();
            $('#mainColumn').removeClass('col-sm-12').addClass('col-sm-9');
        }

        $('#closeCard').on('click', function () {
            $('#caixaDialog').css('display', 'none');
            $('#mainColumn').removeClass('col-sm-9').addClass('col-sm-12');
            cliente = null
        });

        $('#imagem').val('');
        $('#arquivo').val('');
        $('#mensagemenviacliente').val('');

        // Garante que o evento click seja associado apenas uma vez
        $('#enviamensagemparacliente').off('click').on('click', function () {

            var msgenviadacliente = $('#mensagemenviacliente').val().replace(/(\r\n|\n|\r)/g, ' ');

            var urlsend = 'temp_enviamensagemzap'
            var tipomsgenv = 0

            if (nomeimagemupload != null) {

                urlsend = 'temp_enviamsgimagem'
                msgenviadacliente = nomeimagemupload
                tipomsgenv = 2
                $('#mostraImg').hide();
            } else if (nomeipdfupload != null) {

                urlsend = 'temp_enviamsgarquivopdf'
                msgenviadacliente = nomeipdfupload
                tipomsgenv = 3
                $('#mostraArq').hide();
            } else {

                if (msgenviadacliente == '') {
                    showPopup('Alerta', 'Você não deve enviar mensagem em branco!', 'error');
                    return false;
                }
            }

            let mensagem = {
                mensagem: msgenviadacliente,
                numero: cliente[0],
                tipomsg: tipomsgenv,
                dep: cliente[1]
            }

            // Desabilita o botão enquanto a requisição AJAX está em andamento
            $('#enviamensagemparacliente').prop('disabled', true);
            $('#mensagemenviacliente').val('');
            $('#mensagemenviacliente').show()


            $.ajax({
                type: 'POST',
                url: urlsend,
                data: mensagem,
                success: function (resp) {

                    if (JSON.parse(resp).erro != 2) {
                        showPopup('Alerta', JSON.parse(resp).mensagem, 'error');
                        console.log(JSON.parse(resp));
                    } else {
                        novaMensagemHtml = '<div class="message-container right"><div class="chat-message right">' + msgenviadacliente + '</div></div>';
                        $('#mensagens').append(novaMensagemHtml);
                        $('#mensagens').scrollTop(0);
                    }
                    // Reativa o botão após o sucesso da requisição
                    $('#enviamensagemparacliente').prop('disabled', false);
                },
                error: function (resp) {
                    console.log(resp);
                    // Reativa o botão mesmo se ocorrer um erro
                    $('#enviamensagemparacliente').prop('disabled', false);
                }
            });
        });

        // Função para verificar teclas pressionadas
        document.addEventListener('keydown', function (event) {
            var activeElement = document.activeElement;
            if (activeElement.tagName.toLowerCase() === 'textarea') {
                // Verifica se a combinação Shift + Enter foi pressionada
                if (event.key === 'Enter' && event.shiftKey) {
                    event.preventDefault(); // Previne o comportamento padrão
                    var textarea = activeElement;
                    var cursorPos = textarea.selectionStart;
                    var textBefore = textarea.value.substring(0, cursorPos);
                    var textAfter = textarea.value.substring(cursorPos);
                    textarea.value = textBefore + '\n' + textAfter;
                    textarea.selectionStart = textarea.selectionEnd = cursorPos + 1;
                } else if (event.key === 'Enter') {  // Verifica se a tecla pressionada é Enter
                    event.preventDefault();
                    $('#enviamensagemparacliente').click(); // Simula o clique no botão
                }
            }
        });

    }

    async function buscarDadosConversa(id, valida = true) {
        try {

            var Url = './buscadadosconversa'

            if (!valida) {
                Url = './buscamensagens'
            }

            // Esperar a resposta da requisição AJAX
            const resp = await $.get(Url, { id });

            // Parsear o JSON da resposta
            const data = JSON.parse(resp);
            const historico = JSON.parse(data[0].HISTORICOMENSAGEM);
            const cliente = historico.cliente;
            const colaborador = historico.colaborador;

            // Combinar os dois arrays
            const combinado = cliente.concat(colaborador);

            // Ordenar o array combinado pelo campo 'contador'
            combinado.sort((a, b) => a.contador - b.contador);

            // Chamar a função que vai lidar com os dados
            historicomsg(combinado);
        } catch (error) {
            console.error("Erro ao buscar dados:", error);
        }
    }

    function historicomsg(array) {

        // APOS ENVIAR MENSAGEM DE QUEM IRÁ ASSUMIR A CONVERSA, DEVE REALIZAR UMA ATUALIZAÇÃO NO BANCO DE DADOS COM A NOVA MENSAGEM QUE DEVE SER MOSTRADA EM TELA

        $('#mascara').show();

        var html = '';
        for (var i = 0; i < array.length; i++) {
            if (!array[i].usuario) {
                html += '<div class="message-container left"><div class="chat-message left">' + (array[i].mensagem) + '</div></div>';
            } else {
                html += '<div class="message-container right"><div class="chat-message right">' + (array[i].mensagem) + '</div></div>';
            }
        }

        $('#mensagens').empty()
        $('#mensagens').append(html)
        $('#mascara').hide();

    }

    function assumeconversacomcliente(valores) {

        var id = valores[0].trim();
        var idxContato = valores[1].trim();
        var usuario = valores[2].trim();
        var keyredis = ''

        if (valores[3].trim() == 'Mercado') {
            keyredis = 'climercado:'
        } else if (valores[3].trim() == 'Praça') {
            keyredis = 'clipraca:'
        }

        cliente = [idxContato, keyredis]
        nomeipdfupload = null
        nomeimagemupload = null

        // SALVAR CONVERSA DO ARQUIVO JSON NO BANCO DE DADOS \\

        var form = {
            id: id,
            contato: idxContato,
            dep: keyredis
        }
        salvaConversasComCliente(form, true, usuario)
    }

    function informaclientequemvaiassumir(form) {

        form['contato'] = form['contato'].slice(0, 4) + form['contato'].slice(5)

        $.ajax({
            type: 'POST',
            url: 'temp_atualizausuarioassumiuconversa',
            data: form,
            success: function (resp) {
                var msg = JSON.parse(resp)

                if (msg.dados.erro != 0) {
                    console.log(msg.dados)
                    showPopup('Alerta', msg.dados.mensagem, 'error');
                }
            },
            error: function (resp) {
                console.log(resp)
            }
        })
    }

    function salvaConversasComCliente(form, valida, usuario = null) {

        $.ajax({
            type: 'POST',
            url: 'salvaConvBancoJson',
            data: form,
            success: function (resp) {
                var retornocall = JSON.parse(resp)

                if (retornocall.erro == 3) {
                    showPopup('Alerta', retornocall.mensagem, 'error')
                    console.log(retornocall.mensagem)
                } else {
                    if (valida && !usuario) {
                        informaclientequemvaiassumir(form)
                        showPopup('Alerta', 'Você assumiu a conversa', 'success')
                    }

                    buscarDadosConversa(form.id, valida)
                    OpenCardConversation()
                    BuscaDadosTabela()
                }
            },
            error: function (resp) {
                console.log(resp)
            }
        })

    }



    // ------------------------------- BUTTONS CARREGA IMAGENS E ARQUIVOS IDS ------------------------------- \\

    $('#enviarArquivo').click(function () {
        $('#arquivo').click()
    })

    $('#arquivo').on('change', function () {
        var arquivo = $('#arquivo')[0]
        var arqupload = arquivo.files[0]

        if (!arqupload) {
            alert("Por favor, selecione um arquivo.");
            return;
        }

        nomeipdfupload = arqupload.name

        var formData = new FormData();
        formData.append('file', arqupload);

        $.ajax({
            url: 'temp_uploadarquivopdf',
            type: 'POST',
            data: formData,
            processData: false,  // Não processar dados
            contentType: false,  // Não definir o Content-Type
            success: function (data) {
                var retorno = JSON.parse(data)
                if (retorno.erro != 0) {
                    showPopup('Alerta', retorno.mensagem, 'error')
                }

            },
            error: function (xhr, status, error) {
                console.error('Erro:', error);
            }
        });


        $('#mensagemenviacliente').hide();
        $('#mostraArq').show();

    });

    $('#delArquivo').click(function () {

        $.ajax({
            url: 'deletearquivopdf',
            type: 'POST',
            data: { filename: nomeipdfupload },
            success: function (data) {

                var retorno = JSON.parse(data)
                if (retorno.erro != 0) {
                    showPopup('Alerta', retorno.mensagem, 'error')
                }
                nomeipdfupload = null
            },
            error: function (xhr, status, error) {
                console.error('Erro:', error);
            }
        });

        $('#arquivo').val('');
        $('#mostraArq').hide();
        $('#mensagemenviacliente').show();
    })

    $('#enviarImagem').click(function () {
        $('#imagem').click();
    })

    $('#imagem').on('change', function () {

        var dataimg = $('#imagem')[0];
        var imgupload = dataimg.files[0];

        if (!imgupload) {
            alert("Por favor, selecione um arquivo.");
            return;
        }

        nomeimagemupload = imgupload.name

        var formData = new FormData();
        formData.append('file', imgupload);

        $.ajax({
            url: 'temp_uploadimgem',
            type: 'POST',
            data: formData,
            processData: false,  // Não processar dados
            contentType: false,  // Não definir o Content-Type
            success: function (data) {
                var retorno = JSON.parse(data)
                if (retorno.erro != 0) {
                    showPopup('Alerta', retorno.mensagem, 'error')
                }
            },
            error: function (xhr, status, error) {
                console.error('Erro:', error);
            }
        });


        $('#mensagemenviacliente').hide();
        $('#mostraImg').show();
    })

    $('#delImagem').click(function () {

        $.ajax({
            url: 'temp_deleteimgem',
            type: 'POST',
            data: { filename: nomeimagemupload },
            success: function (data) {

                var retorno = JSON.parse(data)
                if (retorno.erro != 0) {
                    showPopup('Alerta', retorno.mensagem, 'error')
                }
                nomeimagemupload = null
            },
            error: function (xhr, status, error) {
                console.error('Erro:', error);
            }
        });

        $('#imagem').val('');
        $('#mostraImg').hide();
        $('#mensagemenviacliente').show();
    })

    // --------------------------------------------------------------------------------------------------------- \\



    $(document).on('click', '.visualizarConversa', function (e) {
        e.stopPropagation();
        let value = $(this).attr('data-id')
        var valores = value.split(",");

        var form = {
            id: valores[0].trim(),
            contato: valores[1].trim(),
            dep: valores[3].trim() == 'Mercado' ? 'climercado:' : 'clipraca:'
        }

        $('#visualizaConversa').show()
        $('#assumiuconversa').hide()

        salvaConversasComCliente(form, false)
        dadoscliente = valores
    })

    $('#assumeconvposvisualizar').click(function () {
        $('#visualizaConversa').hide()
        $('#assumiuconversa').show()
        assumeconversacomcliente(dadoscliente)
    })

    $(document).on('click', '.assumirConversa', function (e) {
        e.stopPropagation();

        let value = $(this).attr('data-id')
        var valores = value.split(",");

        if (!$('#assumiuconversa').is(':visible')) {
            $('#visualizaConversa').hide()
            $('#assumiuconversa').show()
        }

        assumeconversacomcliente(valores)
    })

    $(document).on('click', '.mudarstatus', function (e) {
        e.stopPropagation();
        var status = $(this).data('status');
        status = status.split(",");

        formudastatus = {
            status: parseInt(status[0].trim()),
            idcontato: parseInt(status[1].trim()),
            contato: parseInt(status[2].trim()),
            dep: status[3].trim() == 'Praça' ? 'clipraca:' : 'climercado:'
        }

        mudarstatusprogress(parseInt(status[0].trim()))
        $('#ProgStatus').modal('show')
    })

    $(document).on('click', '.inteligenciaassumeconversa', function (e) {
        e.stopPropagation();

        let value = $(this).attr('data-id')
        value = value.split(",");
        inteligenciaAssumeConversa(value[0].trim().slice(0, 4) + value[0].trim().slice(5), value[1].trim(), value[2].trim())
    })

    function inteligenciaAssumeConversa(contato, nomedep, idcontato) {

        var form = {
            idcontato: idcontato,
            contato: contato,
            dep: nomedep
        }

        $.ajax({
            type: 'POST',
            url: 'conversa_assumida_pela_ia',
            data: { form },
            success: function (resp) {
                var dados = JSON.parse(resp)
                if (dados.dados.erro != 0) {
                    showPopup('Alerta', 'Houve um erro', 'error')
                    console.log(dados)
                } else {

                    showPopup('Alerta', 'IA assume conversa', 'success')
                    BuscaDadosTabela()
                }
            },
            error: function (resp) {
                console.log(resp)
            }
        })
    }

    function BuscaDadosTabela() {
        table.clear();

        $.ajax({
            type: 'GET',
            url: 'buscadadostabelafront',
            data: {},
            success: function (resp) {
                JSON.parse(resp).forEach(function (item) {

                    var color = item.mensagens == 0 ? "#00913f" : "#FF8C00"
                    var ativacao = item.treinamento == 0 ? 'Ativo' : 'Desativado'

                    var row = '<tr>' +
                        '<td><div class="icon-with-number"><input type="text" class="borda-inferior input-nomecli" data-idcontato="' + item.idcontato + '" value="' + (item.nomecli ? item.nomecli : "") + '" maxlength="20"></div></td>' +
                        '<td>' + item.contato + '</td>' +
                        '<td>' + item.status + '</td>' +
                        '<td>' + item.dataHora + '</td>' +
                        '<td>' + item.setor + '</td>' +
                        '<td>' + item.usuario + '</td>' +
                        '<td><div class="icon-with-number"><i class="fas fa-comment  icon-with-number" style="color: ' + color + '; font-size: 30px;"><span class="icon-number">' + item.mensagens + '</span></i></div></td>' +
                        '<td>' + ativacao + '</td>' +
                        '<td>' +
                        item.buttons.abrirnovaguia + ' ' +
                        item.buttons.assumir + ' ' +
                        item.buttons.visualizar + ' ' +
                        item.buttons.roboassumeconversa + ' ' +
                        item.buttons.finalizarConversa + ' ' +
                        '</td>' +
                        '</tr>';
                    table.row.add($(row)).draw();
                });

                $('.input-nomecli').on('blur', function () {
                    var idcontato = $(this).data('idcontato');
                    var nomecli = $(this).val();
                    atualizarNomeCli(idcontato, nomecli);
                });

            },
            error: function (xhr, status, error) {
                console.log("Erro na requisição: ", status, error);
            }
        });
    }

    $(document).on('click', '.abrirNovaGuia', function (e) {
        e.stopPropagation();

        var dados = {
            userid: userIdClb.userId,
            username: userIdClb.username
        }

        const urlBase = 'http://172.16.0.3:5000'
        const dadosOfuscados = btoa(JSON.stringify(dados))
        const urlComParametros = `${urlBase}?data=${dadosOfuscados}`

        window.open(urlComParametros, 'popupWindow', 'width=1144,height=750,left=100,top=100,scrollbars=yes,resizable=no');
    })

    function atualizamensagens(id) {
        $.ajax({
            type: 'POST',
            url: 'atualizamensagenscliente',
            data: { id },
            success: function (resp) {
                BuscaDadosTabela()
                console.log(resp)
            },
            error: function (resp) {
                console.log(resp)
            }
        })
    }

    function BuscaLoginUsuario() {
        $.ajax({
            type: 'GET',
            url: 'buscaDadosUsuario',
            data: {},
            success: function (resp) {
                userIdClb = JSON.parse(resp)
            },
            error: function (xhr, status, error) {
                console.log("Erro na requisição: ", status, error);
            }
        });
    }

    function atualizarNomeCli(idcontato, nomecli) {

        const dados = {
            idcontato: idcontato,
            nome: nomecli
        }

        $.ajax({
            type: 'POST',
            url: 'updatenomecliente',
            data: { dados },
            success: function (resp) {
                var retorno = JSON.parse(resp).dados
                if (retorno.erro != 0) {
                    showPopup('Alerta', 'Houve um erro interno', 'error');
                } else {
                    showPopup('Alerta', 'Nome alterado', 'success')
                }
                BuscaDadosTabela()
            },
            error: function (resp) {
                console.log(resp)
            }
        })
    }

    $(document).on('click', '.finalizarConversa', function (e) {
        e.stopPropagation();
        let value = $(this).attr('data-id')
        FinalizarConversaComUsuario(value)
    })

    function FinalizarConversaComUsuario(contato) {
        $.ajax({
            type: 'POST',
            url: 'finalizarconversacomcliente',
            data: { contato },
            success: function (resp) {

                BuscaDadosTabela()
            },
            error: function (resp) {
                console.log(resp)
            }
        })
    }

    $('#Nextstatus').on('click', function () {

        let points = $('#progressstatus')
        var valuepoint = points.data('value')
        var recebenewpoint = null

        if (points.data('value') == 1) {
            points.css('width', '21%')
            points.data('value', 2)
            recebenewpoint = 2
        } else if (points.data('value') == 2) {
            points.css('width', '34.5%')
            points.data('value', 3)
            recebenewpoint = 3
        } else if (points.data('value') == 3) {
            points.css('width', '48%')
            points.data('value', 4)
            recebenewpoint = 4
        } else if (points.data('value') == 4) {
            points.css('width', '60.5%')
            points.data('value', 5)
            recebenewpoint = 5
        } else if (points.data('value') == 5) {
            points.css('width', '74.5%')
            points.data('value', 6)
            recebenewpoint = 6
        } else if (points.data('value') == 6) {
            points.css('width', '88%')
            points.data('value', 7)
            recebenewpoint = 7
        } else if (points.data('value') == 7) {
            points.css('width', '100%')
            points.data('value', 8)
            recebenewpoint = 8
        } else if (points.data('value') == 8) {
            points.css('width', '7%')
            points.data('value', 1)
            points.css('background-color', 'red')
        }

        if (valuepoint != 8) {
            const colorValue = Math.floor((valuepoint / 7) * 255)
            points.css('background-color', `rgb(${255 - colorValue}, ${colorValue}, 0)`)
            formudastatus['status'] = recebenewpoint
            enviamensagemmudarstatus(formudastatus)
        }

        function enviamensagemmudarstatus(formudastatus) {
            $.ajax({
                type: 'POST',
                url: 'alterarstatus',
                data: { formudastatus },
                success: function (resp) {

                    var orgdados = JSON.parse(resp)
                    if (orgdados.erro != 2) {
                        if (orgdados.erro == 3) {
                            showPopup('Alerta', orgdados.mensagem, 'error')
                        } else {
                            console.log('falar com o desenvolvedor')
                            showPopup('Alerta', 'Ocorreu um erro interno', 'error')
                        }
                    }
                    BuscaDadosTabela()
                    $('#ProgStatus').modal('hide')

                },
                error: function (resp) {
                    console.log(resp)
                }
            })
        }

    })

    function mudarstatusprogress(valuepoint) {

        let points = $('#progressstatus')

        if (valuepoint == 1) {
            points.css('width', '7%')
            points.data('value', valuepoint)
        } else if (valuepoint == 2) {
            points.css('width', '21%')
            points.data('value', valuepoint)
        } else if (valuepoint == 3) {
            points.css('width', '34.5%')
            points.data('value', valuepoint)
        } else if (valuepoint == 4) {
            points.css('width', '48%')
            points.data('value', valuepoint)
        } else if (valuepoint == 5) {
            points.css('width', '60.5%')
            points.data('value', valuepoint)
        } else if (valuepoint == 6) {
            points.css('width', '74.5%')
            points.data('value', valuepoint)
        } else if (valuepoint == 7) {
            points.css('width', '88%')
            points.data('value', valuepoint)
        } else if (valuepoint == 8) {
            points.css('width', '100%')
            points.data('value', valuepoint)
        }

        const colorValue = Math.floor((valuepoint / 7) * 255)
        points.css('background-color', `rgb(${255 - colorValue}, ${colorValue}, 0)`)

    }



    BuscaLoginUsuario();
    BuscaDadosTabela()
    // setInterval(atualizamensagens, 5000);




    // --------------------------------------------------------------------------------------------------------------------------------------------- \\

    const socket = new WebSocket('wss://ip/retornowebsocket/');

    // Evento quando a conexão é aberta
    socket.onopen = function () {
        console.log('Conectado ao WebSocket - Chat');
    };

    // Evento quando uma mensagem é recebida do servidor
    socket.onmessage = function (event) {
        if (cliente == null) {
            BuscaDadosTabela()
        }

        try {
            const data = JSON.parse(event.data);
            var numcli = data.numero
            var msgcli = data.mensagem
            var idcliente = data.id

            if (cliente) {

                var index = 4;
                var pos = cliente[0].indexOf('9', index);
                var numajustado = ''
                if (pos !== -1) {
                    numajustado = cliente[0].slice(0, pos) + cliente[0].slice(pos + 1);
                }

                if (numcli == numajustado) {
                    var novaMensagemHtml = '';
                    novaMensagemHtml = '<div class="message-container left"><div class="chat-message left">' + msgcli + '</div></div>';
                    $('#mensagens').append(novaMensagemHtml);
                    $('#mensagens').scrollTop($('#mensagens')[0].scrollHeight);
                }


                atualizamensagens(idcliente)
            }

        } catch (e) {
            console.error('Error parsing JSON:', e);
        }
    };

    // Evento quando a conexão é fechada
    socket.onclose = function () {
        console.log('Desconectado do WebSocket - Chat ');
    };

    // Evento quando ocorre um erro na conexão
    socket.onerror = function (error) {
        console.error('WebSocket Error:', error);
    };

    // --------------------------------------------------------------------------------------------------------------------------------------------- \\

})
