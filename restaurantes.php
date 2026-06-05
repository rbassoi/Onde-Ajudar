<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$page_title = 'Restaurantes Populares — Onde Ajudar';
$extra_css  = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];
$extra_js   = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'];

$fil_estado = trim($_GET['estado'] ?? 'MG');
$fil_cidade = trim($_GET['cidade'] ?? 'Belo Horizonte');
$aba        = $_GET['aba'] ?? 'restaurantes';

// Estados e cidades disponíveis
$estados_list = $conn->query(
    "SELECT DISTINCT estado FROM restaurantes_populares WHERE ativo = TRUE ORDER BY estado"
)->fetchAll(PDO::FETCH_COLUMN);

$cidades_list = [];
if ($fil_estado) {
    $sc = $conn->prepare(
        "SELECT DISTINCT cidade FROM restaurantes_populares WHERE estado = :e AND ativo = TRUE ORDER BY cidade"
    );
    $sc->execute([':e' => $fil_estado]);
    $cidades_list = $sc->fetchAll(PDO::FETCH_COLUMN);
}

// Restaurantes
$where  = ['r.ativo = TRUE'];
$params = [];
if ($fil_estado) { $where[] = 'r.estado = :estado'; $params[':estado'] = $fil_estado; }
if ($fil_cidade) { $where[] = 'r.cidade = :cidade'; $params[':cidade'] = $fil_cidade; }

$sql = "SELECT * FROM restaurantes_populares r WHERE " . implode(' AND ', $where) . " ORDER BY r.cidade, r.nome";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$restaurantes = $stmt->fetchAll();

// Cardápio semanal — semana atual ou próxima disponível
$cardapio_params = [];
$cardapio_where  = ['data_cardapio >= CURRENT_DATE - INTERVAL \'7 days\'',
                    'data_cardapio <= CURRENT_DATE + INTERVAL \'14 days\''];
if ($fil_cidade) { $cardapio_where[] = 'cidade = :cidade'; $cardapio_params[':cidade'] = $fil_cidade; }
if ($fil_estado) { $cardapio_where[] = 'estado = :estado'; $cardapio_params[':estado'] = $fil_estado; }

$sql_card = "SELECT * FROM cardapio_semanal WHERE " . implode(' AND ', $cardapio_where) . " ORDER BY data_cardapio, refeicao";
$stmt_card = $conn->prepare($sql_card);
$stmt_card->execute($cardapio_params);
$cardapio_rows = $stmt_card->fetchAll();

// Agrupa por data
$cardapio_por_dia = [];
foreach ($cardapio_rows as $row) {
    $cardapio_por_dia[$row['data_cardapio']][$row['refeicao']] = $row;
}

require_once('includes/header.php');
?>

<style>
.rest-wrap {
    max-width: 1000px;
    margin: 0 auto;
    padding: 32px 20px 60px;
    overflow: hidden;
}
.rest-tab-panel { min-width: 0; }

/* Abas */
.rest-tabs {
    display: flex;
    gap: 4px;
    background: var(--paper-2);
    border: 2px solid var(--line);
    border-radius: var(--radius);
    padding: 4px;
    margin-bottom: 28px;
    width: fit-content;
}
.rest-tabs .tab-btn {
    padding: 10px 22px;
    border: none;
    border-radius: calc(var(--radius) - 4px);
    background: transparent;
    font-family: var(--hand);
    font-size: 1.05rem;
    color: var(--muted);
    cursor: pointer;
    transition: background .15s, color .15s;
    white-space: nowrap;
}
.rest-tabs .tab-btn.active {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 2px 8px rgba(207,106,68,.3);
}
.rest-tab-panel { display: none; }
.rest-tab-panel.active { display: block; }

