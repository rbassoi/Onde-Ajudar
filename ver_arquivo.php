<?php
require_once('conexao.php');

$codigo = isset($_GET['codigo']) ? (int)$_GET['codigo'] : 0;

$consulta = $conn->prepare("SELECT arquivo, tipo FROM arquivos WHERE codigo = :codigo");
$consulta->bindParam(':codigo', $codigo, PDO::PARAM_INT);
$consulta->execute();

$dados = $consulta->fetch();

if ($dados) {
    header("Content-type: " . $dados['tipo']);
    echo $dados['arquivo'];
} else {
    http_response_code(404);
    echo "Arquivo não encontrado.";
}
?>
