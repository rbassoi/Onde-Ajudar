<?php
// Arquivo substituído — redireciona para ver_cadastro.php
session_start();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['id'] ?? 0);
header('Location: ver_cadastro.php' . ($id ? "?id=$id" : ''));
exit;
?>
