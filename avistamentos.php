<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$page_title = 'Avistamentos — Moradores de Rua';
$extra_css  = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];
$extra_js   = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'];

// ── Buscar avistamentos recentes ──────────────────────────────
$filtro = $_GET['filtro'] ?? 'recentes';

$sql_feed = "SELECT
    a.id,
    a.data_abordagem,
    a.bairro,
    a.endereco,
    a.relato,
    a.pessoas_count,
    a.necessidades,
    a.latitude,
    a.longitude,
    a.contato_registrante,
    a.status_avistamento,
    a.cor,
    ci.cidade AS cidade_nome
FROM abordagem a
LEFT JOIN cidade ci ON ci.id = a.cidade
WHERE a.status_avistamento IS NOT NULL
ORDER BY a.data_abordagem DESC
LIMIT 60";

$stmt_feed = $conn->prepare($sql_feed);
$stmt_feed->execute();
$avistamentos = $stmt_feed->fetchAll();

// Coordenadas aproximadas das principais cidades de SC (para o mapa)
$coords_sc = [
    'Florianópolis' => [-27.5954, -48.5480],
    'Joinville'     => [-26.3044, -48.8460],
    'Blumenau'      => [-26.9195, -49.0658],
    'São José'      => [-27.5951, -48.6225],
    'Criciúma'      => [-28.6773, -49.3697],
    'Chapecó'       => [-27.1004, -52.6151],
    'Itajaí'        => [-26.9078, -48.6619],
    'Lages'         => [-27.8157, -50.3256],
];

// Montar JSON de pins para o mapa
$pins = [];
foreach ($avistamentos as $av) {
    $lat = $av['latitude'] ?? null;
    $lng = $av['longitude'] ?? null;
    if (!$lat && !$lng && isset($coords_sc[$av['cidade_nome'] ?? ''])) {
        [$lat, $lng] = $coords_sc[$av['cidade_nome']];
        // Pequeno offset aleatório para não empilhar pins na mesma cidade
        $lat += (mt_rand(-50, 50) / 10000);
        $lng += (mt_rand(-50, 50) / 10000);
    }
    if (!$lat) continue;
    $pins[] = [
        'lat'    => $lat,
        'lng'    => $lng,
        'local'  => trim(($av['bairro'] ?? '') . ' ' . ($av['endereco'] ?? '')),
        'pessoas'=> $av['pessoas_count'] ?? 1,
        'tempo'  => $av['data_abordagem'] ? date('d/m H:i', strtotime($av['data_abordagem'])) : '',
        'status' => $av['status_avistamento'] ?? 'pendente',
    ];
}

// Buscar cidades para o select
$stmt_cidades = $conn->prepare("SELECT id, cidade FROM cidade ORDER BY cidade");
$stmt_cidades->execute();
$cidades = $stmt_cidades->fetchAll();

function badge(string $status): string {
    return match ($status) {
        'urgente'  => '<span class="badge badge-urg">urgente</span>',
        'atendido' => '<span class="badge badge-ok">✓ atendido</span>',
        default    => '<span class="badge badge-wait">pendente</span>',
    };
}

function tempo_relativo(?string $ts): string {
    if (!$ts) return '';
    $diff = time() - strtotime($ts);
    if ($diff < 60)    return 'agora mesmo';
    if ($diff < 3600)  return round($diff/60) . ' min atrás';
    if ($diff < 86400) return round($diff/3600) . 'h atrás';
    return date('d/m/Y', strtotime($ts));
}

include 'includes/header.php';
?>

<!-- ── Page header ───────────────────────────────────────────── -->
<div class="page-header">
    <div class="container">
        <div class="flex items-center justify-between gap-2">
            <div>
                <h1>Avistamentos na Rua</h1>
                <p class="sub">Registre e acompanhe situações de pessoas em situação de rua</p>
            </div>
            <a href="#tab-registrar" class="btn btn-primary" id="btn-abrir-registro">
                ＋ Registrar avistamento
            </a>
        </div>
    </div>
</div>

<?php if (isset($_SESSION['msg_avistamento'])): ?>
<div class="container mt-2">
    <div class="alert alert-success" data-dismiss="auto">
        <?= $_SESSION['msg_avistamento'] ?>
    </div>
</div>
<?php unset($_SESSION['msg_avistamento']); endif; ?>

<!-- ── Tabs ─────────────────────────────────────────────────── -->
<div class="avista-tabs">
    <div class="container" style="display:flex;gap:8px;padding:0">
        <button class="tab-btn active" data-tab="feed">≡ Feed</button>
        <button class="tab-btn"       data-tab="mapa">🗺 Mapa</button>
        <button class="tab-btn"       data-tab="registrar">＋ Registrar</button>
    </div>
