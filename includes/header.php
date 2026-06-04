<?php
// Garante que a sessão já foi iniciada antes de incluir
if (session_status() === PHP_SESSION_NONE) session_start();

$login_atual  = $_SESSION['login']        ?? '';
$nome_atual   = $_SESSION['nome_usuario'] ?? '';
$permissao    = $_SESSION['permissao']    ?? '';

// Determina a página ativa para highlight no nav
$pagina_atual = basename($_SERVER['PHP_SELF'], '.php');

function nav_active(string $page): string {
    global $pagina_atual;
    return $pagina_atual === $page ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?? 'Onde Ajudar — Juntos por quem está na rua' ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="<?= $base_path ?? '' ?>estilos/css/app.css">
    <?php if (!empty($extra_css)): foreach ($extra_css as $css): ?>
    <link rel="stylesheet" href="<?= $base_path ?? '' ?><?= $css ?>">
    <?php endforeach; endif; ?>
</head>
<body>

<nav class="app-navbar">
    <div class="inner">
        <a href="<?= $base_path ?? '' ?>index.php" class="brand">
            🤝 <span>Onde</span>&nbsp;Ajudar
        </a>
        <nav>
            <a href="<?= $base_path ?? '' ?>index.php"          class="<?= nav_active('index') ?>">Dashboard</a>
            <a href="<?= $base_path ?? '' ?>avistamentos.php"   class="<?= nav_active('avistamentos') ?>">Avistamentos</a>
            <a href="<?= $base_path ?? '' ?>relatorio_idade.php" class="<?= nav_active('relatorio_idade') ?>">Relatórios</a>
            <?php if ($permissao == 1): ?>
            <a href="<?= $base_path ?? '' ?>usuarios_cadastro.php" class="<?= nav_active('usuarios_cadastro') ?>">Usuários</a>
            <?php endif; ?>
        </nav>
        <div class="user-info">
            <span><?= htmlspecialchars($nome_atual ?: $login_atual) ?></span>
            <a href="<?= $base_path ?? '' ?>sair.php" class="btn-logout">Sair</a>
        </div>
    </div>
</nav>
