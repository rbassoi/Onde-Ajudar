<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header('Location: index.php'); exit; }
require_once('conexao.php');

$token = trim($_GET['token'] ?? '');
$erro  = '';
$usr   = null;

if (!$token) {
    $erro = 'Link inválido ou expirado.';
} else {
    $stmt = $conn->prepare("SELECT rt.id AS tid, rt.token, u.id AS uid, u.login, u.nome
        FROM reset_tokens rt
        JOIN usuarios u ON u.id = rt.usuario_id
        WHERE rt.token = :token AND rt.usado = FALSE AND rt.expira_em > NOW()
        LIMIT 1");
    $stmt->bindValue(':token', $token);
    $stmt->execute();
    $usr = $stmt->fetch();
    if (!$usr) $erro = 'Este link de redefinição é inválido, já foi usado ou expirou.';
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Redefinir senha — Onde Ajudar</title>
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
        🔐 Nova <span style="color:var(--accent)">senha</span>
    </div>

    <?php if ($erro): ?>
        <div class="alert alert-danger" style="margin:16px 0">
            <?= htmlspecialchars($erro) ?>
        </div>
        <div style="text-align:center;font-size:14px">
            <a href="esqueci_senha.php">← Solicitar novo link</a>
        </div>

    <?php else: ?>
        <p style="text-align:center;font-size:13px;color:var(--muted);margin-bottom:24px">
            Olá, <strong><?= htmlspecialchars($usr['nome']) ?></strong>. Escolha uma nova senha.
        </p>

        <?php if (isset($_SESSION['msg_reset'])): ?>
            <div class="alert alert-danger" data-dismiss="auto"><?= $_SESSION['msg_reset'] ?></div>
            <?php unset($_SESSION['msg_reset']); ?>
        <?php endif; ?>

        <form method="post" action="redefinir_senha_processa.php" data-validate id="form-reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

            <div class="form-group">
                <label class="form-label">Nova senha <span style="color:var(--danger)">*</span></label>
                <input type="password" name="senha" id="senha" class="form-control"
                       required minlength="8" placeholder="Mínimo 8 caracteres" autofocus>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar nova senha <span style="color:var(--danger)">*</span></label>
                <input type="password" name="senha_conf" id="senha_conf" class="form-control"
                       required placeholder="Repita a nova senha">
            </div>
            <div id="senha-err" style="color:var(--danger);font-size:13px;margin-top:-8px;margin-bottom:12px;display:none">
                As senhas não coincidem.
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:4px">
                Salvar nova senha →
            </button>
        </form>

        <script>
        document.getElementById('form-reset').addEventListener('submit', function(e) {
            const s1 = document.getElementById('senha').value;
            const s2 = document.getElementById('senha_conf').value;
            if (s1 !== s2) { e.preventDefault(); document.getElementById('senha-err').style.display='block'; }
        });
        document.getElementById('senha_conf').addEventListener('input', function() {
            const ok = this.value === document.getElementById('senha').value;
            document.getElementById('senha-err').style.display = (this.value && !ok) ? 'block' : 'none';
        });
        </script>
    <?php endif; ?>

    <div style="margin-top:20px;text-align:center;font-size:14px">
        <a href="login.php" style="color:var(--muted)">← Voltar para o login</a>
    </div>

</div>
</div>
<script src="estilos/js/app.js"></script>
</body>
</html>
