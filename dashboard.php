<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$page_title = 'Dashboard — Onde Ajudar';
require_once('includes/header.php');

// ── Avistamentos por status ──────────────────────────────────────
$q = $conn->query("
    SELECT status_avistamento AS s, COUNT(*) AS n
    FROM abordagem WHERE status_avistamento IS NOT NULL
    GROUP BY s
");
$avist_status = [];
foreach ($q->fetchAll() as $r) $avist_status[$r['s']] = (int)$r['n'];
$total_avist   = array_sum($avist_status);
$avist_urgente = $avist_status['urgente']  ?? 0;
$avist_pend    = $avist_status['pendente'] ?? 0;
$avist_atend   = $avist_status['atendido'] ?? 0;

// ── Avistamentos hoje ────────────────────────────────────────────
$avist_hoje = (int)$conn->query("
    SELECT COUNT(*) FROM abordagem
    WHERE status_avistamento IS NOT NULL AND DATE(data_abordagem) = CURRENT_DATE
")->fetchColumn();

// ── Avistamentos últimos 30 dias ─────────────────────────────────
$rows_30d = $conn->query("
    SELECT TO_CHAR(data_abordagem,'DD/MM') AS dia, COUNT(*) AS n
    FROM abordagem
    WHERE status_avistamento IS NOT NULL AND data_abordagem >= NOW() - INTERVAL '30 days'
    GROUP BY DATE(data_abordagem), TO_CHAR(data_abordagem,'DD/MM')
    ORDER BY DATE(data_abordagem)
")->fetchAll();
$dias_lbl = json_encode(array_column($rows_30d, 'dia'));
$dias_val = json_encode(array_map('intval', array_column($rows_30d, 'n')));

// ── Desaparecidos ────────────────────────────────────────────────
$q2 = $conn->query("SELECT status, COUNT(*) AS n FROM desaparecidos GROUP BY status");
$desap = [];
foreach ($q2->fetchAll() as $r) $desap[$r['status']] = (int)$r['n'];
$total_desap = array_sum($desap);

// ── Animais ──────────────────────────────────────────────────────
$q3 = $conn->query("SELECT status, COUNT(*) AS n FROM animais_desaparecidos GROUP BY status");
$anim = [];
foreach ($q3->fetchAll() as $r) $anim[$r['status']] = (int)$r['n'];
$total_anim = array_sum($anim);

// ── Restaurantes ─────────────────────────────────────────────────
$total_rest = (int)$conn->query("SELECT COUNT(*) FROM restaurantes_populares WHERE ativo = TRUE")->fetchColumn();

// ── Cadastros PSR ────────────────────────────────────────────────
$total_cad = (int)$conn->query("SELECT COUNT(*) FROM cadastro")->fetchColumn();

// ── Faixa etária PSR ─────────────────────────────────────────────
$rows_idade = $conn->query("
    SELECT i.descricao, COUNT(c.id) AS n
    FROM cadastro c
    JOIN intervalo i ON DATE_PART('year', AGE(c.data_nascimento)) BETWEEN i.idademinima AND i.idademaxima
    GROUP BY i.descricao, i.idademinima ORDER BY i.idademinima
")->fetchAll();
$idade_lbl = json_encode(array_column($rows_idade, 'descricao'));
$idade_val = json_encode(array_map('intval', array_column($rows_idade, 'n')));

// ── Sexo PSR ─────────────────────────────────────────────────────
$rows_sexo = $conn->query("
    SELECT s.sexo, COUNT(c.id) AS n FROM cadastro c JOIN sexo s ON c.sexo = s.id GROUP BY s.sexo
")->fetchAll();
$sexo_lbl = json_encode(array_column($rows_sexo, 'sexo'));
$sexo_val = json_encode(array_map('intval', array_column($rows_sexo, 'n')));

// ── Avistamentos recentes ─────────────────────────────────────────
$recentes = $conn->query("
    SELECT a.id, a.data_abordagem, a.bairro, a.endereco, a.pessoas_count,
           a.status_avistamento, ci.cidade AS cidade_nome
    FROM abordagem a
    LEFT JOIN cidade ci ON ci.id = a.cidade
    WHERE a.status_avistamento IS NOT NULL
    ORDER BY a.data_abordagem DESC LIMIT 6
")->fetchAll();

// ── Últimos desaparecidos ─────────────────────────────────────────
$desap_rec = $conn->query("
    SELECT nome, ultimo_local, status, criado_em FROM desaparecidos ORDER BY criado_em DESC LIMIT 5
")->fetchAll();
?>

<style>
/* ── Dashboard ────────────────────────────────────────────────── */
.dash-wrap { max-width: 1200px; margin: 0 auto; padding: 36px 20px 70px; }

.dash-header { margin-bottom: 32px; }
.dash-header h1 { font-family: var(--hand); font-size: clamp(1.6rem,4vw,2.2rem); margin-bottom: 4px; }
.dash-header p  { color: var(--muted); font-size: .95rem; margin: 0; }

/* Summary cards */
.summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
    gap: 16px;
    margin-bottom: 36px;
}
.sum-card {
    background: var(--paper);
    border: 2px solid rgba(58,52,44,.12);
    border-radius: var(--radius-lg);
    padding: 20px 18px;
    position: relative;
    overflow: hidden;
    transition: box-shadow .15s, transform .12s;
}
.sum-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-2px); }
.sum-card .sc-icon {
    font-size: 1.8rem; margin-bottom: 10px; display: block;
}
.sum-card .sc-num {
    font-family: var(--hand); font-size: 2.2rem; color: var(--ink);
    line-height: 1; display: block; margin-bottom: 4px;
}
.sum-card .sc-num.accent  { color: var(--accent); }
.sum-card .sc-num.warn    { color: #d97706; }
.sum-card .sc-num.danger  { color: #dc2626; }
.sum-card .sc-num.success { color: #16a34a; }
.sum-card .sc-lbl { font-size: 13px; color: var(--muted); line-height: 1.3; }
.sum-card .sc-sub { font-size: 11px; color: var(--muted); margin-top: 6px; }

/* Chart grid */
.chart-row {
    display: grid;
    gap: 20px;
    margin-bottom: 24px;
}
.chart-row.cols-2-1 { grid-template-columns: 2fr 1fr; }
.chart-row.cols-1-1 { grid-template-columns: 1fr 1fr; }
.chart-row.cols-3   { grid-template-columns: repeat(3, 1fr); }

.chart-card {
    background: var(--paper);
    border: 2px solid rgba(58,52,44,.12);
    border-radius: var(--radius-lg);
    padding: 22px 20px;
}
.chart-card h3 {
    font-family: var(--hand); font-size: 1.1rem; margin-bottom: 4px;
}
.chart-card .chart-sub { font-size: 12px; color: var(--muted); margin-bottom: 16px; }
.chart-card canvas { max-height: 240px; }

/* Status badge */
.badge {
    display: inline-block; padding: 2px 10px; border-radius: 20px;
    font-size: 11px; font-weight: 600; letter-spacing: .04em;
}
.badge-urgente  { background: #fee2e2; color: #dc2626; }
.badge-pendente { background: #fef3c7; color: #d97706; }
.badge-atendido { background: #dcfce7; color: #16a34a; }
.badge-aberto   { background: #fef3c7; color: #d97706; }
.badge-encontrado { background: #dcfce7; color: #16a34a; }
.badge-perdido  { background: #fee2e2; color: #dc2626; }

/* Tables */
.dash-table { width: 100%; border-collapse: collapse; font-size: 14px; }
.dash-table th {
    text-align: left; padding: 8px 10px;
    font-size: 11px; font-weight: 600; letter-spacing: .05em; text-transform: uppercase;
    color: var(--muted); border-bottom: 2px solid var(--line);
}
.dash-table td { padding: 10px 10px; border-bottom: 1px solid rgba(58,52,44,.07); vertical-align: middle; }
.dash-table tr:last-child td { border-bottom: none; }

.table-card {
    background: var(--paper);
    border: 2px solid rgba(58,52,44,.12);
    border-radius: var(--radius-lg);
    padding: 22px 20px;
    margin-bottom: 24px;
}
.table-card h3 { font-family: var(--hand); font-size: 1.1rem; margin-bottom: 4px; }
.table-card .chart-sub { font-size: 12px; color: var(--muted); margin-bottom: 16px; }

.table-row {
    display: grid;
    gap: 20px;
    grid-template-columns: 1fr 1fr;
    margin-bottom: 24px;
}

/* Donut legend */
.donut-wrap { display: flex; flex-direction: column; align-items: center; gap: 12px; }
.donut-legend { display: flex; flex-direction: column; gap: 6px; width: 100%; }
.legend-item { display: flex; align-items: center; gap: 8px; font-size: 13px; }
.legend-dot  { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
.legend-label { flex: 1; color: var(--muted); }
.legend-val  { font-weight: 600; color: var(--ink); }

@media (max-width: 768px) {
    .chart-row.cols-2-1,
    .chart-row.cols-1-1,
    .chart-row.cols-3,
    .table-row { grid-template-columns: 1fr; }
}
</style>

<div class="dash-wrap">

    <div class="dash-header">
        <h1>📊 Dashboard</h1>
        <p>Visão geral de todos os módulos · Atualizado agora</p>
    </div>

    <!-- ── Cards de resumo ─────────────────────────────────────── -->
    <div class="summary-grid">
        <div class="sum-card">
            <span class="sc-icon">🏠</span>
            <span class="sc-num accent"><?= $total_avist ?></span>
            <span class="sc-lbl">Avistamentos total</span>
            <span class="sc-sub">Hoje: <?= $avist_hoje ?></span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">🔴</span>
            <span class="sc-num danger"><?= $avist_urgente ?></span>
            <span class="sc-lbl">Avistamentos urgentes</span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">⏳</span>
            <span class="sc-num warn"><?= $avist_pend ?></span>
            <span class="sc-lbl">Avistamentos pendentes</span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">🔍</span>
            <span class="sc-num warn"><?= $desap['aberto'] ?? 0 ?></span>
            <span class="sc-lbl">Desaparecidos em aberto</span>
            <span class="sc-sub">Total: <?= $total_desap ?></span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">🐾</span>
            <span class="sc-num warn"><?= $anim['perdido'] ?? 0 ?></span>
            <span class="sc-lbl">Animais desaparecidos</span>
            <span class="sc-sub">Total: <?= $total_anim ?></span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">🍽️</span>
            <span class="sc-num success"><?= $total_rest ?></span>
            <span class="sc-lbl">Restaurantes ativos</span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">📋</span>
            <span class="sc-num accent"><?= $total_cad ?></span>
            <span class="sc-lbl">Pessoas cadastradas (PSR)</span>
        </div>
        <div class="sum-card">
            <span class="sc-icon">✅</span>
            <span class="sc-num success"><?= $avist_atend ?></span>
            <span class="sc-lbl">Avistamentos atendidos</span>
        </div>
    </div>

    <!-- ── Linha 1: linha do tempo + status avistamentos ──────── -->
    <div class="chart-row cols-2-1">
        <div class="chart-card">
            <h3>Avistamentos — últimos 30 dias</h3>
            <p class="chart-sub">Registros diários de população em situação de rua</p>
            <canvas id="chartTimeline"></canvas>
        </div>
        <div class="chart-card">
            <h3>Status dos avistamentos</h3>
            <p class="chart-sub">Distribuição atual</p>
            <div class="donut-wrap">
                <canvas id="chartAvistStatus" style="max-height:180px"></canvas>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#dc2626"></span>
                        <span class="legend-label">Urgente</span>
                        <span class="legend-val"><?= $avist_urgente ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#f59e0b"></span>
                        <span class="legend-label">Pendente</span>
                        <span class="legend-val"><?= $avist_pend ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#16a34a"></span>
                        <span class="legend-label">Atendido</span>
                        <span class="legend-val"><?= $avist_atend ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Linha 2: desaparecidos + animais + sexo PSR ───────── -->
    <div class="chart-row cols-3">
        <div class="chart-card">
            <h3>Pessoas desaparecidas</h3>
            <p class="chart-sub">Status dos registros</p>
            <div class="donut-wrap">
                <canvas id="chartDesap" style="max-height:160px"></canvas>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#f59e0b"></span>
                        <span class="legend-label">Aberto</span>
                        <span class="legend-val"><?= $desap['aberto'] ?? 0 ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#16a34a"></span>
                        <span class="legend-label">Encontrado</span>
                        <span class="legend-val"><?= $desap['encontrado'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="chart-card">
            <h3>Animais desaparecidos</h3>
            <p class="chart-sub">Status dos registros</p>
            <div class="donut-wrap">
                <canvas id="chartAnim" style="max-height:160px"></canvas>
                <div class="donut-legend">
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#dc2626"></span>
                        <span class="legend-label">Perdido</span>
                        <span class="legend-val"><?= $anim['perdido'] ?? 0 ?></span>
                    </div>
                    <div class="legend-item">
                        <span class="legend-dot" style="background:#16a34a"></span>
                        <span class="legend-label">Encontrado</span>
                        <span class="legend-val"><?= $anim['encontrado'] ?? 0 ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="chart-card">
            <h3>PSR por sexo</h3>
            <p class="chart-sub">Distribuição entre cadastrados</p>
            <canvas id="chartSexo" style="max-height:200px"></canvas>
        </div>
    </div>

    <!-- ── Linha 3: faixa etária ──────────────────────────────── -->
    <div class="chart-card" style="margin-bottom:24px">
        <h3>Faixa etária — População em Situação de Rua</h3>
        <p class="chart-sub">Distribuição dos cadastrados por intervalo de idade</p>
        <canvas id="chartIdade" style="max-height:220px"></canvas>
    </div>

    <!-- ── Tabelas recentes ───────────────────────────────────── -->
    <div class="table-row">
        <div class="table-card">
            <h3>🏠 Avistamentos recentes</h3>
            <p class="chart-sub">Últimos 6 registros</p>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Local</th>
                        <th>Pessoas</th>
                        <th>Status</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($recentes as $r): ?>
                    <tr>
                        <td>
                            <?= htmlspecialchars($r['bairro'] ?: ($r['cidade_nome'] ?: '—')) ?>
                            <?php if ($r['endereco']): ?>
                                <br><small style="color:var(--muted)"><?= htmlspecialchars(mb_strimwidth($r['endereco'], 0, 40, '…')) ?></small>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center"><?= (int)$r['pessoas_count'] ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($r['status_avistamento']) ?>"><?= htmlspecialchars($r['status_avistamento']) ?></span></td>
                        <td style="white-space:nowrap;color:var(--muted);font-size:13px">
                            <?= date('d/m H:i', strtotime($r['data_abordagem'])) ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($recentes)): ?>
                    <tr><td colspan="4" style="text-align:center;color:var(--muted);padding:24px">Nenhum avistamento registrado ainda.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="table-card">
            <h3>🔍 Desaparecidos recentes</h3>
            <p class="chart-sub">Últimos 5 registros</p>
            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Último local</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($desap_rec as $d): ?>
                    <tr>
                        <td><?= htmlspecialchars($d['nome']) ?></td>
                        <td style="color:var(--muted);font-size:13px"><?= htmlspecialchars(mb_strimwidth($d['ultimo_local'], 0, 35, '…')) ?></td>
                        <td><span class="badge badge-<?= htmlspecialchars($d['status']) ?>"><?= htmlspecialchars($d['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($desap_rec)): ?>
                    <tr><td colspan="3" style="text-align:center;color:var(--muted);padding:24px">Nenhum registro ainda.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#8c7b6e';

const accent  = '#cf6a44';
const warn    = '#f59e0b';
const danger  = '#dc2626';
const success = '#16a34a';
const muted   = '#d4c9bc';

// ── Timeline ─────────────────────────────────────────────────────
new Chart(document.getElementById('chartTimeline'), {
    type: 'line',
    data: {
        labels: <?= $dias_lbl ?: '[]' ?>,
        datasets: [{
            label: 'Avistamentos',
            data: <?= $dias_val ?: '[]' ?>,
            borderColor: accent,
            backgroundColor: 'rgba(207,106,68,.12)',
            borderWidth: 2.5,
            pointBackgroundColor: accent,
            pointRadius: 4,
            tension: .35,
            fill: true,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { color: 'rgba(58,52,44,.06)' } },
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(58,52,44,.06)' } }
        }
    }
});

// ── Avistamentos status donut ─────────────────────────────────────
new Chart(document.getElementById('chartAvistStatus'), {
    type: 'doughnut',
    data: {
        labels: ['Urgente', 'Pendente', 'Atendido'],
        datasets: [{
            data: [<?= $avist_urgente ?>, <?= $avist_pend ?>, <?= $avist_atend ?>],
            backgroundColor: [danger, warn, success],
            borderWidth: 0,
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        cutout: '68%',
        plugins: { legend: { display: false } }
    }
});

// ── Desaparecidos donut ───────────────────────────────────────────
new Chart(document.getElementById('chartDesap'), {
    type: 'doughnut',
    data: {
        labels: ['Aberto', 'Encontrado'],
        datasets: [{
            data: [<?= $desap['aberto'] ?? 0 ?>, <?= $desap['encontrado'] ?? 0 ?>],
            backgroundColor: [warn, success],
            borderWidth: 0, hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// ── Animais donut ─────────────────────────────────────────────────
new Chart(document.getElementById('chartAnim'), {
    type: 'doughnut',
    data: {
        labels: ['Perdido', 'Encontrado'],
        datasets: [{
            data: [<?= $anim['perdido'] ?? 0 ?>, <?= $anim['encontrado'] ?? 0 ?>],
            backgroundColor: [danger, success],
            borderWidth: 0, hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, cutout: '65%',
        plugins: { legend: { display: false } }
    }
});

// ── Sexo ─────────────────────────────────────────────────────────
new Chart(document.getElementById('chartSexo'), {
    type: 'doughnut',
    data: {
        labels: <?= $sexo_lbl ?: '[]' ?>,
        datasets: [{
            data: <?= $sexo_val ?: '[]' ?>,
            backgroundColor: [accent, '#7c9cbf', '#a8c5a0', muted],
            borderWidth: 0, hoverOffset: 6,
        }]
    },
    options: {
        responsive: true, cutout: '60%',
        plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 10, font: { size: 12 } } } }
    }
});

// ── Faixa etária bar ─────────────────────────────────────────────
new Chart(document.getElementById('chartIdade'), {
    type: 'bar',
    data: {
        labels: <?= $idade_lbl ?: '[]' ?>,
        datasets: [{
            label: 'Pessoas',
            data: <?= $idade_val ?: '[]' ?>,
            backgroundColor: accent,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(58,52,44,.06)' } }
        }
    }
});
</script>

<?php include 'includes/footer.php'; ?>
