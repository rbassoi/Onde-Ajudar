<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$nome           = trim($_POST['nome']            ?? '');
$idade          = $_POST['idade']                ?? null;
$origem         = trim($_POST['origem']          ?? '');
$ultimo_local   = trim($_POST['ultimo_local']    ?? '');
$telefone       = trim($_POST['telefone_contato'] ?? '');
$descricao      = trim($_POST['descricao']       ?? '');

if (!$nome || !$ultimo_local || !$telefone) {
    header('Location: desaparecidos.php');
    exit;
}

$foto = null;
if (!empty($_FILES['foto']['tmp_name'])) {
    $dir = __DIR__ . '/uploads/desaparecidos/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp','gif'];
    if (in_array($ext, $allow, true)) {
        $nome_arquivo = uniqid('desap_') . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $nome_arquivo)) {
            $foto = $nome_arquivo;
        }
    }
}

$sql = "INSERT INTO desaparecidos
            (nome, idade, origem, ultimo_local, telefone_contato, descricao, foto, registrado_por)
        VALUES
            (:nome, :idade, :origem, :ultimo_local, :telefone, :descricao, :foto, :user_id)";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':nome',         $nome);
$stmt->bindValue(':idade',        $idade ?: null, PDO::PARAM_INT);
$stmt->bindValue(':origem',       $origem ?: null);
$stmt->bindValue(':ultimo_local', $ultimo_local);
$stmt->bindValue(':telefone',     $telefone);
$stmt->bindValue(':descricao',    $descricao ?: null);
$stmt->bindValue(':foto',         $foto);
$stmt->bindValue(':user_id',      $_SESSION['id_usuario'], PDO::PARAM_INT);
$stmt->execute();

header('Location: desaparecidos.php?ok=1');
exit;
