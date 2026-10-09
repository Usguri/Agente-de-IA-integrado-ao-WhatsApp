<?php
require '/var';
require './msg_whatsapp_versao2/load_dependencies.php';
require './src/functionsfront.php';

mostrar_erros();
$logado = iniciarSessao(false, true);
$token = verifica_token_acesso_cinco('token_acesso_cinco');

if ($logado) {
    $_USERID = $_SESSION['USER_ID'];
    $_USERNAME = $_SESSION['USERNAME'];
    $_USER_EMAIL = $_SESSION['USER_EMAIL'];
    session_write_close();
} else if ($token) {
    $_USERID = $_SESSION['USER_ID'];
    $_USERNAME = $_SESSION['USERNAME'];
    $_USER_EMAIL = $_SESSION['USER_EMAIL'];
} else {
    http_response_code(401);
    aviso('Erro ao detectar sessão do usuario, favor atualize o navegador!', 'Sessão terminada');
    exit;
}
session_write_close();




$app->get('/', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    require 'views/index.php';
});

$app->get('/buscaDadosUsuario', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = [
        'userId' => $_USERID,
        'username' => $_USERNAME
    ];
    return json_encode($dados);
});

$app->post('/atualizamensagenscliente', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $idcliente = $req->getQueryParams()['idcliente'] ?? null;

    if ($idcliente !== null) {
        return atualizarnummensagens($idcliente);
    } else {
        $dados = $req->getParsedBody();
        return atualizarnummensagens($dados['id']);
    }
});

$app->get('/buscadadostabelafront', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    return organizarTableConversa($_USERID);
});

$app->post('/updatenomecliente', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody()['dados'];
    return atualizarnomecliente($dados['idcontato'], $dados['nome']);
});

$app->post('/updatetransfcolaborador', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody()['dados'];
    $user = $dados['usuario'];
    $id = $dados['idcontato'];
    return transferircolaboradorconversa($id, $user);
});

$app->get('/buscadadosconversa', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $queryParams = $req->getQueryParams()["id"];
    atualizarnummensagens($queryParams);
    return CarregaChats($queryParams);
});

$app->get('/buscamensagens', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $queryParams = $req->getQueryParams()["id"];
    return CarregaChats($queryParams);
});

$app->post('/temp_salvaConvBancoJson', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {

    $dados = $req->getQueryParams();

    $idcliente = $dados['id'];
    $dep = $dados['dep'];
    $retornofront = SalvarDadosJsonEmBanco($idcliente, $dep);
    return json_encode($retornofront);
});

$app->post('/salvaConvBancoJson', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {

    $data = $req->getParsedBody();
    $retornofront = SalvarDadosJsonEmBanco($data['id'], $data['dep']);
    return json_encode($retornofront);
});

$app->post('/alterarstatus', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody()['formudastatus'];
    return alterarstatuscliente($dados['status'], $dados['idcontato'], $dados['contato'], $_USERID, $dados['dep']);
});

$app->post('/finalizarconversacomcliente', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    return finalizarConversa($req->getParsedBody());
});

$app->post('/enviamsgarquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody();
    return enviaarquivopdfmwhatsapp($dados);
});

$app->post('/conversa_assumida_pela_ia', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody();
    return inteligenciaAssumeConversa($dados['form']);
});

$app->get('/temp_buscadadosconversa', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {

    $queryParams = $req->getQueryParams()["id"];
    atualizarnummensagens($queryParams);
    return CarregaChats($queryParams);
});

$app->get('/temp_buscadadostabelafront', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    return organizarTableConversa();
});

$app->post('/temp_enviamensagemzap', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $queryParams = $req->getParsedBody();

    $msg = $queryParams['mensagem'];
    $numero = $queryParams['numero'];
    $usuario = $queryParams['usuario'] ?? $_USERID;
    $tipomsg = $queryParams['tipomsg'];
    $typedep = $queryParams['dep'];

    return funcaoenviomsgcliente($numero, $msg, $tipomsg, $usuario, $typedep);
});

$app->post('/temp_enviamsgarquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $parsedBody = $req->getParsedBody();

    $mensagem = $parsedBody['mensagem'];
    $numero = $parsedBody['numero'];
    $tipomsg = $parsedBody['tipomsg'];
    $usuario = $parsedBody['usuario'] ?? $_USERID;
    $typedep = $parsedBody['dep'];

    return funcaoenviomsgcliente($numero, $mensagem, $tipomsg, $usuario, $typedep);
});

$app->post('/temp_enviamsgimagem', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $parsedBody = $req->getParsedBody();

    $mensagem = $parsedBody['mensagem'];
    $numero = $parsedBody['numero'];
    $tipomsg = $parsedBody['tipomsg'];
    $usuario = $parsedBody['usuario'] ?? $_USERID;
    $typedep = $parsedBody['dep'];

    return funcaoenviomsgcliente($numero, $mensagem, $tipomsg, $usuario, $typedep);
});

$app->post('/temp_atualizamensagenscliente', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $idcliente = $req->getQueryParams()['idcliente'] ?? null;
    return atualizarnummensagens($idcliente);
});

$app->get('/temp_baixararquivo', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $queryParams = $req->getQueryParams();
    $contator = $queryParams['contator'] ?? null;
    $idcontato = $queryParams['idcontato'] ?? null;
    return buscararquivos($idcontato, $contator);
});

$app->get('/temp_nomearquivo', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $queryParams = $req->getQueryParams();
    $contator = $queryParams['contator'] ?? null;
    $idcontato = $queryParams['idcontato'] ?? null;
    return buscarnomearq($idcontato, $contator);
});

$app->post('/temp_uploadimgem', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getUploadedFiles();
    return uploadimgsendwhatsapp($dados['file']);
});

$app->post('/temp_uploadarquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $uploadedFiles = $req->getUploadedFiles();
    return uploadiarqpdfendwhatsapp($uploadedFiles['file']);
});

$app->post('/uploadarquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getUploadedFiles();
    return uploadiarqpdfendwhatsapp($dados['file']);
});

$app->post('/temp_deletearquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody();
    return deletaiarqpdfalvaempasta($dados['nomearquivo']);
});

$app->post('/deletearquivopdf', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody();
    return deletaiarqpdfalvaempasta($dados['filename']);
});

$app->post('/temp_deleteimgem', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {
    $dados = $req->getParsedBody();
    return deletaimgsalvaempasta($dados['nomearquivo']);
});

$app->post('/temp_atualizausuarioassumiuconversa', function ($req, $response, $args) use ($_USERID, $_USERNAME, $_USER_EMAIL, $_USER_ADMIN, $_USER_GRP, $_USER_MOBILE) {

    $idcliente = $req->getQueryParams()['idcliente'] ?? null;
    $usuario = $req->getQueryParams()['usuario'] ?? null;

    if ($idcliente !== null && $usuario !== null) {
        return AtualizarColaborador($usuario, $idcliente);
    } else {
        $dados = $req->getParsedBody();
        return AtualizarColaborador($_USERID, $dados);
    }
});

$app->run();
