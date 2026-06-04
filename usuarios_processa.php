<?php
session_start();

if (!isset($_SESSION["id_usuario"]) || !isset($_SESSION["nome_usuario"])) {
    header("Location: login.php");
    exit;
}

$login    = $_SESSION["login"];
$id_usuario = $_SESSION["id_usuario"];

require_once("conexao.php");

$user            = trim($_POST['user']     ?? '');
$senha           = trim($_POST['senha']    ?? '');
$email           = trim($_POST['email']    ?? '');
$nome            = trim($_POST['nome']     ?? '');
$perfil          = trim($_POST['perfil']   ?? '');
$sexo            = trim($_POST['sexo']     ?? '');
$matricula       = trim($_POST['matricula'] ?? '');
$funcao          = trim($_POST['funcao']   ?? '');
$situacaousuario = 1;
$senhasegredo    = sha1($senha);

// Verificar login duplicado
$sql_check = $conn->prepare("SELECT login FROM usuarios WHERE login = :user");
$sql_check->bindValue(':user', $user);
$sql_check->execute();
$total_dados = $sql_check->rowCount();

if (empty($user)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Login é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif ($total_dados > 0) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O Login informado já existe!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} elseif (strlen($senha) < 8) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! A senha deve ter mais de 8 dígitos!</strong>
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

} elseif (empty($funcao)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Função é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: usuarios_cadastro.php");

} else {
    $sql_inserir = $conn->prepare("INSERT INTO usuarios
        (login, senha, email, nome, bloqueado, data_cadastro, perfil, usuario_cadastro, sexo, matricula, funcao)
        VALUES (:user, :senhasegredo, :email, :nome, :situacaousuario, NOW(), :perfil, :login, :sexo, :matricula, :funcao)");

    $sql_inserir->bindParam(':user',            $user);
    $sql_inserir->bindParam(':senhasegredo',    $senhasegredo);
    $sql_inserir->bindParam(':email',           $email);
    $sql_inserir->bindParam(':nome',            $nome);
    $sql_inserir->bindParam(':situacaousuario', $situacaousuario, PDO::PARAM_INT);
    $sql_inserir->bindParam(':perfil',          $perfil,          PDO::PARAM_INT);
    $sql_inserir->bindParam(':login',           $login);
    $sql_inserir->bindParam(':sexo',            $sexo,            PDO::PARAM_INT);
    $sql_inserir->bindParam(':matricula',       $matricula);
    $sql_inserir->bindParam(':funcao',          $funcao,          PDO::PARAM_INT);
    $sql_inserir->execute();

    if ($sql_inserir->rowCount() > 0) {
        $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Cadastro registrado com sucesso!</strong>
        </div>';
        header("Location: usuarios_cadastro.php");
    } else {
        $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
            <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
            <strong>Não foi possível cadastrar no banco de dados!</strong>
        </div>';
        header("Location: usuarios_cadastro.php");
    }
}
?>
