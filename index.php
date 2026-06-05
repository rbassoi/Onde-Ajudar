<?php
session_start();

if (!isset($_SESSION['id_usuario']) || !isset($_SESSION['nome_usuario'])) {
    header('Location: login.php');
    exit;
}

$login       = $_SESSION['login'];
$id_usuario  = $_SESSION['id_usuario'];
$permissao   = $_SESSION['permissao'];

require_once('conexao.php');

// ── Paginação ──────────────────────────────────────────────────
$pagina       = max(1, (int)($_GET['pagina'] ?? 1));
$quantidade_pg = 20;
$inicio        = ($quantidade_pg * $pagina) - $quantidade_pg;

// ── Filtros ────────────────────────────────────────────────────
$nome   = $_GET['nome']   ?? '';
$rg     = $_GET['rg']     ?? '';
$cpf    = $_GET['cpf']    ?? '';
$cidade = $_GET['cidade'] ?? '';
$core   = $_GET['core']   ?? '';

// ── Query principal ────────────────────────────────────────────
$sql_total = "SELECT COUNT(DISTINCT c.id)
              FROM cadastro c
              WHERE c.situacao = 1
                AND c.nome ILIKE :nome
                AND c.rg   ILIKE :rg
                AND c.cpf  ILIKE :cpf
                AND (:cidade = '' OR c.cidade = :cidadeid)";

$stmt_total = $conn->prepare($sql_total);
$stmt_total->bindValue(':nome',    '%' . $nome . '%');
$stmt_total->bindValue(':rg',      '%' . $rg   . '%');
$stmt_total->bindValue(':cpf',     '%' . $cpf  . '%');
$stmt_total->bindValue(':cidade',  $cidade ?? '');
$stmt_total->bindValue(':cidadeid', $cidade ?: 0, PDO::PARAM_INT);
$stmt_total->execute();
$total_registros = (int)$stmt_total->fetchColumn();
$num_pagina      = max(1, (int)ceil($total_registros / $quantidade_pg));

$sql = "SELECT
    c.id AS ide,
    c.nome,
    c.rg,
    c.cpf,
    ci.cidade,
    c.situacao,
    c.cor,
    (SELECT COUNT(a.id)           FROM abordagem a WHERE a.id_morador = c.id) AS abordagens,
    (SELECT MAX(a.data_abordagem) FROM abordagem a WHERE a.id_morador = c.id) AS dabordagem,
    (SELECT a.cor FROM abordagem a WHERE a.id_morador = c.id ORDER BY a.data_abordagem DESC LIMIT 1) AS core
FROM cadastro c
LEFT JOIN cidade ci ON c.cidade = ci.id
WHERE
    c.situacao = 1
    AND c.nome ILIKE :nome
    AND c.rg   ILIKE :rg
    AND c.cpf  ILIKE :cpf
    AND (:cidade = '' OR c.cidade = :cidadeid)
    AND (:core   = '' OR c.cor    = :coreid)
ORDER BY c.nome
LIMIT $quantidade_pg OFFSET $inicio";

$result_sql = $conn->prepare($sql);
$result_sql->bindValue(':nome',     '%' . $nome . '%');
$result_sql->bindValue(':rg',       '%' . $rg   . '%');
$result_sql->bindValue(':cpf',      '%' . $cpf  . '%');
$result_sql->bindValue(':cidade',   $cidade ?? '');
$result_sql->bindValue(':cidadeid', $cidade ?: 0, PDO::PARAM_INT);
$result_sql->bindValue(':core',     $core ?? '');
$result_sql->bindValue(':coreid',   $core ?: 0, PDO::PARAM_INT);
$result_sql->execute();

// ── Dados para os selects ──────────────────────────────────────
$cidades = $conn->query("SELECT id, cidade FROM cidade ORDER BY cidade")->fetchAll();
$perfis  = $conn->query("SELECT id, perfil FROM abordado_perfil ORDER BY perfil")->fetchAll();

$page_title = 'Dashboard — Moradores de Rua';
include 'includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h1>Cadastros</h1>
                <p class="sub">Moradores em situação de rua registrados no sistema</p>
            </div>
            <a href="cadastro.php" class="btn btn-primary">＋ Novo cadastro</a>
        </div>
    </div>
</div>

<div class="container" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg'])): ?>
    <div class="alert alert-success mb-3" data-dismiss="auto"><?= $_SESSION['msg'] ?></div>
    <?php unset($_SESSION['msg']); ?>
<?php endif; ?>

