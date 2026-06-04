<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$perfis  = $conn->query("SELECT id, perfil FROM perfil ORDER BY id")->fetchAll();
$sexos   = $conn->query("SELECT id, sexo   FROM sexo   ORDER BY id")->fetchAll();
$funcoes = $conn->query("SELECT id, funcao FROM funcao ORDER BY funcao")->fetchAll();

$usuarios = $conn->query("SELECT u.id, u.login, u.nome, u.email,
    us.situacao, p.perfil, f.funcao
FROM usuarios u
INNER JOIN perfil          p  ON p.id  = u.perfil
INNER JOIN usuarios_situacao us ON us.id = u.bloqueado
INNER JOIN funcao          f  ON f.id  = u.funcao
ORDER BY u.bloqueado, u.nome")->fetchAll();

$page_title = 'Usuários — Moradores de Rua';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Gestão de Usuários</h1>
        <p class="sub">Cadastro, edição e controle de acesso</p>
    </div>
</div>

<div class="container" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg_registro'])): ?>
    <div class="alert alert-<?= strpos($_SESSION['msg_registro'],'sucesso')!==false ? 'success':'danger' ?> mb-3" data-dismiss="auto">
        <?= $_SESSION['msg_registro'] ?>
    </div>
    <?php unset($_SESSION['msg_registro']); ?>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 2fr;gap:20px;align-items:start">

<!-- ── Formulário de cadastro ──────────────────────────────── -->
<div class="card">
    <h2 style="margin-bottom:16px">Novo usuário</h2>
    <form method="post" action="usuarios_processa.php" data-validate>

        <div class="form-group">
            <label class="form-label">Login <span style="color:var(--danger)">*</span></label>
            <input type="text" name="user" class="form-control" required placeholder="login único">
        </div>
        <div class="form-group">
            <label class="form-label">Senha <span style="color:var(--danger)">*</span> <span class="text-muted text-sm">(mín. 8 caracteres)</span></label>
            <input type="password" name="senha" class="form-control" required minlength="8" placeholder="••••••••">
        </div>
        <div class="form-group">
            <label class="form-label">Nome completo <span style="color:var(--danger)">*</span></label>
            <input type="text" name="nome" class="form-control" required placeholder="Nome completo">
        </div>
        <div class="form-group">
            <label class="form-label">E-mail <span style="color:var(--danger)">*</span></label>
            <input type="email" name="email" class="form-control" required placeholder="email@dominio.com">
        </div>
        <div class="form-group">
            <label class="form-label">Perfil <span style="color:var(--danger)">*</span></label>
            <select name="perfil" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach ($perfis as $p): ?>
                <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['perfil']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Sexo</label>
            <select name="sexo" class="form-control">
                <option value="">Selecione...</option>
                <?php foreach ($sexos as $s): ?>
                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['sexo']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label class="form-label">Matrícula</label>
            <input type="text" name="matricula" class="form-control" placeholder="Matrícula com dígito">
        </div>
        <div class="form-group">
            <label class="form-label">Função <span style="color:var(--danger)">*</span></label>
            <select name="funcao" class="form-control" required>
                <option value="">Selecione...</option>
                <?php foreach ($funcoes as $f): ?>
                <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['funcao']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="flex gap-2">
            <button type="reset"  class="btn btn-ghost">Limpar</button>
            <button type="submit" class="btn btn-primary">＋ Cadastrar</button>
        </div>
    </form>
</div>

<!-- ── Lista de usuários ─────────────────────────────────────── -->
<div class="card" style="padding:0;overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Login</th>
                <th>Nome</th>
                <th>Perfil</th>
                <th>Situação</th>
                <th>Função</th>
                <th>Ação</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
            <td><?= htmlspecialchars($u['login']) ?></td>
            <td><?= htmlspecialchars($u['nome']) ?></td>
            <td><span class="badge badge-muted"><?= htmlspecialchars($u['perfil']) ?></span></td>
            <td><?= $u['situacao'] === 'Ativo' ? '<span class="badge badge-ok">Ativo</span>' : '<span class="badge badge-wait">'.$u['situacao'].'</span>' ?></td>
            <td class="text-sm text-muted"><?= htmlspecialchars($u['funcao']) ?></td>
            <td>
                <a href="usuarios_editar.php?id=<?= (int)$u['id'] ?>" class="btn btn-ghost btn-sm">✏ Editar</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($usuarios)): ?>
        <tr><td colspan="6" style="text-align:center;padding:30px;color:var(--muted)">Nenhum usuário cadastrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div><!-- /grid -->
</div>

<?php include 'includes/footer.php'; ?>
