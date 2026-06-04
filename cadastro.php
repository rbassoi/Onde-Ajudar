<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$estados_list = $conn->query("SELECT id, estado FROM estados ORDER BY estado")->fetchAll();
$cidades_list = $conn->query("SELECT id, cidade FROM cidade  ORDER BY cidade")->fetchAll();
$escolaris    = $conn->query("SELECT id, escolaridade FROM cadastro_escolaridade ORDER BY id")->fetchAll();

$page_title = 'Novo Cadastro — Moradores de Rua';
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Novo Cadastro</h1>
        <p class="sub">Registro de morador em situação de rua</p>
    </div>
</div>

<div class="container-sm" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg_registro'])): ?>
    <div class="alert alert-<?= strpos($_SESSION['msg_registro'],'sucesso')!==false?'success':'danger' ?> mb-3" data-dismiss="auto">
        <?= $_SESSION['msg_registro'] ?>
    </div>
    <?php unset($_SESSION['msg_registro']); ?>
<?php endif; ?>

<div class="card">
    <form method="post" action="cad_processa.php" data-validate>

        <!-- Identificação -->
        <div class="form-group">
            <label class="form-label">Nome completo <span style="color:var(--danger)">*</span></label>
            <input type="text" name="nome" class="form-control" required placeholder="Nome do morador">
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">RG</label>
                <input type="text" name="rg" class="form-control" placeholder="Número do RG">
            </div>
            <div class="form-group">
                <label class="form-label">CPF <span style="color:var(--danger)">*</span></label>
                <input type="text" name="cpf" class="form-control" required placeholder="000.000.000-00">
            </div>
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Data de Nascimento <span style="color:var(--danger)">*</span></label>
                <input type="date" name="datanascimento" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Sexo</label>
                <select name="sexo" class="form-control">
                    <option value="">Selecione...</option>
                    <option value="1">Masculino</option>
                    <option value="2">Feminino</option>
                    <option value="3">Outro</option>
                </select>
            </div>
        </div>

        <!-- Origem -->
        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Estado de origem</label>
                <select name="estado" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($estados_list as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Cidade de origem</label>
                <select name="cidade" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($cidades_list as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['cidade']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Escolaridade</label>
            <select name="escolaridade" class="form-control">
                <option value="">Selecione...</option>
                <?php foreach ($escolaris as $es): ?>
                <option value="<?= (int)$es['id'] ?>"><?= htmlspecialchars($es['escolaridade']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Situação -->
        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-2">
            <div class="form-group">
                <label class="form-label">Situação de rua</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="situacaorua" value="1"> Sim</label>
                    <label><input type="radio" name="situacaorua" value="2" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo</label>
                <input type="text" name="motivorua" class="form-control" placeholder="Motivo de estar na rua">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-2">
            <div class="form-group">
                <label class="form-label">Uso de álcool/drogas</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="usuario" value="1"> Sim</label>
                    <label><input type="radio" name="usuario" value="2" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dependente de</label>
                <input type="text" name="tipousuario" class="form-control" placeholder="Substância(s)">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-2">
            <div class="form-group">
                <label class="form-label">Deficiência</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="deficiencia" value="1"> Sim</label>
                    <label><input type="radio" name="deficiencia" value="2" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de deficiência</label>
                <input type="text" name="tipodeficiencia" class="form-control" placeholder="Descreva o tipo">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-2">
            <div class="form-group">
                <label class="form-label">Passagem criminal</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="passagem" value="1"> Sim</label>
                    <label><input type="radio" name="passagem" value="2" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de passagem</label>
                <input type="text" name="tipopassagem" class="form-control" placeholder="Tipo de passagem criminal">
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Dados complementares</label>
            <textarea name="complemento" class="form-control" rows="3" placeholder="Informações adicionais..."></textarea>
        </div>

        <div class="flex gap-2">
            <a href="index.php" class="btn btn-ghost">← Cancelar</a>
            <button type="submit" class="btn btn-primary">💾 Cadastrar morador</button>
        </div>

    </form>
</div>

</div>

<?php include 'includes/footer.php'; ?>