<!-- ── Painel de busca ───────────────────────────────────────── -->
<div class="search-panel mb-3">
    <form method="get" action="">
        <div class="row" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:14px">
            <div style="flex:2;min-width:200px">
                <label class="form-label">Nome</label>
                <input type="search" name="nome" class="form-control"
                       placeholder="Nome do cadastro…" value="<?= htmlspecialchars($nome) ?>">
            </div>
            <div style="flex:1;min-width:120px">
                <label class="form-label">RG</label>
                <input type="search" name="rg" class="form-control"
                       placeholder="RG…" value="<?= htmlspecialchars($rg) ?>">
            </div>
            <div style="flex:1;min-width:130px">
                <label class="form-label">CPF</label>
                <input type="search" name="cpf" class="form-control"
                       placeholder="CPF…" value="<?= htmlspecialchars($cpf) ?>">
            </div>
            <div style="flex:1;min-width:160px">
                <label class="form-label">Cidade</label>
                <select name="cidade" class="form-control">
                    <option value="">Todas as cidades</option>
                    <?php foreach ($cidades as $c): ?>
                    <option value="<?= (int)$c['id'] ?>" <?= ($cidade == $c['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($c['cidade']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="flex gap-2 items-center flex-wrap">
            <button type="submit" class="btn btn-primary">🔍 Pesquisar</button>
            <a href="index.php" class="btn btn-ghost">Limpar filtros</a>
            <span class="text-muted text-sm" style="margin-left:auto">
                <?= $total_registros ?> registro<?= $total_registros !== 1 ? 's' : '' ?> encontrado<?= $total_registros !== 1 ? 's' : '' ?>
            </span>
        </div>
    </form>
</div>

<!-- ── Tabela de resultados ──────────────────────────────────── -->
<div class="card" style="padding:0;overflow:hidden">
    <table class="data-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>RG</th>
                <th>CPF</th>
                <th>Cidade</th>
                <th>Abordagens</th>
                <th>Último contato</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = $result_sql->fetch()): ?>
            <?php
            $dias_class = 'grey';
            $dias_texto = '—';
            $pega_data  = $row['dabordagem'];

            if ($pega_data) {
                $diff = (new DateTime())->diff(new DateTime($pega_data));
                $dias = (int)$diff->format('%a');
                $dias_texto = $dias === 0 ? 'hoje' : $dias . ' dias';
                $dias_class = $dias <= 7 ? 'green' : 'yellow';
            }
            ?>
            <tr>
                <td>
                    <a href="ver_cadastro.php?id=<?= (int)$row['ide'] ?>">
                        <?= htmlspecialchars($row['nome']) ?>
                    </a>
                </td>
                <td class="text-muted text-sm"><?= htmlspecialchars($row['rg'] ?? '—') ?></td>
                <td class="text-muted text-sm"><?= htmlspecialchars($row['cpf'] ?? '—') ?></td>
                <td><?= htmlspecialchars($row['cidade'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-muted"><?= (int)($row['abordagens'] ?? 0) ?></span>
                </td>
                <td>
                    <a href="ver_cadastro.php?id=<?= (int)$row['ide'] ?>"
                       class="data-table days-btn <?= $dias_class ?>">
                        <?= $dias_texto ?>
                    </a>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <?php if ($total_registros === 0): ?>
    <div style="padding:40px;text-align:center;color:var(--muted)">
        Nenhum cadastro encontrado para os filtros informados.
    </div>
    <?php endif; ?>
</div>

<!-- ── Paginação ─────────────────────────────────────────────── -->
<?php if ($num_pagina > 1): ?>
<div class="pagination">
    <?php
    $qs = http_build_query(['nome' => $nome, 'rg' => $rg, 'cpf' => $cpf, 'cidade' => $cidade, 'core' => $core]);
    if ($pagina > 1): ?>
        <a href="?pagina=1&<?= $qs ?>">«</a>
        <a href="?pagina=<?= $pagina - 1 ?>&<?= $qs ?>">‹ Anterior</a>
    <?php endif; ?>

    <span class="current"><?= $pagina ?></span>
    <span class="text-muted text-sm">de <?= $num_pagina ?></span>

    <?php if ($pagina < $num_pagina): ?>
        <a href="?pagina=<?= $pagina + 1 ?>&<?= $qs ?>">Próxima ›</a>
        <a href="?pagina=<?= $num_pagina ?>&<?= $qs ?>">»</a>
    <?php endif; ?>
</div>
<?php endif; ?>

</div><!-- /container -->

<?php include 'includes/footer.php'; ?>