/* Cardápio semanal */
.cardapio-semana { display: flex; flex-direction: column; gap: 14px; }
.cardapio-dia {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    overflow: hidden;
}
.cardapio-dia.hoje { border-color: var(--accent); box-shadow: 0 0 0 2px rgba(207,106,68,.15); }
.cardapio-dia-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 12px 20px;
    background: var(--paper-2);
    border-bottom: 1.5px solid var(--line);
}
.cardapio-dia.hoje .cardapio-dia-header {
    background: var(--accent);
    color: #fff;
    border-bottom-color: var(--accent-dark);
}
.cardapio-dia-nome {
    font-family: var(--hand);
    font-size: 1.1rem;
}
.badge-pop-rua {
    background: var(--warn-soft);
    color: var(--warn);
    font-size: 11px;
    font-weight: 600;
    padding: 2px 8px;
    border-radius: 20px;
}
.cardapio-dia.hoje .badge-pop-rua {
    background: rgba(255,255,255,.25);
    color: #fff;
}
.cardapio-refeicoes {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
}
.cardapio-refeicao {
    padding: 14px 20px;
    border-right: 1.5px solid var(--board);
}
.cardapio-refeicao:last-child { border-right: none; }
.cardapio-ref-label {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: var(--muted);
    margin-bottom: 6px;
}
.cardapio-ref-itens {
    font-size: 13.5px;
    line-height: 1.65;
    color: var(--ink);
}
.cardapio-empty {
    text-align: center;
    padding: 50px 20px;
    color: var(--muted);
    font-family: var(--hand);
    font-size: 1.1rem;
}
@media (max-width: 600px) {
    .cardapio-refeicoes { grid-template-columns: 1fr; }
    .cardapio-refeicao { border-right: none; border-bottom: 1.5px solid var(--board); }
    .cardapio-refeicao:last-child { border-bottom: none; }
}

/* Mapa */
.map-wrapper {
    position: relative;
    overflow: hidden;
    width: 100%;
    max-width: 100%;
    height: 420px;
    border-radius: var(--radius-lg);
    border: 2px solid var(--line);
    box-sizing: border-box;
}
#rest-map {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
}
.map-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-top: 14px;
}
.map-list-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 11px 16px;
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius);
    cursor: pointer;
    transition: border-color .15s, box-shadow .15s, background .15s;
    text-align: left;
    width: 100%;
}
.map-list-item:hover  { border-color: var(--accent); box-shadow: 0 2px 8px rgba(207,106,68,.2); }
.map-list-item.active { border-color: var(--accent); background: var(--accent-soft); }
.map-list-dot {
    width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0;
    border: 2.5px solid rgba(0,0,0,.25);
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
.map-list-info { flex: 1; min-width: 0; }
.map-list-info strong { display: block; font-size: 14px; }
.map-list-info span   { font-size: 12px; color: var(--muted); }

/* Link "ver no mapa" nos cards */
.rest-addr-link {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    font-size: 12px;
    color: var(--accent);
    cursor: pointer;
    background: none;
    border: none;
    padding: 0;
    text-decoration: underline;
    text-underline-offset: 2px;
}
.rest-addr-link:hover { color: var(--accent-dark); }

/* Info de preços */
.preco-box {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    padding: 20px 24px;
    margin-bottom: 28px;
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    align-items: flex-start;
}
.preco-box-title {
    font-family: var(--hand);
    font-size: 1.1rem;
    margin-bottom: 10px;
    display: flex;
    align-items: center;
    gap: 8px;
}
.preco-table { border-collapse: collapse; font-size: 14px; min-width: 200px; }
.preco-table td { padding: 4px 12px 4px 0; }
.preco-table td:last-child { font-weight: 600; color: var(--accent); }
.beneficio-item { font-size: 13px; color: var(--muted); line-height: 1.6; }
.beneficio-item strong { color: var(--ink); }

/* Filtros */
.rest-filters {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    align-items: flex-end;
    margin-bottom: 28px;
}
.rest-filters .form-group { margin: 0; min-width: 160px; flex: 1; }

/* Grid de cards */
.rest-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}
.rest-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: box-shadow .15s, transform .12s;
}
.rest-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
.rest-card-header {
    padding: 18px 20px 14px;
    border-bottom: 1.5px solid var(--line);
    display: flex;
    align-items: flex-start;
    gap: 12px;
}
.rest-icon {
    font-size: 2rem;
    line-height: 1;
    flex-shrink: 0;
}
.rest-card-header h3 {
    font-family: var(--hand);
    font-size: 1.1rem;
    margin: 0 0 4px;
    line-height: 1.3;
}
.rest-card-header .rest-loc {
    font-size: 12px;
    color: var(--muted);
}
.badge-ativo  { background: var(--ok-soft);   color: var(--ok);   padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-obras  { background: var(--warn-soft);  color: var(--warn); padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }
.badge-fechado{ background: var(--danger-soft);color: var(--danger);padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; white-space: nowrap; }

.rest-card-body { padding: 16px 20px; }
.horarios { display: flex; flex-direction: column; gap: 6px; margin-bottom: 12px; }
.horario-row {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
}
.horario-label {
    font-weight: 600;
    min-width: 60px;
    font-size: 12px;
    color: var(--muted);
    text-transform: uppercase;
    letter-spacing: .04em;
}
.horario-time { color: var(--ink); }
.horario-price {
    margin-left: auto;
    font-weight: 600;
    color: var(--accent);
    font-size: 13px;
}
.rest-obs {
    font-size: 12px;
    color: var(--muted);
    background: var(--board);
    border-radius: var(--radius-sm);
    padding: 8px 12px;
    line-height: 1.5;
    margin-top: 10px;
}

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}
.empty-state .emoji { font-size: 3rem; margin-bottom: 12px; }

