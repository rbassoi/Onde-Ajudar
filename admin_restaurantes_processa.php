<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
if ($_SESSION['permissao'] != 1)     { header('Location: home.php');  exit; }

require_once('conexao.php');

$acao = $_POST['acao'] ?? 'salvar';
$id   = !empty($_POST['id']) ? (int)$_POST['id'] : null;

if ($acao === 'excluir' && $id) {
    $conn->prepare("DELETE FROM restaurantes_populares WHERE id = :id")->execute([':id' => $id]);
    header('Location: admin_restaurantes.php?ok=excluido');
    exit;
}

// Salvar (insert ou update)
$nome           = trim($_POST['nome']            ?? '');
$cidade         = trim($_POST['cidade']          ?? '');
$estado         = strtoupper(trim($_POST['estado'] ?? ''));
$endereco       = trim($_POST['endereco']        ?? '');
$bairro         = trim($_POST['bairro']          ?? '') ?: null;
$horario_cafe   = trim($_POST['horario_cafe']    ?? '') ?: null;
$horario_almoco = trim($_POST['horario_almoco']  ?? '') ?: null;
$horario_jantar = trim($_POST['horario_jantar']  ?? '') ?: null;
$preco_cafe     = $_POST['preco_cafe']   !== '' ? (float)$_POST['preco_cafe']   : null;
$preco_almoco   = $_POST['preco_almoco'] !== '' ? (float)$_POST['preco_almoco'] : null;
$preco_jantar   = $_POST['preco_jantar'] !== '' ? (float)$_POST['preco_jantar'] : null;
$observacoes    = trim($_POST['observacoes']     ?? '') ?: null;
$status         = $_POST['status']               ?? 'ativo';
$latitude       = $_POST['latitude']  !== '' ? (float)$_POST['latitude']  : null;
$longitude      = $_POST['longitude'] !== '' ? (float)$_POST['longitude'] : null;

if (!$nome || !$cidade || !$estado || !$endereco) {
    header('Location: admin_restaurantes.php');
    exit;
}

if ($id) {
    $sql = "UPDATE restaurantes_populares SET
                nome = :nome, cidade = :cidade, estado = :estado,
                endereco = :endereco, bairro = :bairro,
                horario_cafe = :hcafe, horario_almoco = :halmoco, horario_jantar = :hjantar,
                preco_cafe = :pcafe, preco_almoco = :palmoco, preco_jantar = :pjantar,
                observacoes = :obs, status = :status,
                latitude = :lat, longitude = :lng
            WHERE id = :id";
} else {
    $sql = "INSERT INTO restaurantes_populares
                (nome, cidade, estado, endereco, bairro,
                 horario_cafe, horario_almoco, horario_jantar,
                 preco_cafe, preco_almoco, preco_jantar,
                 observacoes, status, latitude, longitude)
            VALUES
                (:nome, :cidade, :estado, :endereco, :bairro,
                 :hcafe, :halmoco, :hjantar,
                 :pcafe, :palmoco, :pjantar,
                 :obs, :status, :lat, :lng)";
}

$stmt = $conn->prepare($sql);
$stmt->bindValue(':nome',    $nome);
$stmt->bindValue(':cidade',  $cidade);
$stmt->bindValue(':estado',  $estado);
$stmt->bindValue(':endereco',$endereco);
$stmt->bindValue(':bairro',  $bairro);
$stmt->bindValue(':hcafe',   $horario_cafe);
$stmt->bindValue(':halmoco', $horario_almoco);
$stmt->bindValue(':hjantar', $horario_jantar);
$stmt->bindValue(':pcafe',   $preco_cafe);
$stmt->bindValue(':palmoco', $preco_almoco);
$stmt->bindValue(':pjantar', $preco_jantar);
$stmt->bindValue(':obs',     $observacoes);
$stmt->bindValue(':status',  $status);
$stmt->bindValue(':lat',     $latitude);
$stmt->bindValue(':lng',     $longitude);
if ($id) $stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();

header('Location: admin_restaurantes.php?ok=salvo');
exit;
