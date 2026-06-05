<?php
session_start();

require_once("conexao.php");

$login  = isset($_POST["login"])  ? trim($_POST["login"])          : false;
$senha  = isset($_POST["senha"])  ? sha1(trim($_POST["senha"]))    : false;
$funcao = isset($_POST["funcao"]) ? trim($_POST["funcao"])         : false;

if (!$login || !$senha) {
    $_SESSION['msg_login'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Você deve digitar o login e a senha!<br/></strong>
    </div>';
    header("Location: login.php");
    exit;
}

$sql = "SELECT id, nome, login, senha, email, perfil, sexo, matricula, funcao
        FROM usuarios
        WHERE login = :login AND bloqueado = 1 AND funcao = :funcao
        LIMIT 1";

$result_sql = $conn->prepare($sql);
$result_sql->bindValue(':login',  $login);
$result_sql->bindValue(':funcao', $funcao, PDO::PARAM_INT);
$result_sql->execute();
$total_dados = $result_sql->rowCount();

if ($total_dados) {
    $dados = $result_sql->fetch();

    if (!strcmp($senha, $dados["senha"])) {
        $_SESSION["id_usuario"]   = $dados["id"];
        $_SESSION["login"]        = $dados["login"];
        $_SESSION["nome_usuario"] = $dados["nome"];
        $_SESSION["permissao"]    = $dados["perfil"];
        $_SESSION["sexo"]         = $dados["sexo"];
        $_SESSION["email"]        = $dados["email"];
        $_SESSION["matricula"]    = $dados["matricula"];
        $_SESSION["funcao"]       = $dados["funcao"];

        header("Location: avistamentos.php#registrar");
        exit;
    }

    $_SESSION['msg_login'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Senha inválida!<br/></strong>
    </div>';
    header("Location: login.php");
    exit;
}

$_SESSION['msg_login'] = '<div class="alert alert-danger alert-dismissable">
    <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
    <strong>Login inválido!<br/></strong>
</div>';
header("Location: login.php");
exit;
?>