@media (max-width: 480px) {
    .rest-filters .form-group { min-width: 100%; }
    .preco-box { flex-direction: column; gap: 14px; }
}
</style>

<div class="rest-wrap">

    <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;margin-bottom:24px">
        <div style="display:flex;align-items:center;gap:12px">
            <a href="home.php" style="color:var(--muted);font-size:13px">← Início</a>
            <span style="color:var(--line)">·</span>
            <h1 style="margin:0;font-size:1.6rem">🍽️ Restaurantes Populares</h1>
        </div>
        <?php if ($_SESSION['permissao'] == 1): ?>
        <a href="admin_restaurantes.php" class="btn btn-ghost btn-sm">⚙ Administrar</a>
        <?php endif; ?>
    </div>

    <!-- Tabela de preços (aparece quando há cidade filtrada ou sempre) -->
    <?php
    $mostrar_preco = !empty($restaurantes);
    $cidade_exibida = $fil_cidade ?: ($restaurantes[0]['cidade'] ?? '');
    $estado_exibido = $fil_estado ?: ($restaurantes[0]['estado'] ?? '');
    ?>
    <?php if ($mostrar_preco): ?>
    <div class="preco-box">
        <div>
            <div class="preco-box-title">💰 Valores das refeições — <?= htmlspecialchars($cidade_exibida) ?>/<?= htmlspecialchars($estado_exibido) ?></div>
            <table class="preco-table">
                <tr><td>☕ Café da manhã</td><td>R$ <?= number_format($restaurantes[0]['preco_cafe'] ?? 0.75, 2, ',', '.') ?></td></tr>
                <tr><td>🍽️ Almoço</td><td>R$ <?= number_format($restaurantes[0]['preco_almoco'] ?? 3.00, 2, ',', '.') ?></td></tr>
                <tr><td>🌙 Jantar</td><td>R$ <?= number_format($restaurantes[0]['preco_jantar'] ?? 1.50, 2, ',', '.') ?></td></tr>
            </table>
        </div>
        <div>
            <div class="preco-box-title">🎟️ Benefícios</div>
            <div class="beneficio-item">
                <strong>Bolsa Família:</strong> 50% de desconto em todas as refeições.<br>
                Apresentar cartão Bolsa Família e documento de identidade.
            </div>
            <div class="beneficio-item" style="margin-top:8px">
                <strong>Gratuidade:</strong> Pessoas em situação de rua cadastradas no <strong>CadÚnico</strong>
                e catadores de material reciclável cadastrados na <strong>SLU</strong>.
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Filtros + abas combinados -->
    <div style="display:flex;flex-wrap:wrap;align-items:flex-end;justify-content:space-between;gap:14px;margin-bottom:24px">
        <div class="rest-tabs">
            <button class="tab-btn <?= $aba === 'restaurantes' || $aba === '' ? 'active' : '' ?>" data-tab="restaurantes">🏠 Restaurantes</button>
            <button class="tab-btn <?= $aba === 'cardapio' ? 'active' : '' ?>"                   data-tab="cardapio">📋 Cardápio</button>
            <button class="tab-btn <?= $aba === 'mapa' ? 'active' : '' ?>"                       data-tab="mapa">🗺 Mapa</button>
        </div>
        <form method="get" style="display:flex;gap:8px;flex-wrap:wrap;align-items:flex-end">
            <input type="hidden" name="aba" value="<?= htmlspecialchars($aba) ?>">
            <div class="form-group" style="margin:0;min-width:80px">
                <label class="form-label">Estado</label>
                <select name="estado" class="form-control" onchange="this.form.submit()">
                    <option value="">Todos</option>
                    <?php foreach ($estados_list as $uf): ?>
                    <option value="<?= htmlspecialchars($uf) ?>" <?= $fil_estado === $uf ? 'selected' : '' ?>><?= htmlspecialchars($uf) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if ($fil_estado && !empty($cidades_list)): ?>
            <div class="form-group" style="margin:0;min-width:160px">
                <label class="form-label">Cidade</label>
                <select name="cidade" class="form-control" onchange="this.form.submit()">
                    <option value="">Todas</option>
                    <?php foreach ($cidades_list as $cid): ?>
                    <option value="<?= htmlspecialchars($cid) ?>" <?= $fil_cidade === $cid ? 'selected' : '' ?>><?= htmlspecialchars($cid) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <!-- ── Aba: Restaurantes ───────────────────────────────────── -->
    <div class="rest-tab-panel <?= $aba !== 'cardapio' ? 'active' : '' ?>" id="tab-restaurantes">
        <?php if (empty($restaurantes)): ?>
        <div class="empty-state">
            <div class="emoji">🍽️</div>
            <p style="font-family:var(--hand);font-size:1.2rem;margin-bottom:4px">Nenhum restaurante encontrado</p>
        </div>
        <?php else: ?>
        <div class="rest-grid">
            <?php foreach ($restaurantes as $r):
                $badge = match($r['status']) {
                    'obras'   => '<span class="badge-obras">🔧 Em obras</span>',
                    'fechado' => '<span class="badge-fechado">✕ Fechado</span>',
                    default   => '<span class="badge-ativo">✓ Aberto</span>',
                };
            ?>
            <div class="rest-card">
                <div class="rest-card-header">
                    <span class="rest-icon">🍽️</span>
                    <div style="flex:1;min-width:0">
                        <h3><?= htmlspecialchars($r['nome']) ?></h3>
                        <div class="rest-loc">
                            <?= htmlspecialchars($r['endereco']) ?>
                            <?php if ($r['bairro']): ?> — <?= htmlspecialchars($r['bairro']) ?><?php endif; ?>
                        </div>
                        <?php if ($r['latitude'] && $r['longitude']): ?>
                        <button type="button" class="rest-addr-link" style="margin-top:4px"
                                onclick="focusRestaurante(<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>, <?= $r['id'] ?>)">
                            📍 Ver no mapa
                        </button>
                        <?php endif; ?>
                    </div>
                    <?= $badge ?>
                </div>
                <div class="rest-card-body">
                    <div class="horarios">
                        <?php if ($r['horario_cafe']): ?>
                        <div class="horario-row">
                            <span class="horario-label">☕ Café</span>
                            <span class="horario-time"><?= htmlspecialchars($r['horario_cafe']) ?></span>
                            <?php if ($r['preco_cafe']): ?><span class="horario-price">R$ <?= number_format($r['preco_cafe'], 2, ',', '.') ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($r['horario_almoco']): ?>
                        <div class="horario-row">
                            <span class="horario-label">🍽️ Almoço</span>
                            <span class="horario-time"><?= htmlspecialchars($r['horario_almoco']) ?></span>
                            <?php if ($r['preco_almoco']): ?><span class="horario-price">R$ <?= number_format($r['preco_almoco'], 2, ',', '.') ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                        <?php if ($r['horario_jantar']): ?>
                        <div class="horario-row">
                            <span class="horario-label">🌙 Jantar</span>
                            <span class="horario-time"><?= htmlspecialchars($r['horario_jantar']) ?></span>
                            <?php if ($r['preco_jantar']): ?><span class="horario-price">R$ <?= number_format($r['preco_jantar'], 2, ',', '.') ?></span><?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php if ($r['observacoes']): ?>
                    <div class="rest-obs">⚠️ <?= nl2br(htmlspecialchars($r['observacoes'])) ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Aba: Cardápio da semana ─────────────────────────────── -->
    <div class="rest-tab-panel <?= $aba === 'cardapio' ? 'active' : '' ?>" id="tab-cardapio">
        <?php
        $dias_pt = ['Sunday'=>'Domingo','Monday'=>'Segunda-feira','Tuesday'=>'Terça-feira',
                    'Wednesday'=>'Quarta-feira','Thursday'=>'Quinta-feira','Friday'=>'Sexta-feira','Saturday'=>'Sábado'];
        $refeicao_label = ['cafe'=>'☕ Café da manhã','almoco'=>'🍽️ Almoço','jantar'=>'🌙 Jantar'];
        $hoje_str = date('Y-m-d');
        ?>
        <?php if (empty($cardapio_por_dia)): ?>
        <div class="cardapio-empty">
            📋 Cardápio não disponível para este período.<br>
            <span style="font-size:.9rem;color:var(--muted)">Selecione Belo Horizonte / MG ou aguarde a atualização semanal.</span>
        </div>
        <?php else: ?>
        <div class="cardapio-semana">
            <?php foreach ($cardapio_por_dia as $data => $refeicoes):
                $dt        = new DateTime($data);
                $dia_nome  = $dias_pt[$dt->format('l')] ?? $dt->format('l');
                $dia_fmt   = $dt->format('d/m');
                $e_hoje    = ($data === $hoje_str);
                $pop_rua   = !empty(array_filter($refeicoes, fn($r) => $r['apenas_pop_rua']));
                $todos_pop = count(array_filter($refeicoes, fn($r) => $r['apenas_pop_rua'])) === count($refeicoes);
            ?>
            <div class="cardapio-dia <?= $e_hoje ? 'hoje' : '' ?>">
                <div class="cardapio-dia-header">
                    <span class="cardapio-dia-nome">
                        <?= $e_hoje ? '📍 Hoje — ' : '' ?><?= $dia_nome ?>, <?= $dia_fmt ?>
                    </span>
                    <?php if ($todos_pop): ?>
                    <span class="badge-pop-rua">Apenas pop. em situação de rua</span>
                    <?php endif; ?>
                </div>
                <div class="cardapio-refeicoes">
                    <?php foreach (['cafe','almoco','jantar'] as $ref):
                        if (!isset($refeicoes[$ref])) continue;
                        $row = $refeicoes[$ref];
                        $itens_list = array_map('trim', explode('·', $row['itens']));
                    ?>
                    <div class="cardapio-refeicao">
                        <div class="cardapio-ref-label"><?= $refeicao_label[$ref] ?></div>
                        <div class="cardapio-ref-itens">
                            <?php foreach ($itens_list as $item): ?>
                            <div>• <?= htmlspecialchars($item) ?></div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Aba: Mapa ──────────────────────────────────────────── -->
    <div class="rest-tab-panel <?= $aba === 'mapa' ? 'active' : '' ?>" id="tab-mapa">
        <div class="map-wrapper"><div id="rest-map"></div></div>
        <div class="map-list">
            <?php
            $status_color = ['ativo' => '#4a8c5c', 'obras' => '#c4952a', 'fechado' => '#c0392b'];
            foreach ($restaurantes as $r):
                if (!$r['latitude'] || !$r['longitude']) continue;
                $cor = $status_color[$r['status']] ?? '#cf6a44';
            ?>
            <button type="button" class="map-list-item"
                    onclick="focusRestaurante(<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>, <?= $r['id'] ?>)"
                    id="mli-<?= $r['id'] ?>">
                <span class="map-list-dot" style="background:<?= $cor ?>"></span>
                <span class="map-list-info">
                    <strong><?= htmlspecialchars($r['nome']) ?></strong>
                    <span><?= htmlspecialchars($r['endereco']) ?><?= $r['bairro'] ? ' — ' . htmlspecialchars($r['bairro']) : '' ?></span>
                </span>
                <span style="font-size:12px;color:var(--muted)">📍 Ver no mapa</span>
            </button>
            <?php endforeach; ?>
        </div>
    </div>

