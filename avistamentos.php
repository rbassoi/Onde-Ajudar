<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$page_title = 'Avistamentos — Moradores de Rua';
$extra_css  = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
    'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css',
    'https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css',
];
$extra_js   = [
    'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
    'https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js',
    'https://unpkg.com/leaflet.heat/dist/leaflet-heat.js',
];

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
    a.cidade AS cidade_id,
    ci.cidade AS cidade_nome,
    e.id AS estado_id,
    e.estado AS estado_nome
FROM abordagem a
LEFT JOIN cidade ci ON ci.id = a.cidade
LEFT JOIN estados e ON e.id = ci.estado_id
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

// Buscar estados para o select (cidades carregadas dinamicamente via AJAX)
$stmt_estados = $conn->prepare("SELECT id, estado FROM estados ORDER BY estado");
$stmt_estados->execute();
$estados = $stmt_estados->fetchAll();

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

    <!-- Filterbar status -->
    <div class="filterbar">
        <button class="f active" data-filter="all">Todos</button>
        <button class="f" data-filter="urgentes">🔴 Urgentes</button>
        <button class="f" data-filter="pendentes">⏳ Pendentes</button>
        <button class="f" data-filter="atendidos">✓ Atendidos</button>
    </div>

    <!-- Filtro de localização -->
    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:10px;align-items:center">
        <select id="fil-estado" class="form-control" style="width:auto;min-width:150px;height:36px;padding:4px 10px">
            <option value="">Todos os estados</option>
            <?php foreach ($estados as $e): ?>
            <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
            <?php endforeach; ?>
        </select>
        <select id="fil-cidade" class="form-control" style="width:auto;min-width:150px;height:36px;padding:4px 10px" disabled>
            <option value="">Todas as cidades</option>
        </select>
        <select id="fil-bairro" class="form-control" style="width:auto;min-width:140px;height:36px;padding:4px 10px" disabled>
            <option value="">Todos os bairros</option>
        </select>
        <button class="btn btn-ghost btn-sm" style="height:36px" onclick="limparFiltrosLocalizacao()">✕ Limpar</button>
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
        <div class="avista-card"
             data-status="<?= htmlspecialchars($av['status_avistamento'] ?? 'pendente') ?>"
             data-cidade-id="<?= (int)($av['cidade_id'] ?? 0) ?>"
             data-estado-id="<?= (int)($av['estado_id'] ?? 0) ?>"
             data-bairro="<?= htmlspecialchars(strtolower(trim($av['bairro'] ?? ''))) ?>">
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

    <!-- Controles do mapa -->
    <div style="position:absolute;bottom:20px;right:20px;z-index:1000;display:flex;flex-direction:column;gap:8px">
        <button id="btn-my-location" title="Centralizar na minha localização" style="
            background:var(--paper);border:2px solid var(--line);border-radius:var(--radius-sm);
            padding:8px 12px;font-size:1.1rem;cursor:pointer;box-shadow:var(--shadow);
        ">📍</button>
    </div>

    <button id="btn-toggle-heat" title="Alternar entre marcadores e mapa de calor" style="
        position:absolute;bottom:20px;left:20px;z-index:1000;
        background:var(--paper);border:2px solid var(--line);border-radius:var(--radius-sm);
        padding:8px 14px;font-size:.82rem;font-weight:600;cursor:pointer;box-shadow:var(--shadow);
        display:flex;align-items:center;gap:6px;
    ">🔥 Mapa de calor</button>

    <!-- Legenda -->
    <div style="position:absolute;top:12px;right:12px;z-index:1000;background:var(--paper);border:1px solid var(--line);border-radius:var(--radius-sm);padding:8px 12px;font-size:.78rem;box-shadow:var(--shadow)">
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px"><span style="width:10px;height:10px;border-radius:50%;background:#cf6a44;display:inline-block"></span> Urgente</div>
        <div style="display:flex;align-items:center;gap:6px;margin-bottom:4px"><span style="width:10px;height:10px;border-radius:50%;background:#c9a84c;display:inline-block"></span> Pendente</div>
        <div style="display:flex;align-items:center;gap:6px"><span style="width:10px;height:10px;border-radius:50%;background:#5a9e4a;display:inline-block"></span> Atendido</div>
    </div>

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

    <form method="post" action="avistamentos_processa.php" data-validate enctype="multipart/form-data">
        <input type="hidden" name="acao" value="registrar">

        <!-- Localização -->
        <div class="form-group">
            <label class="form-label">Local do avistamento <span style="color:var(--danger)">*</span></label>
            <div style="display:flex;gap:8px;align-items:center">
                <input type="text" name="local" id="inp-local" class="form-control" required
                       placeholder="Ex.: Rua das Flores, 120 — Centro" style="flex:1">
                <button type="button" id="btn-geolocate" class="btn btn-ghost" title="Usar minha localização" style="white-space:nowrap;flex-shrink:0">
                    📍 Minha localização
                </button>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Estado <span style="color:var(--danger)">*</span></label>
                <select name="estado_id" id="sel-estado-form" class="form-control" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($estados as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Cidade <span style="color:var(--danger)">*</span></label>
                <select name="cidade" id="sel-cidade-form" class="form-control" required disabled>
                    <option value="">Selecione o estado...</option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Bairro</label>
            <input type="text" name="bairro" id="inp-bairro-form" class="form-control"
                   placeholder="Bairro" list="lista-bairros-form" autocomplete="off">
            <datalist id="lista-bairros-form"></datalist>
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

        <!-- Foto -->
        <div class="form-group">
            <label class="form-label">Foto do local <span class="text-muted text-sm">(opcional)</span></label>
            <input type="file" name="foto" id="inp-foto" accept="image/*" capture="environment" style="display:none">
            <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                <button type="button" id="btn-camera" class="btn btn-ghost" style="gap:6px">
                    📷 Tirar foto
                </button>
                <button type="button" id="btn-upload" class="btn btn-ghost" style="gap:6px;font-size:.85rem">
                    📁 Escolher arquivo
                </button>
            </div>
            <div id="foto-preview" style="display:none;margin-top:10px;position:relative;width:fit-content">
                <img id="foto-preview-img" src="" alt="Prévia" style="max-width:100%;max-height:200px;border-radius:8px;border:2px solid var(--line)">
                <button type="button" id="btn-remove-foto" title="Remover foto" style="
                    position:absolute;top:-8px;right:-8px;background:var(--accent);color:#fff;
                    border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;
                    font-size:14px;line-height:24px;text-align:center;padding:0
                ">×</button>
            </div>
        </div>

        <!-- Geolocalização oculta -->
        <input type="hidden" name="latitude"  id="inp-lat">
        <input type="hidden" name="longitude" id="inp-lng">

        <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:8px">
            <button type="submit" class="btn btn-primary btn-lg" style="flex:1">
                Publicar no feed →
            </button>
        </div>

    </form>
</div>

</div>
</div><!-- /tab-registrar -->

<!-- Modal: aviso de localização não verificada -->
<div id="modal-loc" role="dialog" aria-modal="true" style="
    display:none;position:fixed;inset:0;z-index:9000;
    background:rgba(44,38,32,.6);
    align-items:center;justify-content:center;padding:20px;
">
    <div class="card" style="max-width:440px;width:100%;padding:32px 28px;text-align:center">
        <div style="font-size:2.8rem;margin-bottom:8px">⚠️</div>
        <h3 style="margin-bottom:10px;font-size:1.15rem">Localização não verificada</h3>
        <p style="color:var(--muted);line-height:1.6;margin-bottom:24px;font-size:.93rem">
            Não conseguimos confirmar que você está <strong style="color:var(--ink)">próximo ao local informado</strong>.<br><br>
            Registros falsos ou imprecisos podem <strong style="color:var(--accent)">desviar recursos</strong> e dificultar o atendimento por autoridades e voluntários a quem realmente precisa.
        </p>
        <div style="display:flex;flex-direction:column;gap:10px">
            <button id="modal-loc-confirmar" class="btn btn-primary">Estou próximo — confirmar registro</button>
            <button id="modal-loc-cancelar"  class="btn btn-ghost" style="font-size:.85rem">
                ← Cancelar e usar 📍 Minha localização
            </button>
        </div>
    </div>
</div>

<!-- FAB no feed -->
<div id="tab-feed-fab" class="fab" onclick="document.querySelector('[data-tab=registrar]').click()">
    ＋ Registrar
</div>

<script>
/* ── Helpers ─────────────────────────────────────────────── */
function normStr(s) {
    return String(s).toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '').trim();
}
function getStatusFilter() {
    const btn = document.querySelector('.filterbar .f.active');
    return btn ? btn.dataset.filter : 'all';
}

