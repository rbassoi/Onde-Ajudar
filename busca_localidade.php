<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { http_response_code(403); exit; }
require_once('conexao.php');
header('Content-Type: application/json; charset=utf-8');

$acao = $_GET['acao'] ?? '';

if ($acao === 'cidades') {
    $estado_id = (int)($_GET['estado_id'] ?? 0);
    if (!$estado_id) { echo json_encode([]); exit; }
    $stmt = $conn->prepare("SELECT id, cidade FROM cidade WHERE estado_id = :eid ORDER BY cidade");
    $stmt->bindValue(':eid', $estado_id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    exit;
}

if ($acao === 'bairros') {
    $cidade_id = (int)($_GET['cidade_id'] ?? 0);
    if (!$cidade_id) { echo json_encode([]); exit; }
    $stmt = $conn->prepare(
        "SELECT DISTINCT trim(bairro) AS bairro FROM abordagem
         WHERE cidade = :cid AND bairro IS NOT NULL AND trim(bairro) <> ''
         ORDER BY bairro LIMIT 100"
    );
    $stmt->bindValue(':cid', $cidade_id, PDO::PARAM_INT);
    $stmt->execute();
    echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN));
    exit;
}

echo json_encode([]);
