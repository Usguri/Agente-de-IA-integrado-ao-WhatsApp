const { parsePhoneNumberFromString } = libphonenumber;
const userList = document.getElementById('sidebar-chat-list');
let listausuario = []
var iconePesquisa = document.getElementById('icone-pesquisa');
var iconeArrowLeft = document.getElementById('icone-arrow-left');
var iconeMais = document.getElementById('id-mais');
var iconeX = document.getElementById('id-x');
var inputmsgtxt = document.getElementById('inputmsgtxt');
var inputimagem = document.getElementById('inputimagem');
var inputarquivo = document.getElementById('inputarquivo')
var sendmsg = document.getElementById('sendmensagem')
var loadingsend = document.getElementById('loadingsend')
const chat = document.querySelector(".chat")
const nomecontato = document.querySelector(".header__text")
const chatWindow = document.querySelector('.chat-window');
const Windowinicial = document.querySelector('.informacoes');
const chatMessages = chat.querySelector(".chat__messages")
const siscinco = `urlcinco`
const chatcontainer = document.querySelector(".chat-container")
const ws = new WebSocket("ws://localhost:5001");
let idcontcliente = null
let matriculaclb = null
let numerocliente = null
let nomedepartamento = null
let numagt = null
const usuario = localStorage.getItem('usuario')
const login = localStorage.getItem('login')
let nomearquivoupload = null
let clbassumeconversa = document.getElementById("clbassumeconversa")
let IAassumeconversa = document.getElementById("IAassumeconversa")
let armazenadadosUsuario = null
let getIdCliente = null
let idagente = null


ws.onmessage = function (event) {

    try {

        var dados = JSON.parse(event.data)
        if (dados.id) {
            var idcliente = dados.id

            var timestamp = new Date(dados.datahora * 1000)
            var hours = timestamp.getHours() < 10 ? '0' + timestamp.getHours() : timestamp.getHours()
            var minutes = timestamp.getMinutes() < 10 ? "0" + timestamp.getMinutes() : timestamp.getMinutes()
            var horario = hours + ':' + minutes

            if (idcontcliente == idcliente) {

                UpdateNumMsg(idcliente)
                Carregarlista()

                var newmsgcliente = createMessageOtherElement(dados, horario)
                chatMessages.appendChild(newmsgcliente)
                scrollScreen()

            } else {
                Carregarlista()
            }
        } else {
            Carregarlista()
        }


    } catch (e) {
        console.error('Error parsing JSON:', e);
    }
}


async function Carregarlista() {
    const buscalista = `${siscinco}temp_buscadadostabelafront?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&userid=${usuario}`;

    const response = await fetch(buscalista)
    if (response.ok) {
        const data = await response.json();
        if (data) {
            sobrelistacontatos(data)
        }
        else {
            console.log('favor fale com o dev')
        }
    } else {
        console.error('Erro ao buscar dados:', response.statusText);
    }
}

function sobrelistacontatos(data) {
    var loadingContainer = document.getElementById("loading-container")

    if (userList) {
        userList.innerHTML = ''
        listausuario = data

        // Adiciona os dados à lista
        data.forEach(conversa => {
            const listItem = document.createElement('li')
            listItem.innerHTML = desenhalistacontato(conversa)
            userList.appendChild(listItem)
        })
        loadingContainer.style.display = "none"
    } else {
        console.error('Elemento com ID "sidebar-chat-list" não encontrado.')
    }
}