/* ── AJAX: cidades ───────────────────────────────────────── */
async function carregarCidades(estadoId, selectId, placeholder) {
    const sel = document.getElementById(selectId);
    if (!sel) return [];
    sel.disabled = true;
    sel.innerHTML = '<option value="">Carregando...</option>';
    try {
        const r = await fetch('busca_localidade.php?acao=cidades&estado_id=' + estadoId);
        const cidades = await r.json();
        sel.innerHTML = '<option value="">' + (placeholder || 'Selecione...') + '</option>';
        cidades.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.cidade;
            sel.appendChild(opt);
        });
        sel.disabled = false;
        return cidades;
    } catch(e) {
        sel.innerHTML = '<option value="">' + (placeholder || 'Selecione...') + '</option>';
        sel.disabled = false;
        return [];
    }
}

/* ── AJAX: bairros (datalist do formulário) ─────────────── */
async function carregarBairros(cidadeId, datalistId) {
    const dl = document.getElementById(datalistId);
    if (!dl || !cidadeId) return;
    dl.innerHTML = '';
    try {
        const r = await fetch('busca_localidade.php?acao=bairros&cidade_id=' + cidadeId);
        const bairros = await r.json();
        bairros.forEach(b => { const o = document.createElement('option'); o.value = b; dl.appendChild(o); });
    } catch(e) {}
}

