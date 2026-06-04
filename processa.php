<?php
session_start();
require_once('conexao.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_SESSION['id'] = (int)$_GET['id'];
}
$id = (int)($_SESSION['id'] ?? 0);

$cidade      = trim($_POST['cidade']      ?? '');
$bairro      = trim($_POST['bairro']      ?? '');
$rua         = trim($_POST['rua']         ?? '');
$tempocidade = trim($_POST['tempo_cidade'] ?? '');

if (empty($cidade)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>O campo Cidade é obrigatório!</strong></div>';
    header("Location: ver_cadastro.php?id=$id");
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO abordagem (id_morador, data_abordagem, cidade, bairro, endereco, tempo_cidade)
         VALUES (:id, NOW(), :cidade, :bairro, :rua, :tempocidade)"
    );
    $stmt->bindValue(':id',          $id,          PDO::PARAM_INT);
    $stmt->bindValue(':cidade',      $cidade ?: null);
    $stmt->bindValue(':bairro',      $bairro);
    $stmt->bindValue(':rua',         $rua);
    $stmt->bindValue(':tempocidade', $tempocidade);
    $stmt->execute();

    $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Visita registrada com sucesso!</strong></div>';
    header("Location: ver_cadastro.php?id=$id");

} catch (PDOException $e) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Não foi possível registrar a visita.</strong></div>';
    header("Location: ver_cadastro.php?id=$id");
    exit;
}
?>
