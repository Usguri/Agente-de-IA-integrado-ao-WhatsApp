const login = document.querySelector(".login");
const principal = document.querySelector(".principal")
const chat = document.querySelector(".chat")
const userList = document.getElementById('sidebar-chat-list');
const chatWindow = document.querySelector('.chat-window');
const loadingContainer = document.getElementById("loading-container")
const logincontainer = document.getElementById("login-container")
var token;
var usuario;

async function sessionuser(usuario, senha) {
  try {
    const response = await fetch('/verifica-login', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ usuario, senha }),
    });

    const data = await response.json();

    if (data.sucesso) {

      localStorage.setItem('token', data.dados[0]);
      localStorage.setItem('login', data.dados[1]);
      localStorage.setItem('usuario', data.dados[1]);

      console.log('Login bem-sucedido!');
      window.location.href = '/tela-inicial';
    } else {
      console.error('Erro no login:', data.mensagem);
    }
  } catch (error) {
    console.error('Erro ao fazer login:', error);
  }
}

document.getElementById('login-btn').addEventListener('click', () => {
  usuario = document.getElementById('usuario').value;
  const senha = document.getElementById('senha').value;
  sessionuser(usuario, senha)
});

function verificarEnter(event) {
  // Verifica se a tecla pressionada é "Enter"
  if (event.key === 'Enter') {
    usuario = document.getElementById('usuario').value;
    const senha = document.getElementById('senha').value;
    sessionuser(usuario, senha)
  }
}

window.onload = function () {

  const params = new URLSearchParams(window.location.search);
  const dadosOfuscados = params.get('data');

  if (!dadosOfuscados) {
    return false
  } else {
    loadingContainer.style.display = ''
    logincontainer.style.display = 'none'
    minhaFuncao(dadosOfuscados)
  }
}

async function minhaFuncao(dadosOfuscados) {

  const dadosDecodificados = JSON.parse(atob(dadosOfuscados));
  let userid = dadosDecodificados.userid
  let username = dadosDecodificados.username

  try {
    const response = await fetch('/verificalogincinco', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ userid, username }),
    });

    const data = await response.json();

    if (data.sucesso) {


      localStorage.setItem('token', data.dados);
      localStorage.setItem('login', userid);
      localStorage.setItem('usuario', username);

      console.log('Login bem-sucedido!');
      loadingContainer.style.display = 'none'
      window.location.href = '/tela-inicial';
    } else {
      console.error('Erro no login:', data.mensagem);
    }
  } catch (error) {
    console.error('Erro ao fazer login:', error);
  }
}