</div>

<script>
// ── Dados ─────────────────────────────────────────────────────────────────────
const _pins = <?= json_encode(array_values(array_filter(array_map(fn($r) => !$r['latitude'] ? null : [
    'id'      => $r['id'],
    'nome'    => $r['nome'],
    'endereco'=> $r['endereco'] . ($r['bairro'] ? ' — ' . $r['bairro'] : ''),
    'status'  => $r['status'],
    'lat'     => (float)$r['latitude'],
    'lng'     => (float)$r['longitude'],
    'horarios'=> array_values(array_filter([
        $r['horario_cafe']   ? '☕ Café: '   . $r['horario_cafe']   : null,
        $r['horario_almoco'] ? '🍽️ Almoço: ' . $r['horario_almoco'] : null,
        $r['horario_jantar'] ? '🌙 Jantar: ' . $r['horario_jantar'] : null,
    ])),
], $restaurantes)))) ?>;

const _statusColor = { ativo: '#4a8c5c', obras: '#c4952a', fechado: '#c0392b' };
let _map = null, _markers = {}, _mapReady = false;

// ── Abas ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.rest-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.rest-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.rest-tab-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById('tab-' + btn.dataset.tab);
        if (panel) panel.classList.add('active');

        if (btn.dataset.tab === 'mapa') {
            // Dois rAF garantem que o browser pintou o container antes do Leaflet medir
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (!_mapReady) initRestMap();
                else _map?.invalidateSize({ animate: false });
            }));
        }

        const url = new URL(window.location);
        url.searchParams.set('aba', btn.dataset.tab);
        history.replaceState(null, '', url);
    });
});