function desenhalistacontato(conversa) {

    const classcolor = conversa.mensagens > 0 ? 'clmsg' : ''
    const iconeuser = conversa.mensagens > 0 ? '<i class="fas fa-user fa-beat-fade" style="font-size:25px;"></i>' : '<i class="fas fa-user" style="font-size:25px;"></i>'
    const clbconv = conversa.usuario != null ? conversa.usuario : null
    const contatocli = parsePhoneNumberFromString(conversa.contato, 'BR');
    const mostranomecli = conversa.nomecli ? conversa.nomecli : contatocli.formatNational()

    if (clbconv == null) {
        clbassumeconversa.style.display = "block"
        IAassumeconversa.style.display = "none"
    } else {
        clbassumeconversa.style.display = "none"
        IAassumeconversa.style.display = "block"
    }

    return `<div class="cotato" name="${contatocli.formatNational()}" onclick="BuscaMensagens('${conversa.agente}', '${conversa.idcontato}', '${mostranomecli}', '${conversa.contato}', '${clbconv}', '${conversa.numeroagente}', '${conversa.idagente}')">
                 ${iconeuser} ${mostranomecli}                
                <br>
                <b class="setor">${AjustarDataHora(conversa.datahora)}</b>
            </div>
            <div class="${classcolor}">
                ${conversa.mensagens > 0 ? conversa.mensagens : ''}
            </div>
            
            <div style="${clbconv != null ? 'display:none;' : ''}" id="contatoCliente${conversa.idcontato}">
                <i class="fas fa-robot" style="font-size: 20px; color: #0d6efd;"></i>
            </div>
            `
}

async function BuscaMensagens(dep, idcontato, numformatado, numero, clbusuario, numagente, idagt) {

    UpdateNumMsg(idcontato)
    const elements = document.querySelectorAll('.coluna-tm-10');

    if (clbusuario != null) {
        clbassumeconversa.style.display = "block"
        IAassumeconversa.style.display = "none"
    }

    // Itera sobre os elementos e redefine o atributo 'style'
    elements.forEach(element => {
        element.removeAttribute('style');
    });

    Windowinicial.remove();
    chatcontainer.style.removeProperty('display');
    nomecontato.textContent = numformatado

    Carregarlista()

    try {

        const urlsalvaarqjson = `${siscinco}temp_salvaConvBancoJson?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&id=${idcontato}&dep=${dep} `;

        async function buscarmensagens() {
            try {
                let data = '';

                const response = await fetch(urlsalvaarqjson, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    }
                });

                if (!response.ok) {
                    throw new Error(`Erro HTTP ${response.status}: ${response.statusText} `);
                }

                const responseText = await response.text();
                const retorno = JSON.parse(responseText)

                if (retorno.erro == 3) {
                    alert(retorno.mensagem)
                    return false
                }


                const buscaConversas = `${siscinco}temp_buscadadosconversa?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&id=${idcontato} `;
                const retornomsg = await fetch(buscaConversas);


                if (!retornomsg.ok) {
                    throw new Error(`Erro HTTP ${retornomsg.status}: ${retornomsg.statusText} `);
                }

                data = await retornomsg.json();
                const historico = JSON.parse(data[0].HISTORICOMENSAGEM);
                const cliente = historico.cliente;
                const colaborador = historico.colaborador;
                const combinado = cliente.concat(colaborador);

                // Ordenar o array combinado pelo campo 'contador'
                combinado.sort((a, b) => a.contador - b.contador);
                idcontcliente = idcontato
                matriculaclb = clbusuario
                numerocliente = numero
                nomedepartamento = dep
                numagt = numagente
                armazenadadosUsuario = [idcontato, dep, numero]
                getIdCliente = document.getElementById('contatoCliente' + idcontato)
                idagente = idagt

                mensagenstrocadas(combinado)

            } catch (error) {
                console.error('Erro ao buscar dados:', error);
            }
        }

        await buscarmensagens();
    } catch (error) {
        console.error('Erro ao buscar dados da conversa:', error);
    }
}

