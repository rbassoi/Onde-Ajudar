<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_SESSION['id'] = (int)$_GET['id'];
}
$id = (int)($_SESSION['id'] ?? 0);
if ($id < 1) {
    header('Location: index.php');
    exit;
}

// ── Dados do cadastro ──────────────────────────────────────────
$sql = "SELECT
    c.id, c.nome, c.rg, c.cpf, c.situacao,
    c.data_nascimento, c.sexo, c.estado, c.cidade,
    c.escolaridade, c.situacao_rua, c.motivo,
    c.deficiencia, c.tipo_deficiencia,
    c.usuario, c.tipo_usuario,
    c.passagem, c.tipo_passagem, c.complemento,
    cs.id AS ids,   cs.situacao AS situacao_label,
    ci.id AS idcid, ci.cidade   AS cidade_label,
    se.id AS idn,   se.sexo     AS sexo_label,
    es.id AS idest, es.estado   AS estado_label,
    e.id  AS ide,   e.escolaridade AS escolaridade_label
FROM cadastro c
LEFT JOIN cadastro_situacao   cs ON cs.id = c.situacao
LEFT JOIN cidade              ci ON ci.id = c.cidade
LEFT JOIN sexo                se ON se.id = c.sexo
LEFT JOIN estados             es ON es.id = c.estado
LEFT JOIN cadastro_escolaridade e ON e.id = c.escolaridade
WHERE c.id = :id";

$stmt = $conn->prepare($sql);
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetch();

if (!$row) {
    header('Location: index.php');
    exit;
}

// ── Listas de referência ───────────────────────────────────────
$situacoes    = $conn->query("SELECT id, situacao FROM cadastro_situacao ORDER BY id")->fetchAll();
$estados_list = $conn->query("SELECT id, estado   FROM estados ORDER BY estado")->fetchAll();
$cidades_list = $conn->query("SELECT id, cidade   FROM cidade  ORDER BY cidade")->fetchAll();
$escolaris    = $conn->query("SELECT id, escolaridade FROM cadastro_escolaridade ORDER BY id")->fetchAll();

$page_title = 'Editar Cadastro — ' . htmlspecialchars($row['nome']);
include 'includes/header.php';
?>

<div class="page-header">
    <div class="container">
        <h1>Editar Cadastro</h1>
        <p class="sub"><?= htmlspecialchars($row['nome']) ?></p>
    </div>
</div>

<div class="container" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg_registro'])): ?>
    <div class="alert alert-<?= strpos($_SESSION['msg_registro'], 'sucesso') !== false ? 'success' : 'danger' ?> mb-3" data-dismiss="auto">
        <?= $_SESSION['msg_registro'] ?>
    </div>
    <?php unset($_SESSION['msg_registro']); ?>
<?php endif; ?>

<div class="card">
    <form method="post" action="cadastro_update_processa.php" data-validate>
        <input type="hidden" name="id" value="<?= $id ?>">

        <!-- Nome -->
        <div class="form-group">
            <label class="form-label">Nome <span style="color:var(--danger)">*</span></label>
            <input type="text" name="nome" class="form-control" required
                   value="<?= htmlspecialchars($row['nome']) ?>">
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px" class="mb-3">
            <!-- Situação -->
            <div class="form-group">
                <label class="form-label">Situação</label>
                <select name="situacao" class="form-control">
                    <?php foreach ($situacoes as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $row['situacao'] == $s['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($s['situacao']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- RG -->
            <div class="form-group">
                <label class="form-label">RG</label>
                <input type="text" name="rg" class="form-control"
                       value="<?= htmlspecialchars($row['rg'] ?? '') ?>">
            </div>
            <!-- CPF -->
            <div class="form-group">
                <label class="form-label">CPF <span style="color:var(--danger)">*</span></label>
                <input type="text" name="cpf" class="form-control" required
                       value="<?= htmlspecialchars($row['cpf'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:14px" class="mb-3">
            <!-- Data nascimento -->
            <div class="form-group">
                <label class="form-label">Data de Nascimento</label>
                <input type="date" name="datanascimento" class="form-control"
                       value="<?= htmlspecialchars($row['data_nascimento'] ?? '') ?>">
            </div>
            <!-- Estado -->
            <div class="form-group">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($estados_list as $e): ?>
                    <option value="<?= $e['id'] ?>" <?= $row['estado'] == $e['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($e['estado']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Cidade -->
            <div class="form-group">
                <label class="form-label">Cidade</label>
                <select name="cidade" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($cidades_list as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $row['cidade'] == $c['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['cidade']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Escolaridade -->
            <div class="form-group">
                <label class="form-label">Escolaridade</label>
                <select name="escolaridade" class="form-control">
                    <option value="">Selecione...</option>
                    <?php foreach ($escolaris as $es): ?>
                    <option value="<?= $es['id'] ?>" <?= $row['escolaridade'] == $es['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($es['escolaridade']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-3">
            <div class="form-group">
                <label class="form-label">Situação de Rua</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="situacaorua" value="1" <?= $row['situacao_rua'] == 1 ? 'checked' : '' ?>> Sim</label>
                    <label><input type="radio" name="situacaorua" value="2" <?= $row['situacao_rua'] == 2 ? 'checked' : '' ?>> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Motivo que vive na rua</label>
                <input type="text" name="motivorua" class="form-control"
                       value="<?= htmlspecialchars($row['motivo'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-3">
            <div class="form-group">
                <label class="form-label">Possui Deficiência</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="deficiencia" value="1" <?= $row['deficiencia'] == 1 ? 'checked' : '' ?>> Sim</label>
                    <label><input type="radio" name="deficiencia" value="2" <?= $row['deficiencia'] == 2 ? 'checked' : '' ?>> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Deficiência</label>
                <input type="text" name="tipodeficiencia" class="form-control"
                       value="<?= htmlspecialchars($row['tipo_deficiencia'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-3">
            <div class="form-group">
                <label class="form-label">Uso de Álcool/Drogas</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="usuario" value="1" <?= $row['usuario'] == 1 ? 'checked' : '' ?>> Sim</label>
                    <label><input type="radio" name="usuario" value="2" <?= $row['usuario'] == 2 ? 'checked' : '' ?>> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Dependente de</label>
                <input type="text" name="tipousuario" class="form-control"
                       value="<?= htmlspecialchars($row['tipo_usuario'] ?? '') ?>">
            </div>
        </div>

        <div style="display:grid;grid-template-columns:auto 1fr;gap:20px;align-items:start" class="mb-3">
            <div class="form-group">
                <label class="form-label">Passagem Criminal</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="passagem" value="1" <?= $row['passagem'] == 1 ? 'checked' : '' ?>> Sim</label>
                    <label><input type="radio" name="passagem" value="2" <?= $row['passagem'] == 2 ? 'checked' : '' ?>> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de Passagem</label>
                <input type="text" name="tipopassagem" class="form-control"
                       value="<?= htmlspecialchars($row['tipo_passagem'] ?? '') ?>">
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Dados Complementares</label>
            <textarea name="complemento" class="form-control" rows="4"
                      placeholder="Informações adicionais..."><?= htmlspecialchars($row['complemento'] ?? '') ?></textarea>
        </div>

        <div style="display:flex;gap:10px;flex-wrap:wrap">
            <a href="ver_cadastro.php?id=<?= $id ?>" class="btn btn-ghost">← Cancelar</a>
            <button type="submit" class="btn btn-primary">💾 Salvar alterações</button>
        </div>
    </form>
</div>

</div>

<?php include 'includes/footer.php'; ?>