// Carrega o mapa se a aba já estiver ativa ao carregar a página
if (document.getElementById('tab-mapa')?.classList.contains('active')) {
    requestAnimationFrame(() => requestAnimationFrame(initRestMap));
}

// ── Mapa Leaflet ──────────────────────────────────────────────────────────────
function initRestMap() {
    if (typeof L === 'undefined' || _mapReady) return;
    _mapReady = true;

    _map = L.map('rest-map', { zoomControl: true });

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(_map);

    const bounds = [];

    _pins.forEach(p => {
        const color = _statusColor[p.status] || '#cf6a44';

        // Marcador tipo pin teardrop — grande e visível
        const pinHtml = `
            <div style="position:relative;width:32px;height:42px">
                <div style="
                    position:absolute;top:0;left:0;
                    width:32px;height:32px;border-radius:50% 50% 50% 0;
                    transform:rotate(-45deg);
                    background:${color};
                    border:3px solid #fff;
                    box-shadow:0 2px 8px rgba(0,0,0,.35);
                "></div>
                <div style="
                    position:absolute;bottom:0;left:50%;transform:translateX(-50%);
                    width:0;height:0;
                    border-left:6px solid transparent;
                    border-right:6px solid transparent;
                    border-top:10px solid ${color};
                "></div>
            </div>`;

        const icon = L.divIcon({
            className: '',
            html: pinHtml,
            iconSize:   [32, 42],
            iconAnchor: [16, 42],
            popupAnchor:[0, -44],
        });

        const statusLabel = {
            ativo:   '<span style="background:#d9e7d2;color:#4a8c5c;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">✓ Aberto</span>',
            obras:   '<span style="background:#fef3d0;color:#c4952a;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">🔧 Em obras</span>',
            fechado: '<span style="background:#fde8e6;color:#c0392b;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">✕ Fechado</span>',
        }[p.status] || '';

        const popup = `
            <div style="font-family:Inter,sans-serif;min-width:220px;max-width:260px">
                <strong style="font-size:14px;display:block;margin-bottom:4px">${escHtml(p.nome)}</strong>
                <div style="color:#8d8579;font-size:12px;margin-bottom:8px">${escHtml(p.endereco)}</div>
                <div style="margin-bottom:8px">${statusLabel}</div>
                <div style="font-size:13px;line-height:1.9;border-top:1px solid #e8e2d8;padding-top:8px">
                    ${p.horarios.map(h => `<div>${escHtml(h)}</div>`).join('')}
                </div>
                <a href="https://www.google.com/maps/search/?api=1&query=${p.lat},${p.lng}"
                   target="_blank" rel="noopener"
                   style="display:inline-block;margin-top:10px;font-size:12px;color:#cf6a44;text-decoration:underline">
                   🗺 Abrir no Google Maps →
                </a>
            </div>`;

        _markers[p.id] = L.marker([p.lat, p.lng], { icon })
            .bindPopup(popup, { maxWidth: 280, offset: [0, -10] })
            .addTo(_map);

        bounds.push([p.lat, p.lng]);
    });

    // Enquadra todos os marcadores
    if (bounds.length > 0) {
        _map.fitBounds(L.latLngBounds(bounds).pad(0.15));
    } else {
        _map.setView([-19.92, -43.94], 12);
    }
}

// ── Foca restaurante (chamado tanto do card quanto da lista do mapa) ──────────
function focusRestaurante(lat, lng, id) {
    const mapaBtn = document.querySelector('.rest-tabs .tab-btn[data-tab="mapa"]');
    const mapaTab = document.getElementById('tab-mapa');

    if (!mapaTab?.classList.contains('active')) {
        mapaBtn?.click();           // abre a aba — initRestMap será chamado pelo listener
    }

    const doFocus = () => {
        if (!_map) { setTimeout(doFocus, 80); return; }
        _map.setView([lat, lng], 16, { animate: true });
        setTimeout(() => _markers[id]?.openPopup(), 300);
        document.querySelectorAll('.map-list-item').forEach(el => el.classList.remove('active'));
        document.getElementById('mli-' + id)?.classList.add('active');
        // Rola suavemente até o mapa
        document.getElementById('rest-map')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };
    doFocus();
}

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once('includes/footer.php'); ?>
