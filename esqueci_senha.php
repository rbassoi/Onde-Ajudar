<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Esqueci minha senha — Onde Ajudar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        body { background: var(--board); background-image: radial-gradient(rgba(58,52,44,.06) 1px, transparent 1.1px); background-size: 20px 20px; }
    </style>
</head>
<body>
<div class="login-page">
<div class="login-box" style="max-width:420px">

    <div style="font-family:var(--hand);font-size:1.5rem;text-align:center;margin-bottom:4px">
        🔑 <span style="color:var(--accent)">Recuperar</span> senha
    </div>
    <p style="text-align:center;font-size:13px;color:var(--muted);margin-bottom:24px">
        Informe seu e-mail cadastrado e enviaremos o link de redefinição.
    </p>

    <?php if (isset($_SESSION['msg_reset'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['msg_reset'],'✓')!==false?'success':'danger' ?>" data-dismiss="auto">
            <?= $_SESSION['msg_reset'] ?>
        </div>
        <?php unset($_SESSION['msg_reset']); ?>
    <?php endif; ?>

    <form method="post" action="esqueci_senha_processa.php" data-validate>
        <div class="form-group">
            <label class="form-label">E-mail cadastrado <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" class="form-control" required
                   placeholder="seu@email.com" autofocus>
        </div>
        <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px">
            Enviar link de redefinição →
        </button>
    </form>

    <div style="margin-top:20px;display:flex;flex-direction:column;gap:8px;align-items:center;font-size:14px">
        <a href="login.php">← Voltar para o login</a>
        <a href="registro.php" style="color:var(--muted);font-size:13px">Não tem conta? Cadastre-se</a>
    </div>

</div>
</div>
<script src="estilos/js/app.js"></script>
</body>
</html>
