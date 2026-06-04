<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["nome_usuario"])) {
    header("Location: login.php");
    exit;
}

$login    = $_SESSION["login"];
$id_usuario = $_SESSION["id_usuario"];

include_once("conexao.php");

$user     = trim($_POST['user']      ?? '');
$email    = trim($_POST['email']     ?? '');
$nome     = trim($_POST['nome']      ?? '');
$perfil   = trim($_POST['perfil']    ?? '');
$situacao = trim($_POST['situacao']  ?? '');
$batalhao = trim($_POST['batalhao1'] ?? '');
$id       = trim($_POST['id']        ?? '');

if (empty($user)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Login é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif (empty($email)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo E-mail é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif (empty($nome)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Nome é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif (empty($perfil)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Perfil é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif (empty($batalhao)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Batalhão é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} else {
    $sql_edita = $conn->prepare("UPDATE usuarios
        SET login = :user, email = :email, nome = :nome, bloqueado = :situacao,
            data_cadastro = NOW(), perfil = :perfil, usuario_cadastro = :login
        WHERE id = :id");

    $sql_edita->bindParam(':user',     $user);
    $sql_edita->bindParam(':email',    $email);
    $sql_edita->bindParam(':nome',     $nome);
    $sql_edita->bindParam(':situacao', $situacao, PDO::PARAM_INT);
    $sql_edita->bindParam(':perfil',   $perfil,   PDO::PARAM_INT);
    $sql_edita->bindParam(':login',    $login);
    $sql_edita->bindParam(':id',       $id,       PDO::PARAM_INT);
    $sql_edita->execute();

    if ($sql_edita->rowCount() > 0) {
        $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Alteração registrada com sucesso!</strong>
        </div>';
        header("Location: usuarios_cadastro.php");
    } else {
        $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Atenção!! A alteração não pôde ser registrada no banco de dados!</strong>
        </div>';
        header("Location: usuarios_editar.php");
    }
}
?>