function AjustarDataHora(datahora) {

    let date = parseDateTime(datahora)

    const optionsDate = { day: '2-digit', month: '2-digit', year: 'numeric' };
    const optionsTime = { hour: '2-digit', minute: '2-digit' }; // Ajustado para mostrar apenas HH:MM

    const today = new Date();
    today.setHours(0, 0, 0, 0); // Começar o dia às 00:00:00

    const diffTime = Math.abs(date - today);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));

    let formattedDate = date.toLocaleDateString('pt-BR', optionsDate); // Format: dd/mm/yyyy
    let formattedTime = date.toLocaleTimeString('pt-BR', optionsTime); // Format: HH:MM

    if (date.toDateString() === today.toDateString()) {
        // Se a data for hoje
        if (date.getHours() < 0) {
            // Antes das 00:00
            const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            const dayName = dayNames[date.getDay()];
            return `${dayName} `;
        } else {
            // Após as 00:00
            return `${formattedTime} `;
        }
    } else if (diffDays < 7) {
        // Data não ultrapassa 1 semana
        const dayNames = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
        const dayName = dayNames[date.getDay()];
        return `${dayName} - ${formattedTime} `;
    } else {
        // Data ultrapassa 1 semana
        return `${formattedDate} - ${formattedTime} `;
    }
}

function parseDateTime(dateTimeString) {

    const [datePart, timePart] = dateTimeString.split(' ');
    const [day, month, year] = datePart.split('/').map(num => parseInt(num, 10));
    const [hour, minute, second] = timePart.split(':').map(num => parseInt(num, 10));
    return new Date(year, month - 1, day, hour, minute, second);
}

function escolhecontato(contato) {

    if (iconePesquisa.style.display != 'none') {
        iconePesquisa.style.display = 'none';
        iconeArrowLeft.style.display = 'inline';
    }

    if (contato == '') {
        LimparPesquisa()
    } else {
        escolherContatoPesquisa(contato)
    }
}

function LimparPesquisa() {

    const contato = document.getElementById('contato');
    contato.value = ''

    iconePesquisa.style.display = 'inline';
    iconeArrowLeft.style.display = 'none';

    userList.innerHTML = '';
    listausuario.forEach(conversa => {
        const listItem = document.createElement('li');
        listItem.innerHTML = desenhalistacontato(conversa)
        userList.appendChild(listItem);
    });
}

function escolherContatoPesquisa(contato) {
    userList.innerHTML = '';
    const contatoLower = contato.toLowerCase();

    listausuario.forEach(conversa => {
        const contatoItemLower = conversa.contato.toLowerCase();

        if (contatoLower && contatoItemLower.includes(contatoLower)) {
            const listItem = document.createElement('li');
            listItem.innerHTML = desenhalistacontato(conversa)
            userList.appendChild(listItem);
        }
    });
}

//cria mensagem do usuario
const createMessageSelfElement = (mensagem, horario) => {

    const divPrincipal = document.createElement("div")
    const divTexto = document.createElement("div")
    const divHorario = document.createElement("div")

    divPrincipal.classList.add("message--self")
    divTexto.classList.add("message-text")
    divHorario.classList.add("message-time")

    divTexto.textContent = mensagem
    divHorario.textContent = horario

    divPrincipal.appendChild(divTexto)
    divPrincipal.appendChild(divHorario)

    return divPrincipal
}

//cria mensagem de outros 
const createMessageOtherElement = (dados, horario) => {

    if (dados.tipomsg == 3) {

        var nmarq = null
        var antesDoUnderline = null
        if (dados.nomearq != null) {
            nmarq = dados.nomearq.split('_')
            antesDoUnderline = nmarq[1]
        }

        const arquivo = desenharArq(horario, dados.contador, antesDoUnderline)

        return arquivo


    } else if (dados.tipomsg == 2) {

        var nmarq = null
        var antesDoUnderline = null
        if (dados.nomearq != null) {
            nomedoarq = dados.nomearq.split('_')
            antesDoUnderline = nomedoarq[0]
        }

        const arquivo = desenharArq(horario, dados.contador, antesDoUnderline)
        return arquivo
    }
    else {
        const divPrincipal = document.createElement("div")
        const divTexto = document.createElement("div")
        const divHorario = document.createElement("div")

        divPrincipal.classList.add("message--other")
        divTexto.classList.add("message-text")
        divHorario.classList.add("message-time")

        divTexto.textContent = dados.mensagem
        divHorario.textContent = horario

        divPrincipal.appendChild(divTexto)
        divPrincipal.appendChild(divHorario)

        return divPrincipal
    }
}

