<?php
session_start();
if (isset($_SESSION['id_usuario'])) { header('Location: index.php'); exit; }
require_once('conexao.php');

$funcoes = $conn->query("SELECT id, funcao FROM funcao ORDER BY funcao")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar conta — Onde Ajudar</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Patrick+Hand&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        body { background: var(--board); background-image: radial-gradient(rgba(58,52,44,.06) 1px, transparent 1.1px); background-size: 20px 20px; }
        .reg-page { min-height: 100vh; display: flex; align-items: flex-start; justify-content: center; padding: 40px 20px; }
        .reg-box { background: var(--paper); border: 2px solid rgba(58,52,44,.15); border-radius: 20px; padding: 36px 32px; width: 100%; max-width: 480px; box-shadow: 0 4px 24px rgba(44,38,32,.12); }
        .reg-box .logo { font-family: var(--hand); font-size: 1.5rem; text-align: center; margin-bottom: 4px; }
        .reg-box .logo span { color: var(--accent); }
        .reg-box .sub { text-align: center; font-size: 13px; color: var(--muted); margin-bottom: 26px; }
        .divider { display: flex; align-items: center; gap: 10px; color: var(--muted); font-size: 13px; margin: 20px 0; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: rgba(58,52,44,.15); }
        .hint { font-size: 12px; color: var(--muted); margin-top: 4px; }
        .perfil-note { background: var(--paper-2); border: 1.5px solid rgba(207,106,68,.3); border-radius: 10px; padding: 10px 14px; font-size: 13px; color: #5a534a; margin-top: 8px; display: none; }
        .perfil-note.show { display: block; }
    </style>
</head>
<body>
<div class="reg-page">
<div class="reg-box">

    <div class="logo">🤝 <span>Onde</span> Ajudar</div>
    <p class="sub">Crie sua conta e faça parte da rede de cuidado</p>

    <?php if (isset($_SESSION['msg_registro'])): ?>
        <div class="alert alert-<?= strpos($_SESSION['msg_registro'],'sucesso')!==false?'success':'danger' ?>" data-dismiss="auto">
            <?= $_SESSION['msg_registro'] ?>
        </div>
        <?php unset($_SESSION['msg_registro']); ?>
    <?php endif; ?>

    <form method="post" action="registro_processa.php" data-validate id="form-reg">

        <div class="form-group">
            <label class="form-label">Nome completo <span style="color:var(--danger)">*</span></label>
            <input type="text" name="nome" class="form-control" required placeholder="Como você se chama">
        </div>

        <div class="form-group">
            <label class="form-label">E-mail <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="seu@email.com">
            <p class="hint">Usado para recuperar sua senha</p>
        </div>

        <div class="form-group">
            <label class="form-label">Login <span style="color:var(--danger)">*</span></label>
            <input type="text" name="user" class="form-control" required
                   pattern="[a-zA-Z0-9._-]+" placeholder="Apenas letras, números, . _ -"
                   autocomplete="username">
            <p class="hint">Sem espaços ou caracteres especiais</p>
        </div>

        <div class="form-group">
            <label class="form-label">Função / Como você atua <span style="color:var(--danger)">*</span></label>
            <select name="funcao" id="sel-funcao" class="form-control" required onchange="mostrarNota(this)">
                <option value="">Selecione...</option>
                <?php foreach ($funcoes as $f): ?>
                <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['funcao']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="perfil-note" id="nota-autoridade">
                ⚠ Contas de <strong>Autoridade Pública</strong> e <strong>Ministério Público</strong> são ativadas manualmente por um administrador após verificação.
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Senha <span style="color:var(--danger)">*</span></label>
                <input type="password" name="senha" id="senha" class="form-control" required
                       minlength="8" placeholder="Mín. 8 caracteres" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar senha <span style="color:var(--danger)">*</span></label>
                <input type="password" name="senha_conf" id="senha_conf" class="form-control" required
                       placeholder="Repita a senha" autocomplete="new-password">
            </div>
        </div>
        <div id="senha-err" style="color:var(--danger);font-size:13px;margin-top:-8px;margin-bottom:12px;display:none">
            As senhas não coincidem.
        </div>

        <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px">
            Criar minha conta →
        </button>

    </form>

    <div class="divider">ou</div>

    <div style="text-align:center;font-size:14px">
        Já tem conta? <a href="login.php">Entrar</a>
    </div>
    <div style="text-align:center;font-size:13px;margin-top:8px">
        <a href="landing.php" style="color:var(--muted)">← Voltar para o início</a>
    </div>

</div>
</div>

<script src="estilos/js/app.js"></script>
<script>
const autoridadeIds = <?= json_encode(array_values(array_filter(array_column($funcoes, 'id', 'funcao'), fn($f) => in_array($f, ['Autoridade Pública', 'Ministério Público']), ARRAY_KEYS))) ?>;

// Corrige o json_encode acima para pegar IDs corretos
const autoridadeFuncoes = <?= json_encode(
    array_values(array_map(
        fn($f) => (int)$f['id'],
        array_filter($funcoes, fn($f) => in_array($f['funcao'], ['Autoridade Pública', 'Ministério Público']))
    ))
) ?>;

function mostrarNota(sel) {
    const nota = document.getElementById('nota-autoridade');
    nota.classList.toggle('show', autoridadeFuncoes.includes(parseInt(sel.value)));
}

// Validação de senhas iguais
document.getElementById('form-reg').addEventListener('submit', function(e) {
    const s1 = document.getElementById('senha').value;
    const s2 = document.getElementById('senha_conf').value;
    const err = document.getElementById('senha-err');
    if (s1 !== s2) {
        e.preventDefault();
        err.style.display = 'block';
        document.getElementById('senha_conf').focus();
    } else {
        err.style.display = 'none';
    }
});
document.getElementById('senha_conf').addEventListener('input', function() {
    const match = this.value === document.getElementById('senha').value;
    document.getElementById('senha-err').style.display = (this.value && !match) ? 'block' : 'none';
});
</script>
</body>
</html>