/* ── AJAX: bairros (select do filtro) ───────────────────── */
async function carregarBairrosFilter(cidadeId) {
    const sel = document.getElementById('fil-bairro');
    if (!sel) return;
    sel.disabled = true;
    sel.innerHTML = '<option value="">Todos os bairros</option>';
    if (!cidadeId) return;
    try {
        const r = await fetch('busca_localidade.php?acao=bairros&cidade_id=' + cidadeId);
        const bairros = await r.json();
        bairros.forEach(b => {
            const o = document.createElement('option');
            o.value = b.toLowerCase(); o.textContent = b; sel.appendChild(o);
        });
        sel.disabled = bairros.length === 0;
    } catch(e) {}
}

/* ── Geolocalização ──────────────────────────────────────── */
document.getElementById('btn-geolocate')?.addEventListener('click', async function() {
    if (!navigator.geolocation) return alert('Geolocalização não suportada.');
    this.textContent = '⏳ Buscando…';
    this.disabled = true;
    const btn = this;
    try {
        const pos = await new Promise((res, rej) => navigator.geolocation.getCurrentPosition(res, rej));
        const { latitude: lat, longitude: lng } = pos.coords;
        document.getElementById('inp-lat').value = lat;
        document.getElementById('inp-lng').value = lng;

        const resp = await fetch(
            'https://nominatim.openstreetmap.org/reverse?lat=' + lat +
            '&lon=' + lng + '&format=json&accept-language=pt-BR'
        );
        const data = await resp.json();
        const addr = data.address || {};

        // Endereço na rua
        const partsRua = [addr.road, addr.house_number].filter(Boolean);
        if (partsRua.length) document.getElementById('inp-local').value = partsRua.join(', ');

        // Estado
        const estadoNome = addr.state || '';
        const selEstado = document.getElementById('sel-estado-form');
        if (selEstado && estadoNome) {
            const optEstado = Array.from(selEstado.options)
                .find(o => normStr(o.text) === normStr(estadoNome));
            if (optEstado) {
                selEstado.value = optEstado.value;
                await carregarCidades(optEstado.value, 'sel-cidade-form', 'Selecione a cidade...');
                // Cidade
                const cidNome = addr.city || addr.town || addr.village || addr.county || '';
                const selCidade = document.getElementById('sel-cidade-form');
                if (cidNome && selCidade) {
                    const optCid = Array.from(selCidade.options)
                        .find(o => normStr(o.text) === normStr(cidNome));
                    if (optCid) {
                        selCidade.value = optCid.value;
                        await carregarBairros(optCid.value, 'lista-bairros-form');
                    }
                }
            }
        }
        // Bairro
        const bairroNome = addr.suburb || addr.neighbourhood || addr.residential || '';
        const inpBairro = document.getElementById('inp-bairro-form');
        if (bairroNome && inpBairro) inpBairro.value = bairroNome;

        btn.textContent = '✓ Localização capturada';
        btn.style.color = 'var(--ok)';
    } catch(e) {
        if (e && e.code !== undefined) alert('Não foi possível obter sua localização.');
        btn.textContent = '📍 Minha localização';
    }
    btn.disabled = false;
});

// Foto — câmera e upload
(function() {
    const inpFoto    = document.getElementById('inp-foto');
    const btnCamera  = document.getElementById('btn-camera');
    const btnUpload  = document.getElementById('btn-upload');
    const preview    = document.getElementById('foto-preview');
    const previewImg = document.getElementById('foto-preview-img');
    const btnRemove  = document.getElementById('btn-remove-foto');

    function showPreview(file) {
        if (!file) return;
        const url = URL.createObjectURL(file);
        previewImg.src = url;
        preview.style.display = 'block';
    }

    // Câmera: mantém capture="environment" (câmera traseira no celular)
    btnCamera?.addEventListener('click', () => {
        inpFoto.setAttribute('capture', 'environment');
        inpFoto.click();
    });

    // Upload: remove o atributo capture para abrir o seletor de arquivos
    btnUpload?.addEventListener('click', () => {
        inpFoto.removeAttribute('capture');
        inpFoto.click();
    });

    inpFoto?.addEventListener('change', () => {
        const file = inpFoto.files[0];
        if (file) showPreview(file);
    });

    btnRemove?.addEventListener('click', () => {
        inpFoto.value = '';
        previewImg.src = '';
        preview.style.display = 'none';
    });
})();