const scrollScreen = () => {
    chatMessages.scrollTo({
        top: chatMessages.scrollHeight,
        behavior: "smooth"
    });
};

function redirecionaTelaLogin(usuario) {
    if (!usuario) {
        console.log('Erro ao logar:', usuario);
        window.location.href = '/';
    } else {
        ws.onopen = () => {
            Carregarlista()
            console.log("Conectado ao WebSocket - Chat Cotribá");
        };
    }
}

function mensagenstrocadas(mensagens) {
    chatMessages.innerHTML = '';
    mensagens.forEach(msg => {
        var horario = msg.datahora ? msg.datahora.split(' ')[1] : msg.data.split(' ')[1]
        if (!msg.usuario) {
            message = createMessageOtherElement(msg, horario.slice(0, 5))
        } else {
            message = createMessageSelfElement(msg.mensagem, horario.slice(0, 5))
        }
        chatMessages.appendChild(message)
        scrollScreen()
    });
}

document.getElementById('inputmsgtxt').addEventListener('keydown', function (event) {
    if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault()
        clbenviamsg()
    }
})

async function clbenviamsg() {

    var msg = document.getElementById('inputmsgtxt')
    sendmsg.style.display = 'none'
    loadingsend.style.display = 'block'

    if (inputimagem.value != '') {
        const urluploadarq = `${siscinco}temp_enviamsgimagem?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

        const form = {
            mensagem: nomearquivoupload,
            numero: numerocliente,
            tipomsg: 2,
            usuario: usuario,
            dep: nomedepartamento,
            numagt: numagt,
            idagente: idagente
        };

        fetch(urluploadarq, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(form),
        }).then(response => response.json()).then(data => {
            if (data.erro != 2) {
                console.log(data.mensagem)
                alert(data.mensagem)
            } else {
                carreganewmsg(nomearquivoupload)
                nomearquivoupload = null
                sendmsg.style.display = 'block'
                loadingsend.style.display = 'none'
            }
            inputimagem.disabled = false
            inputimagem.style.display = 'none'
        }).catch(error => {
            console.error('Erro ao fazer upload:', error);
        })

    }
    else if (inputarquivo.value != '') {
        const urluploadarq = `${siscinco}temp_enviamsgarquivopdf?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`
        const form = {
            mensagem: nomearquivoupload,
            numero: numerocliente,
            tipomsg: 3,
            usuario: usuario,
            dep: nomedepartamento,
            numagt: numagt,
            idagente: idagente
        };

        fetch(urluploadarq, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(form),
        }).then(response => response.json()).then(data => {
            if (data.erro != 2) {
                console.log(data.mensagem)
                alert(data.mensagem)
            } else {
                carreganewmsg(nomearquivoupload)
                nomearquivoupload = null
                sendmsg.style.display = 'block'
                loadingsend.style.display = 'none'
            }

            inputarquivo.disabled = false
            inputarquivo.style.display = 'none'
        }).catch(error => {
            console.error('Erro ao fazer upload:', error);
        });

    }
    else {
        const urlsalvaarqjson = `${siscinco}temp_enviamensagemzap?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

        const form = {
            mensagem: msg.value,
            numero: numerocliente,
            tipomsg: 0,
            usuario: usuario,
            dep: nomedepartamento,
            numagt: numagt,
            idagente: idagente
        }

        fetch(urlsalvaarqjson, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(form),
        }).then(response => response.json()).then(data => {
            if (data.erro != 2) {
                console.log(data.mensagem)
                alert(data.mensagem)
            } else {
                carreganewmsg(msg.value)
                msg.value = ''
                sendmsg.style.display = 'block'
                loadingsend.style.display = 'none'
            }
        }).catch(error => {
            console.error('Erro ao fazer upload:', error);
        })
    }

    function carreganewmsg(msgenv) {
        const now = new Date();
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const seconds = String(now.getSeconds()).padStart(2, '0');
        const dateajust = `${hours}:${minutes}:${seconds} `

        ajustarinputs()
        var addmsg = createMessageSelfElement(msgenv, dateajust.slice(0, 5))
        chatMessages.appendChild(addmsg)
        scrollScreen()
    }

}