</div>

<!-- ════════════════════════════════════════════════════════════
     TAB: FEED
     ════════════════════════════════════════════════════════════ -->
<div id="tab-feed" class="tab-panel active">
<div class="container" style="padding-top:20px;padding-bottom:80px">

    <!-- Filterbar -->
    <div class="filterbar">
        <button class="f active" data-filter="all">Todos</button>
        <button class="f" data-filter="urgentes">🔴 Urgentes</button>
        <button class="f" data-filter="pendentes">⏳ Pendentes</button>
        <button class="f" data-filter="atendidos">✓ Atendidos</button>
    </div>

    <?php if (empty($avistamentos)): ?>
        <div class="card text-center" style="padding:40px">
            <div style="font-size:2.5rem;margin-bottom:12px">📍</div>
            <p class="text-muted">Nenhum avistamento registrado ainda.</p>
            <button class="btn btn-primary mt-2" onclick="document.querySelector('[data-tab=registrar]').click()">
                ＋ Registrar o primeiro avistamento
            </button>
        </div>
    <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:12px" id="feed-list">
        <?php foreach ($avistamentos as $av):
            $needs = array_filter(array_map('trim', explode(',', $av['necessidades'] ?? '')));
            $local = trim(($av['bairro'] ?? '') . ($av['bairro'] && $av['endereco'] ? ' · ' : '') . ($av['endereco'] ?? ''));
            if (!$local) $local = $av['cidade_nome'] ?? 'Local não informado';
        ?>
        <div class="avista-card" data-status="<?= htmlspecialchars($av['status_avistamento'] ?? 'pendente') ?>">
            <div class="thumb" style="background:var(--paper-2);display:grid;place-items:center;font-size:1.5rem">
                📍
            </div>
            <div class="body">
                <div class="top">
                    <div class="loc"><?= htmlspecialchars($local) ?></div>
                    <?= badge($av['status_avistamento'] ?? 'pendente') ?>
                </div>
                <div class="meta">
                    <?= (int)($av['pessoas_count'] ?? 1) ?> pessoa(s)
                    · <?= tempo_relativo($av['data_abordagem']) ?>
                    <?php if ($av['contato_registrante']): ?>
                    · por <?= htmlspecialchars($av['contato_registrante']) ?>
                    <?php endif; ?>
                </div>
                <?php if ($needs): ?>
                <div class="needs mt-1">
                    <?php foreach ($needs as $n): ?>
                    <span class="need-tag"><?= htmlspecialchars($n) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <?php if ($av['relato']): ?>
                <p class="text-sm text-muted mt-1" style="margin:4px 0 0">
                    <?= htmlspecialchars(mb_substr($av['relato'], 0, 100)) . (mb_strlen($av['relato']) > 100 ? '…' : '') ?>
                </p>
                <?php endif; ?>
                <!-- Ações rápidas de status -->
                <div class="flex gap-1 mt-2" style="margin-top:8px">
                    <?php if (($av['status_avistamento'] ?? '') !== 'atendido'): ?>
                    <form method="post" action="avistamentos_processa.php" style="display:inline">
                        <input type="hidden" name="acao"   value="status">
                        <input type="hidden" name="id"     value="<?= (int)$av['id'] ?>">
                        <input type="hidden" name="status" value="atendido">
                        <button type="submit" class="btn btn-ghost btn-sm">✓ Marcar atendido</button>
                    </form>
                    <?php if (($av['status_avistamento'] ?? '') !== 'urgente'): ?>
                    <form method="post" action="avistamentos_processa.php" style="display:inline">
                        <input type="hidden" name="acao"   value="status">
                        <input type="hidden" name="id"     value="<?= (int)$av['id'] ?>">
                        <input type="hidden" name="status" value="urgente">
                        <button type="submit" class="btn btn-sm" style="color:var(--accent);border-color:var(--accent)">! Urgente</button>
                    </form>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>
</div><!-- /tab-feed -->

<!-- ════════════════════════════════════════════════════════════
     TAB: MAPA
     ════════════════════════════════════════════════════════════ -->
<div id="tab-mapa" class="tab-panel">
<div style="height:calc(100vh - 130px);position:relative">

    <div id="avista-map" style="height:100%;width:100%"></div>

    <!-- Botão minha localização -->
    <button id="btn-my-location" title="Minha localização" style="
        position:absolute;bottom:20px;right:20px;z-index:1000;
        background:var(--paper);border:2px solid var(--line);border-radius:var(--radius-sm);
        padding:8px 12px;font-size:1.2rem;cursor:pointer;box-shadow:var(--shadow);
    ">📍</button>

    <!-- Inline data for markers -->
    <script id="map-pins-data" type="application/json">
        <?= json_encode($pins, JSON_UNESCAPED_UNICODE) ?>
    </script>

