<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$especie    = trim($_POST['especie']          ?? '');
$raca       = trim($_POST['raca']             ?? '');
$nome_animal= trim($_POST['nome_animal']      ?? '');
$cor        = trim($_POST['cor']              ?? '');
$porte      = trim($_POST['porte']            ?? '');
$sexo       = trim($_POST['sexo']             ?? 'desconhecido');
$rua        = trim($_POST['rua']              ?? '');
$bairro     = trim($_POST['bairro']           ?? '');
$estado_id  = (int)($_POST['estado_id']       ?? 0);
$cidade_id  = (int)($_POST['cidade_id']       ?? 0);
$telefone   = trim($_POST['telefone_contato'] ?? '');
$recompensa = ($_POST['recompensa'] ?? '') !== '' ? (float)$_POST['recompensa'] : null;
$descricao  = trim($_POST['descricao']        ?? '');
$latitude   = ($_POST['latitude']  ?? '') !== '' ? (float)$_POST['latitude']  : null;
$longitude  = ($_POST['longitude'] ?? '') !== '' ? (float)$_POST['longitude'] : null;

if (!$especie || !$cor || !$rua || !$estado_id || !$cidade_id || !$telefone) {
    header('Location: animais_desaparecidos.php');
    exit;
}

// Monta ultimo_local a partir dos campos estruturados
$s_estado = $conn->prepare("SELECT estado FROM estados WHERE id = :id");
$s_estado->execute([':id' => $estado_id]);
$estado_nome = $s_estado->fetchColumn() ?: '';

$s_cidade = $conn->prepare("SELECT cidade FROM cidade WHERE id = :id");
$s_cidade->execute([':id' => $cidade_id]);
$cidade_nome = $s_cidade->fetchColumn() ?: '';

$partes = array_filter([
    $rua,
    $bairro ?: null,
    $cidade_nome ? $cidade_nome . ($estado_nome ? '/' . $estado_nome : '') : null,
]);
$ultimo_local = implode(' — ', $partes);

$foto = null;
if (!empty($_FILES['foto']['tmp_name'])) {
    $dir = __DIR__ . '/uploads/animais/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ext   = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
    $allow = ['jpg','jpeg','png','webp','gif'];
    if (in_array($ext, $allow, true)) {
        $nome_arq = uniqid('animal_') . '.' . $ext;
        if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $nome_arq)) {
            $foto = $nome_arq;
        }
    }
}

$sql = "INSERT INTO animais_desaparecidos
            (especie, raca, nome_animal, cor, porte, sexo,
             ultimo_local, telefone_contato, recompensa, descricao,
             foto, latitude, longitude, registrado_por)
        VALUES
            (:especie, :raca, :nome_animal, :cor, :porte, :sexo,
             :ultimo, :telefone, :recompensa, :descricao,
             :foto, :lat, :lng, :user_id)";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':especie',    $especie);
$stmt->bindValue(':raca',       $raca       ?: null);
$stmt->bindValue(':nome_animal',$nome_animal ?: null);
$stmt->bindValue(':cor',        $cor);
$stmt->bindValue(':porte',      $porte      ?: null);
$stmt->bindValue(':sexo',       $sexo);
$stmt->bindValue(':ultimo',     $ultimo_local);
$stmt->bindValue(':telefone',   $telefone);
$stmt->bindValue(':recompensa', $recompensa);
$stmt->bindValue(':descricao',  $descricao  ?: null);
$stmt->bindValue(':foto',       $foto);
$stmt->bindValue(':lat',        $latitude);
$stmt->bindValue(':lng',        $longitude);
$stmt->bindValue(':user_id',    $_SESSION['id_usuario'], PDO::PARAM_INT);
$stmt->execute();

header('Location: animais_desaparecidos.php?ok=1');
exit;