function sairdochat() {
    let usuario = localStorage.getItem('usuario');
    localStorage.removeItem(usuario);
    localStorage.clear();
    ws.onclose = () => {
        console.log("Conexão WebSocket - Chat Cotribá encerrada");
    };
    window.location.href = '/'
}

function desenharArq(horario, contator, nomearq) {
    const divPrincipal = document.createElement("div");
    const divTextButton = document.createElement("button");
    const divHorario = document.createElement("div");
    const buttonIcon = document.createElement("i");

    divPrincipal.classList.add("message--other");
    divTextButton.classList.add("message-text");
    divHorario.classList.add("message-time");

    // Adicione um ícone de download        
    buttonIcon.classList.add("fa-solid", "fa-circle-down");
    buttonIcon.style.margin = "5px";
    buttonIcon.style.fontSize = "xx-large";
    buttonIcon.style.color = "green";


    divTextButton.appendChild(document.createTextNode(nomearq))
    divTextButton.appendChild(buttonIcon)

    divTextButton.style.backgroundColor = "rgba(0,0,0,0)";
    divTextButton.style.color = "white";
    divTextButton.style.border = "none";
    divTextButton.style.padding = "5px 10px";
    divTextButton.style.borderRadius = "5px";
    divTextButton.style.cursor = "pointer";
    divTextButton.style.display = "flex";
    divTextButton.style.alignItems = "center";
    divTextButton.style.justifyContent = "center";
    divTextButton.style.gap = "5px";

    divTextButton.addEventListener("click", function (event) {
        event.preventDefault();
        baixararquivo(contator);
    });

    divHorario.textContent = horario;

    divPrincipal.appendChild(divTextButton);
    divPrincipal.appendChild(divHorario);

    return divPrincipal
}

async function UpdateNumMsg(idcliente) {
    try {
        const urlUpdate = siscinco + `temp_atualizamensagenscliente?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&idcliente=${idcliente} `;

        async function atualizarNumeroMensagens() {

            const response = await fetch(urlUpdate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            });

            if (!response.ok) {
                throw new Error(`Erro HTTP ${response.status}: ${response.statusText} `);
            }
        }
        await atualizarNumeroMensagens();
    } catch (error) {
        console.error('Erro ao buscar dados da conversa:', error);
    }
}

