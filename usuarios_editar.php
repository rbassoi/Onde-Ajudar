<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id < 1) { header('Location: usuarios_cadastro.php'); exit; }

// Busca dados do usuário
$stmt = $conn->prepare("SELECT u.id, u.login, u.nome, u.email, u.bloqueado, u.matricula,
    u.perfil, u.sexo, u.funcao
FROM usuarios u WHERE u.id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$usr = $stmt->fetch();
if (!$usr) { header('Location: usuarios_cadastro.php'); exit; }

$perfis  = $conn->query("SELECT id, perfil FROM perfil ORDER BY id")->fetchAll();
$situacs = $conn->query("SELECT id, situacao FROM usuarios_situacao ORDER BY id")->fetchAll();

$page_title = 'Editar Usuário — ' . htmlspecialchars($usr['nome']);
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Editar Usuário</h1>
        <p class="sub"><?= htmlspecialchars($usr['login']) ?></p>
    </div>
</div>

<div class="container-sm" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg_registro'])): ?>
    <div class="alert alert-<?= strpos($_SESSION['msg_registro'],'sucesso')!==false?'success':'danger' ?> mb-3" data-dismiss="auto">
        <?= $_SESSION['msg_registro'] ?>
    </div>
    <?php unset($_SESSION['msg_registro']); ?>
<?php endif; ?>

<!-- Alterar dados -->
<div class="card mb-3">
    <h2 style="margin-bottom:16px">Dados do usuário</h2>
    <form method="post" action="usuarios_update_processa.php" data-validate>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Login <span style="color:var(--danger)">*</span></label>
                <input type="text" name="user" class="form-control" required
                       value="<?= htmlspecialchars($usr['login']) ?>">
            </div>
            <div class="form-group">
                <label class="form-label">E-mail <span style="color:var(--danger)">*</span></label>
                <input type="email" name="email" class="form-control" required
                       value="<?= htmlspecialchars($usr['email'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Nome completo <span style="color:var(--danger)">*</span></label>
            <input type="text" name="nome" class="form-control" required
                   value="<?= htmlspecialchars($usr['nome']) ?>">
        </div>

        <div class="grid-2 mb-3">
            <div class="form-group">
                <label class="form-label">Perfil <span style="color:var(--danger)">*</span></label>
                <select name="perfil" class="form-control" required>
                    <?php foreach ($perfis as $p): ?>
                    <option value="<?= (int)$p['id'] ?>" <?= $usr['perfil'] == $p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['perfil']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Situação</label>
                <select name="situacao" class="form-control">
                    <?php foreach ($situacs as $s): ?>
                    <option value="<?= (int)$s['id'] ?>" <?= $usr['bloqueado'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['situacao']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- batalhao1 necessário pelo usuarios_update_processa.php -->
        <input type="hidden" name="batalhao1" value="">

        <div class="flex gap-2">
            <a href="usuarios_cadastro.php" class="btn btn-ghost">← Cancelar</a>
            <button type="submit" class="btn btn-primary">💾 Salvar</button>
        </div>
    </form>
</div>

<!-- Alterar senha -->
<div class="card">
    <h2 style="margin-bottom:16px">Alterar senha</h2>
    <form method="post" action="usuarios_update_senha_processa.php" data-validate>
        <input type="hidden" name="id" value="<?= $id ?>">
        <div class="grid-2 mb-3">
            <div class="form-group">
                <label class="form-label">Nova senha <span style="color:var(--danger)">*</span> <span class="text-muted text-sm">(mín. 8 caracteres)</span></label>
                <input type="password" name="senha" class="form-control" required minlength="8" placeholder="••••••••">
            </div>
        </div>
        <button type="submit" class="btn btn-primary">🔑 Alterar senha</button>
    </form>
</div>

</div>

<?php include 'includes/footer.php'; ?>
