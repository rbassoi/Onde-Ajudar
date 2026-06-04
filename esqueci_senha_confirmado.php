<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header('Location: index.php'); exit; }

$token = $_SESSION['_dev_reset_token'] ?? null;
$email = $_SESSION['_dev_reset_email'] ?? null;
unset($_SESSION['_dev_reset_token'], $_SESSION['_dev_reset_email']);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifique seu e-mail — Onde Ajudar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        body { background: var(--board); background-image: radial-gradient(rgba(58,52,44,.06) 1px, transparent 1.1px); background-size: 20px 20px; }
        .big-icon { font-size: 3.5rem; text-align: center; margin-bottom: 12px; }
    </style>
</head>
<body>
<div class="login-page">
<div class="login-box" style="max-width:440px;text-align:center">

    <div class="big-icon">📬</div>
    <h2 style="font-family:var(--hand);margin-bottom:8px">Verifique seu e-mail</h2>
    <p style="color:#5a534a;font-size:15px;margin-bottom:24px">
        Se o endereço <strong><?= $email ? htmlspecialchars($email) : 'informado' ?></strong>
        estiver cadastrado, você receberá um link para redefinir sua senha em instantes.
        O link expira em <strong>6 horas</strong>.
    </p>

    <?php if ($token): // Bloco de desenvolvimento — remover em produção ?>
    <div style="background:var(--warn-soft);border:1.5px solid var(--warn);border-radius:10px;padding:14px 16px;text-align:left;margin-bottom:20px">
        <p style="font-size:12px;font-weight:600;color:var(--warn);margin-bottom:8px">
            ⚠ AMBIENTE DE DESENVOLVIMENTO — em produção este link seria enviado por e-mail:
        </p>
        <a href="redefinir_senha.php?token=<?= htmlspecialchars($token) ?>"
           style="font-size:13px;word-break:break-all;color:var(--accent)">
            redefinir_senha.php?token=<?= htmlspecialchars($token) ?>
        </a>
    </div>
    <?php endif; ?>

    <div style="display:flex;flex-direction:column;gap:10px">
        <a href="login.php" class="btn btn-primary btn-block">← Voltar para o login</a>
        <a href="esqueci_senha.php" style="font-size:13px;color:var(--muted)">Não recebeu? Tentar novamente</a>
    </div>

</div>
</div>
<script src="estilos/js/app.js"></script>
</body>
</html>