async function baixararquivo(contator) {

    try {
        const urlbuscaarq = siscinco + `temp_baixararquivo?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&contator=${contator}&idcontato=${idcontcliente} `;
        const urlbuscanomearq = siscinco + `temp_nomearquivo?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60&contator=${contator}&idcontato=${idcontcliente} `;

        async function buscararquivo() {

            const respnomearq = await fetch(urlbuscanomearq)
            if (!respnomearq.ok) {
                throw new Error(`Erro HTTP ${respnomearq.status}: ${respnomearq.statusText} `)
            }

            let nomearq = await respnomearq.text()

            const respblobarq = await fetch(urlbuscaarq)
            if (!respblobarq.ok) {
                throw new Error(`Erro HTTP ${respblobarq.status}: ${respblobarq.statusText} `)
            }

            let blobarq = await respblobarq.blob()

            const url = window.URL.createObjectURL(blobarq);
            const a = document.createElement('a');
            a.href = url;
            a.download = nomearq;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
        await buscararquivo();
    } catch (error) {
        console.error('Erro ao buscar dados da conversa:', error);
    }
}

function ChamaArquivo(tpfile) {

    if (tpfile == 'Documento') {
        inputarquivo.click();
    } else if (tpfile == 'Foto') {
        inputimagem.click();
    }
}

inputimagem.addEventListener('change', function (event) {
    const input = event.target;
    if (input.files && input.files.length > 0) {
        salvarArquivo(input, 1)
    }
})

inputarquivo.addEventListener('change', function (event) {
    const input = event.target;
    if (input.files && input.files.length > 0) {
        salvarArquivo(input, 2)
    }
})

async function salvarArquivo(arquivo, num) {

    nomearquivoupload = arquivo.files[0].name
    var url = ''

    var formData = new FormData();
    formData.append('file', arquivo.files[0]);

    if (num == 1) {
        url = 'temp_uploadimgem'
        inputmsgtxt.style.display = 'none'
        inputarquivo.style.display = 'none'
        inputimagem.style.display = 'inline'
        inputimagem.disabled = true
    } else if (num == 2) {
        url = 'temp_uploadarquivopdf'
        inputmsgtxt.style.display = 'none'
        inputimagem.style.display = 'none'
        inputarquivo.style.display = 'inline'
        inputarquivo.disabled = true
    }

    mostraArquivo.style.display = 'inline'
    document.getElementById('button-escolhe-arquivo').disabled = true
    var urluploadarq = `${siscinco}${url}?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

    fetch(urluploadarq, {
        method: 'POST',
        body: formData,
    }).then(response => response.json()).then(data => {
        if (data.erro != 0) {
            console.log(data.mensagem)
            alert(data.mensagem)
        }
    }).catch(error => {
        console.error('Erro ao fazer upload:', error);
    })
}

function Deletararquivo() {

    var url = ''

    if (inputimagem.value != '') {
        url = 'temp_deleteimgem'
        inputimagem.disabled = false
    } else if (inputarquivo.value != '') {
        url = 'temp_deletearquivopdf'
        inputarquivo.disabled = false
    }


    ajustarinputs()
    const urldeletararq = `${siscinco}${url}?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

    var form = {
        nomearquivo: nomearquivoupload
    }

    fetch(urldeletararq, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(form),
    }).then(response => response.json()).then(data => {
        if (data.erro != 0) {
            console.log(data.mensagem)
            alert(data.mensagem)
        }
    }).catch(error => {
        console.error('Erro ao fazer upload:', error);
    })

    nomearquivoupload = null
}

function ajustarinputs() {
    inputimagem.value = ''
    inputarquivo.value = ''
    mostraArquivo.style.display = 'none'
    inputmsgtxt.style.display = 'inline'
    document.getElementById('button-escolhe-arquivo').disabled = false
}

function AssumirConverComCliente() {
    const url = `${siscinco}temp_atualizausuarioassumiuconversa?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

    var form = {
        id: armazenadadosUsuario[0],
        contato: armazenadadosUsuario[1],
        dep: armazenadadosUsuario[1],
        usuario: usuario
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(form),
    }).then(response => {
        if (!response.ok || response.status === 204) {
            throw new Error('Resposta sem conteúdo ou erro na requisição');
        }
        return response.json();
    }).then(data => {
        if (data.dados.erro == 0) {
            clbassumeconversa.style.display = "none"
            IAassumeconversa.style.display = "block"
            getIdCliente.style.display = 'none'
            ws.send(JSON.stringify(data))
        } else {
            console.log(data.dados.mensagem)
        }
    }).catch(error => {
        console.error('Erro ao fazer upload:', error);
    })

}

function IAAssumeConversa() {
    const url = `${siscinco}conversa_assumida_pela_ia?token_acesso_cinco=ed9bc03782867abdd2836c91012d1d60`

    var form = {
        idcontato: armazenadadosUsuario[0],
        contato: armazenadadosUsuario[1],
        dep: armazenadadosUsuario[1],
        usuario: usuario
    }

    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(form),
    }).then(response => {
        if (!response.ok || response.status === 204) {
            throw new Error('Resposta sem conteúdo ou erro na requisição');
        }
        return response.json();
    }).then(data => {
        if (data.dados.erro == 0) {
            clbassumeconversa.style.display = "block"
            IAassumeconversa.style.display = "none"
            getIdCliente.style.display = 'block'
            ws.send(JSON.stringify(data))

        } else {
            console.log(data.dados.mensagem)
        }
    }).catch(error => {
        console.error('Erro ao fazer upload:', error);
    })
}

redirecionaTelaLogin(usuario)