/* ── Cascades: formulário ────────────────────────────────── */
document.getElementById('sel-estado-form')?.addEventListener('change', function() {
    const selCidade = document.getElementById('sel-cidade-form');
    const dl  = document.getElementById('lista-bairros-form');
    const inp = document.getElementById('inp-bairro-form');
    if (dl)  dl.innerHTML = '';
    if (inp) inp.value = '';
    if (selCidade) { selCidade.innerHTML = '<option value="">Selecione a cidade...</option>'; selCidade.disabled = true; }
    if (this.value) carregarCidades(this.value, 'sel-cidade-form', 'Selecione a cidade...');
});

document.getElementById('sel-cidade-form')?.addEventListener('change', function() {
    const dl  = document.getElementById('lista-bairros-form');
    const inp = document.getElementById('inp-bairro-form');
    if (dl)  dl.innerHTML = '';
    if (inp) inp.value = '';
    if (this.value) carregarBairros(this.value, 'lista-bairros-form');
});

/* ── Cascades: filtro do feed ────────────────────────────── */
document.getElementById('fil-estado')?.addEventListener('change', async function() {
    const filCidade = document.getElementById('fil-cidade');
    const filBairro = document.getElementById('fil-bairro');
    if (filBairro) { filBairro.innerHTML = '<option value="">Todos os bairros</option>'; filBairro.disabled = true; }
    if (filCidade) { filCidade.innerHTML = '<option value="">Todas as cidades</option>'; filCidade.disabled = true; }
    if (this.value) await carregarCidades(this.value, 'fil-cidade', 'Todas as cidades');
    if (typeof filterCards === 'function') filterCards(getStatusFilter());
});

document.getElementById('fil-cidade')?.addEventListener('change', async function() {
    await carregarBairrosFilter(this.value);
    if (typeof filterCards === 'function') filterCards(getStatusFilter());
});

document.getElementById('fil-bairro')?.addEventListener('change', function() {
    if (typeof filterCards === 'function') filterCards(getStatusFilter());
});

function limparFiltrosLocalizacao() {
    const filEstado = document.getElementById('fil-estado');
    const filCidade = document.getElementById('fil-cidade');
    const filBairro = document.getElementById('fil-bairro');
    if (filEstado) filEstado.value = '';
    if (filCidade) { filCidade.innerHTML = '<option value="">Todas as cidades</option>'; filCidade.disabled = true; }
    if (filBairro) { filBairro.innerHTML = '<option value="">Todos os bairros</option>'; filBairro.disabled = true; }
    if (typeof filterCards === 'function') filterCards(getStatusFilter());
}

/* ── Modal de confirmação de localização ─────────────────── */
(function() {
    const form    = document.querySelector('#tab-registrar form[data-validate]');
    const modal   = document.getElementById('modal-loc');
    if (!form || !modal) return;

    let _confirmar = false;

    form.addEventListener('submit', function(e) {
        if (_confirmar) return; // já confirmou pelo modal

        // Só mostra o modal se os campos obrigatórios estiverem preenchidos
        // (deixa a validação nativa do HTML5 agir primeiro se não estiverem)
        const algumVazio = Array.from(form.querySelectorAll('[required]'))
            .some(el => !el.disabled && !el.value.trim());
        if (algumVazio) return;

        const lat = document.getElementById('inp-lat').value;
        const lng = document.getElementById('inp-lng').value;
        if (lat && lng) return; // localização verificada — ok

        e.preventDefault();
        modal.style.display = 'flex';
    });

    document.getElementById('modal-loc-confirmar').addEventListener('click', () => {
        _confirmar = true;
        modal.style.display = 'none';
        form.submit(); // validação já passou; envia direto
    });

    document.getElementById('modal-loc-cancelar').addEventListener('click', () => {
        modal.style.display = 'none';
        // Rola até o botão de geolocalização para facilitar
        document.getElementById('btn-geolocate')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
    });

    // Fecha ao clicar fora do card
    modal.addEventListener('click', e => {
        if (e.target === modal) modal.style.display = 'none';
    });
})();

/* ── Botão "Registrar avistamento" no cabeçalho ──────────── */
document.getElementById('btn-abrir-registro')?.addEventListener('click', function(e) {
    e.preventDefault();
    document.querySelector('.avista-tabs .tab-btn[data-tab="registrar"]')?.click();
});

/* ── FAB ─────────────────────────────────────────────────── */
document.querySelectorAll('.avista-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        const fab = document.getElementById('tab-feed-fab');
        if (fab) fab.style.display = btn.dataset.tab === 'feed' ? 'flex' : 'none';
    });
});
</script>

<?php include 'includes/footer.php'; ?>
