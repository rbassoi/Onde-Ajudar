<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["nome_usuario"])) {
    header("Location: login.php");
    exit;
}

$login    = $_SESSION["login"];
$id_usuario = $_SESSION["id_usuario"];

include_once("conexao.php");

$senha = trim($_POST['senha'] ?? '');
$id    = trim($_POST['id']    ?? '');

$senhasegredo = sha1($senha);

if (strlen($senha) < 8) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! A senha deve ter mais de 8 dígitos!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");
    exit;
}

$sql_edita = $conn->prepare("UPDATE usuarios
    SET senha = :senha, data_cadastro = NOW(), usuario_cadastro = :login
    WHERE id = :id");

$sql_edita->bindParam(':senha', $senhasegredo);
$sql_edita->bindParam(':login', $login);
$sql_edita->bindParam(':id',    $id, PDO::PARAM_INT);
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
exit;
?>
