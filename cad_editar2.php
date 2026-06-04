<?php
// Arquivo substituído — redireciona para cad_editar.php
session_start();
$id = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_SESSION['id'] ?? 0);
header('Location: cad_editar.php' . ($id ? "?id=$id" : ''));
exit;
?>