</div>
</div><!-- /tab-mapa -->

<!-- ════════════════════════════════════════════════════════════
     TAB: REGISTRAR
     ════════════════════════════════════════════════════════════ -->
<div id="tab-registrar" class="tab-panel">
<div class="container-sm" style="padding-top:24px;padding-bottom:80px">

<div class="card">
    <h2 style="margin-bottom:18px">Novo avistamento</h2>

    <form method="post" action="avistamentos_processa.php" data-validate>
        <input type="hidden" name="acao" value="registrar">

        <!-- Localização -->
        <div class="form-group">
            <label class="form-label">Local do avistamento <span style="color:var(--danger)">*</span></label>
            <input type="text" name="local" class="form-control" required
                   placeholder="Ex.: Rua das Flores, 120 — Centro">
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Cidade <span style="color:var(--danger)">*</span></label>
                <select name="cidade" class="form-control" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($cidades as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['cidade']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Bairro</label>
                <input type="text" name="bairro" class="form-control" placeholder="Bairro">
            </div>
        </div>

        <!-- Pessoas -->
        <div class="form-group">
            <label class="form-label">Quantas pessoas no local?</label>
            <div class="counter">
                <button type="button" class="dec">−</button>
                <div class="val">1</div>
                <button type="button" class="inc">＋</button>
                <input type="hidden" name="pessoas_count" value="1">
            </div>
        </div>

        <!-- Necessidades -->
        <div class="form-group">
            <label class="form-label">O que precisam? <span class="text-muted text-sm">(selecione todos que se aplicam)</span></label>
            <div class="chips mt-1">
                <?php
                $needs_opts = ['frio','comida','água','saúde','criança','animal','abrigo','roupa','documentos'];
                foreach ($needs_opts as $n): ?>
                <label class="chip" data-toggle="chip">
                    <input type="checkbox" name="necessidades[]" value="<?= $n ?>">
                    <span class="dot"></span>
                    <?= ucfirst($n) ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Status inicial -->
        <div class="form-group">
            <label class="form-label">Urgência</label>
            <div style="display:flex;gap:10px">
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                    <input type="radio" name="status_avistamento" value="pendente" checked> Pendente
                </label>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
                    <input type="radio" name="status_avistamento" value="urgente">
                    <span style="color:var(--accent)">● Urgente</span>
                </label>
            </div>
        </div>

        <!-- Descrição -->
        <div class="form-group">
            <label class="form-label">Descrição <span class="text-muted text-sm">(opcional)</span></label>
            <textarea name="descricao" class="form-control" rows="3"
                      placeholder="Descreva brevemente a situação observada…"></textarea>
        </div>

        <!-- Contato do registrante -->
        <div class="form-group">
            <label class="form-label">Seu nome / contato <span class="text-muted text-sm">(opcional)</span></label>
            <input type="text" name="contato" class="form-control"
                   placeholder="Como podemos identificar você neste registro">
        </div>

        <!-- Geolocalização oculta -->
        <input type="hidden" name="latitude"  id="inp-lat">
        <input type="hidden" name="longitude" id="inp-lng">

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            <button type="button" id="btn-geolocate" class="btn btn-ghost">
                📍 Usar minha localização
            </button>
            <button type="submit" class="btn btn-primary btn-lg" style="flex:1">
                Publicar no feed →
            </button>
        </div>

    </form>
</div>

</div>
</div><!-- /tab-registrar -->

<!-- FAB no feed -->
<div id="tab-feed-fab" class="fab" onclick="document.querySelector('[data-tab=registrar]').click()">
    ＋ Registrar
</div>

<script>
// Geolocalização do formulário
document.getElementById('btn-geolocate')?.addEventListener('click', function() {
    if (!navigator.geolocation) return alert('Geolocalização não suportada.');
    navigator.geolocation.getCurrentPosition(pos => {
        document.getElementById('inp-lat').value = pos.coords.latitude;
        document.getElementById('inp-lng').value = pos.coords.longitude;
        this.textContent = '✓ Localização capturada';
        this.style.color = 'var(--ok)';
    }, () => alert('Não foi possível obter sua localização.'));
});

// Esconde FAB quando o feed não está ativo
document.querySelectorAll('.avista-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const fab = document.getElementById('tab-feed-fab');
        if (fab) fab.style.display = btn.dataset.tab === 'feed' ? 'flex' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
