<?php
session_start();
require_once('conexao.php');

if (isset($_SESSION['id_usuario'])) { header('Location: index.php'); exit; }
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar — Onde Ajudar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        body { background: var(--board); background-image: radial-gradient(rgba(58,52,44,.06) 1px, transparent 1.1px); background-size: 20px 20px; }
        .divider { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 13px; margin: 20px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: rgba(58,52,44,.15); }
    </style>
</head>
<body>

<div class="login-page">
    <div class="login-box">

        <div class="logo">🤝 <span>Onde</span> Ajudar</div>
        <p class="sub">Juntos por quem está na rua</p>

        <?php if (isset($_SESSION['msg_login'])): ?>
            <div class="alert alert-<?= strpos($_SESSION['msg_login'],'sucesso')!==false?'success':'danger' ?>" data-dismiss="auto">
                <?= $_SESSION['msg_login'] ?>
            </div>
            <?php unset($_SESSION['msg_login']); ?>
        <?php endif; ?>

        <form method="post" action="login_valida.php" data-validate>

            <div class="form-group">
                <label class="form-label" for="inp-login">Login</label>
                <input type="text" id="inp-login" name="login" class="form-control"
                       placeholder="Seu usuário" required autofocus autocomplete="username">
            </div>

            <div class="form-group">
                <label class="form-label" for="inp-senha">Senha</label>
                <input type="password" id="inp-senha" name="senha" class="form-control"
                       placeholder="••••••••" required autocomplete="current-password">
            </div>

            <div class="form-group">
                <label class="form-label" for="inp-funcao">Função</label>
                <select id="inp-funcao" name="funcao" class="form-control" required>
                    <option value="">Selecione sua função...</option>
                    <?php
                    $stmt = $conn->prepare("SELECT id, funcao FROM funcao ORDER BY funcao");
                    $stmt->execute();
                    while ($row = $stmt->fetch()):
                    ?>
                    <option value="<?= (int)$row['id'] ?>"><?= htmlspecialchars($row['funcao']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <!-- Esqueci minha senha -->
            <div style="text-align:right;margin-top:-6px;margin-bottom:16px">
                <a href="esqueci_senha.php" style="font-size:13px;color:var(--muted)">Esqueci minha senha</a>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                Entrar →
            </button>

        </form>

        <div class="divider">ou</div>

        <a href="registro.php" class="btn btn-ghost btn-block" style="text-align:center">
            Criar conta gratuitamente
        </a>

        <div style="text-align:center;margin-top:14px;font-size:13px;color:var(--muted)">
            <a href="landing.php" style="color:var(--muted)">← Voltar para o início</a>
        </div>

    </div>
</div>

<script src="estilos/js/app.js"></script>
</body>
</html>
