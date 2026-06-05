<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$page_title = 'Animais Desaparecidos — Onde Ajudar';
$extra_css  = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.css'];
$extra_js   = ['https://unpkg.com/leaflet@1.9.4/dist/leaflet.js'];

$aba = $_GET['aba'] ?? 'registrar';

$stmt = $conn->query(
    "SELECT * FROM animais_desaparecidos ORDER BY criado_em DESC LIMIT 100"
);
$registros = $stmt->fetchAll();

$stmt_estados = $conn->prepare("SELECT id, estado FROM estados ORDER BY estado");
$stmt_estados->execute();
$estados = $stmt_estados->fetchAll();

require_once('includes/header.php');

$especies_emoji = [
    'cachorro' => '🐕', 'gato' => '🐈', 'passaro' => '🐦',
    'coelho'   => '🐇', 'hamster' => '🐹', 'reptil' => '🦎',
    'outro'    => '🐾',
];
?>

<style>
.anim-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 32px 20px 60px;
}

/* Tabs */
.anim-tabs {
    display: flex;
    gap: 4px;
    background: var(--paper-2);
    border: 2px solid var(--line);
    border-radius: var(--radius);
    padding: 4px;
    margin-bottom: 32px;
    width: fit-content;
}
.anim-tabs .tab-btn {
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
.anim-tabs .tab-btn.active {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 2px 8px rgba(207,106,68,.3);
}
.anim-tab-panel { display: none; }
.anim-tab-panel.active { display: block; }

/* Form */
.anim-form-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    padding: 32px 28px;
    max-width: 660px;
}
.anim-form-card h2 {
    font-family: var(--hand);
    font-size: 1.5rem;
    margin-bottom: 4px;
}
.anim-form-card .sub {
    color: var(--muted);
    font-size: .9rem;
    margin-bottom: 28px;
}
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
@media (max-width: 560px) {
    .form-row-2, .form-row-3 { grid-template-columns: 1fr; }
    .anim-form-card { padding: 24px 16px; }
}

/* Foto upload */
.foto-area {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
    margin-top: 4px;
}
.foto-btn {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 22px 12px;
    background: var(--paper-2);
    border: 2px dashed var(--line);
    border-radius: var(--radius);
    font-family: var(--hand);
    font-size: 1rem;
    color: var(--ink);
    cursor: pointer;
    transition: border-color .15s, background .15s;
    min-height: 100px;
}
.foto-btn:hover { border-color: var(--accent); background: var(--accent-soft); }
.foto-btn .foto-icon { font-size: 2rem; line-height: 1; }

/* Feed cards */
.anim-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(270px, 1fr));
    gap: 18px;
}
.anim-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: box-shadow .15s, transform .12s;
}
.anim-card:hover { box-shadow: var(--shadow-lg); transform: translateY(-3px); }
.anim-card-foto {
    width: 100%; height: 180px; object-fit: cover; display: block; background: var(--board);
}
.anim-card-no-foto {
    width: 100%; height: 180px; background: var(--board);
    display: flex; align-items: center; justify-content: center;
    font-size: 4rem; color: var(--muted);
}
.anim-card-body { padding: 16px; }
.anim-card-body h3 { font-family: var(--hand); font-size: 1.15rem; margin-bottom: 6px; }
.anim-card-meta { font-size: 13px; color: var(--muted); line-height: 1.7; }
.anim-card-meta strong { color: var(--ink); }
.badge-perdido   { background: var(--warn-soft);   color: var(--warn);   padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-encontrado{ background: var(--ok-soft);     color: var(--ok);     padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-recompensa{ background: #fff3d0; color: #a07000; padding: 2px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }

/* Mapa */
.map-wrapper {
    position: relative;
    overflow: hidden;
    width: 100%;
    height: 420px;
    border-radius: var(--radius-lg);
    border: 2px solid var(--line);
    box-sizing: border-box;
}
#anim-map { position: absolute; inset: 0; width: 100%; height: 100%; }
.map-list { display: flex; flex-direction: column; gap: 6px; margin-top: 14px; }
.map-list-item {
    display: flex; align-items: center; gap: 12px; padding: 11px 16px;
    background: var(--paper); border: 2px solid var(--line);
    border-radius: var(--radius); cursor: pointer;
    transition: border-color .15s, box-shadow .15s, background .15s;
    text-align: left; width: 100%;
}
.map-list-item:hover  { border-color: var(--accent); box-shadow: 0 2px 8px rgba(207,106,68,.2); }
.map-list-item.active { border-color: var(--accent); background: var(--accent-soft); }
.map-list-dot { width: 18px; height: 18px; border-radius: 50%; flex-shrink: 0; border: 2.5px solid rgba(0,0,0,.2); }
.map-list-info { flex: 1; min-width: 0; }
.map-list-info strong { display: block; font-size: 14px; }
.map-list-info span   { font-size: 12px; color: var(--muted); }

.empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
.empty-state .emoji { font-size: 3rem; margin-bottom: 12px; }
</style>

<div class="anim-wrap">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
        <a href="home.php" style="color:var(--muted);font-size:13px">← Início</a>
        <span style="color:var(--line)">·</span>
        <h1 style="margin:0;font-size:1.6rem">🐾 Animais Desaparecidos</h1>
    </div>

    <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" data-dismiss="auto" style="margin-bottom:20px">
        Registro enviado com sucesso. Esperamos que seu animal seja encontrado logo!
    </div>
    <?php endif; ?>

    <div class="anim-tabs">
        <button class="tab-btn <?= $aba === 'registrar' || $aba === '' ? 'active' : '' ?>" data-tab="registrar">＋ Registrar animal</button>
        <button class="tab-btn <?= $aba === 'feed' ? 'active' : '' ?>"                     data-tab="feed">Feed (<?= count($registros) ?>)</button>
        <button class="tab-btn <?= $aba === 'mapa' ? 'active' : '' ?>"                     data-tab="mapa">🗺 Mapa</button>
    </div>

    <!-- ── Aba: Registrar ─────────────────────────────────────── -->
    <div class="anim-tab-panel <?= $aba === 'registrar' || $aba === '' ? 'active' : '' ?>" id="tab-registrar">
        <div class="anim-form-card">
            <h2>Registrar animal desaparecido</h2>
            <p class="sub">Quanto mais informações, maior a chance de reencontro.</p>

            <form method="post" action="animais_desaparecidos_processa.php" enctype="multipart/form-data">
                <input type="hidden" name="latitude"  id="inp-lat" value="">
                <input type="hidden" name="longitude" id="inp-lng" value="">

                <!-- Espécie + Raça + Nome -->
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Espécie *</label>
                        <select name="especie" class="form-control" required>
                            <option value="">Selecione...</option>
                            <option value="cachorro">🐕 Cachorro</option>
                            <option value="gato">🐈 Gato</option>
                            <option value="passaro">🐦 Pássaro</option>
                            <option value="coelho">🐇 Coelho</option>
                            <option value="hamster">🐹 Hamster/Roedor</option>
                            <option value="reptil">🦎 Réptil</option>
                            <option value="outro">🐾 Outro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Raça</label>
                        <input type="text" name="raca" class="form-control"
                               placeholder="Ex: Labrador, Vira-lata" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Nome do animal</label>
                        <input type="text" name="nome_animal" class="form-control"
                               placeholder="Ex: Rex, Bolinha" maxlength="100">
                    </div>
                </div>

                <!-- Cor + Porte + Sexo -->
                <div class="form-row-3">
                    <div class="form-group">
                        <label class="form-label">Cor / pelagem *</label>
                        <input type="text" name="cor" class="form-control" required
                               placeholder="Ex: Caramelo, preto e branco" maxlength="100">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Porte</label>
                        <select name="porte" class="form-control">
                            <option value="">Não informado</option>
                            <option value="pequeno">Pequeno</option>
                            <option value="medio">Médio</option>
                            <option value="grande">Grande</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sexo</label>
                        <select name="sexo" class="form-control">
                            <option value="desconhecido">Desconhecido</option>
                            <option value="macho">Macho</option>
                            <option value="femea">Fêmea</option>
                        </select>
                    </div>
                </div>

                <!-- Último local visto — estruturado -->
                <div class="form-group">
                    <label class="form-label">Rua / Logradouro *</label>
                    <input type="text" name="rua" id="inp-rua-anim" class="form-control" required maxlength="200"
                           placeholder="Ex: Rua das Flores, 200">
                </div>
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Estado *</label>
                        <select name="estado_id" id="sel-estado-anim" class="form-control" required>
                            <option value="">Selecione...</option>
                            <?php foreach ($estados as $e): ?>
                            <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['estado']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Cidade *</label>
                        <select name="cidade_id" id="sel-cidade-anim" class="form-control" required disabled>
                            <option value="">Selecione o estado...</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" id="inp-bairro-anim" class="form-control"
                           placeholder="Bairro" list="lista-bairros-anim" autocomplete="off">
                    <datalist id="lista-bairros-anim"></datalist>
                    <div style="margin-top:8px">
                        <button type="button" id="btn-geolocate-anim" class="btn btn-ghost"
                                style="font-size:13px;padding:6px 14px">
                            📍 Minha localização
                        </button>
                        <span id="geocode-status-anim" style="font-size:12px;margin-left:8px;color:var(--muted)"></span>
                    </div>
                </div>

                <!-- Telefone + Recompensa -->
                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Telefone de contato *</label>
                        <input type="tel" name="telefone_contato" class="form-control" required maxlength="30"
                               placeholder="(11) 9 9999-9999">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Recompensa (R$) <span style="font-size:11px;color:var(--muted)">opcional</span></label>
                        <input type="number" name="recompensa" class="form-control" step="0.01" min="0"
                               placeholder="0.00">
                    </div>
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <label class="form-label">Descrição <span style="font-size:11px;color:var(--muted)">opcional</span></label>
                    <textarea name="descricao" class="form-control" rows="3"
                              placeholder="Sinais particulares, coleira, tags, comportamento..."></textarea>
                </div>

                <!-- Foto -->
                <div class="form-group">
                    <label class="form-label">Foto do animal <span style="font-size:11px;color:var(--muted)">opcional</span></label>
                    <input type="file" name="foto" id="inp-foto-anim" accept="image/*" capture="environment" style="display:none">
                    <div class="foto-area">
                        <button type="button" id="btn-camera-anim" class="foto-btn">
                            <span class="foto-icon">📷</span>
                            Tirar foto
                        </button>
                        <button type="button" id="btn-upload-anim" class="foto-btn">
                            <span class="foto-icon">📁</span>
                            Galeria
                        </button>
                    </div>
                    <div id="foto-preview-anim" style="display:none;margin-top:12px;position:relative;width:fit-content">
                        <img id="foto-preview-img-anim" src="" alt="Prévia"
                             style="max-width:100%;max-height:220px;border-radius:10px;border:2px solid var(--line)">
                        <button type="button" id="btn-remove-foto-anim" title="Remover foto"
                                style="position:absolute;top:-10px;right:-10px;background:var(--accent);color:#fff;
                                       border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;
                                       font-size:16px;line-height:28px;text-align:center;padding:0">×</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="font-size:1.1rem;padding:14px">
                    Registrar animal desaparecido →
                </button>
            </form>
        </div>
    </div>

    <!-- ── Aba: Feed ──────────────────────────────────────────── -->
    <div class="anim-tab-panel <?= $aba === 'feed' ? 'active' : '' ?>" id="tab-feed">
        <?php if (empty($registros)): ?>
        <div class="empty-state">
            <div class="emoji">🐾</div>
            <p style="font-family:var(--hand);font-size:1.2rem;margin-bottom:4px">Nenhum animal registrado ainda</p>
            <p>Seja o primeiro a registrar um animal desaparecido.</p>
        </div>
        <?php else: ?>
        <div class="anim-grid">
            <?php foreach ($registros as $r):
                $emoji = $especies_emoji[$r['especie']] ?? '🐾';
                $sexo_label = ['macho' => '♂ Macho', 'femea' => '♀ Fêmea', 'desconhecido' => ''][$r['sexo']] ?? '';
                $porte_label = ['pequeno' => 'Pequeno', 'medio' => 'Médio', 'grande' => 'Grande'][$r['porte'] ?? ''] ?? '';
            ?>
            <div class="anim-card">
                <?php if ($r['foto']): ?>
                <img class="anim-card-foto"
                     src="uploads/animais/<?= htmlspecialchars($r['foto']) ?>"
                     alt="Foto do animal">
                <?php else: ?>
                <div class="anim-card-no-foto"><?= $emoji ?></div>
                <?php endif; ?>

                <div class="anim-card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                        <h3>
                            <?= $emoji ?> <?= $r['nome_animal'] ? htmlspecialchars($r['nome_animal']) : ucfirst($r['especie']) ?>
                        </h3>
                        <div style="display:flex;gap:6px;flex-wrap:wrap">
                            <?php if ($r['recompensa']): ?>
                            <span class="badge-recompensa">💰 R$ <?= number_format($r['recompensa'], 2, ',', '.') ?></span>
                            <?php endif; ?>
                            <span class="badge-<?= $r['status'] ?>">
                                <?= $r['status'] === 'encontrado' ? '✓ Encontrado' : '⚠ Perdido' ?>
                            </span>
                        </div>
                    </div>

                    <div class="anim-card-meta">
                        <?php if ($r['raca']): ?>
                        <div><strong>Raça:</strong> <?= htmlspecialchars($r['raca']) ?></div>
                        <?php endif; ?>
                        <div><strong>Cor:</strong> <?= htmlspecialchars($r['cor']) ?></div>
                        <?php if ($porte_label || $sexo_label): ?>
                        <div>
                            <?= $porte_label ? '<strong>Porte:</strong> ' . $porte_label : '' ?>
                            <?= ($porte_label && $sexo_label) ? ' &nbsp;·&nbsp; ' : '' ?>
                            <?= $sexo_label ?>
                        </div>
                        <?php endif; ?>
                        <div><strong>Último local:</strong> <?= htmlspecialchars($r['ultimo_local']) ?></div>
                        <div><strong>Contato:</strong> <?= htmlspecialchars($r['telefone_contato']) ?></div>
                        <?php if ($r['descricao']): ?>
                        <div style="margin-top:6px;color:var(--ink)"><?= nl2br(htmlspecialchars($r['descricao'])) ?></div>
                        <?php endif; ?>
                        <div style="margin-top:6px;font-size:11px;color:var(--muted)">
                            <?= date('d/m/Y', strtotime($r['criado_em'])) ?>
                            <?php if ($r['latitude'] && $r['longitude']): ?>
                            &nbsp;·&nbsp;
                            <button type="button" class="rest-addr-link"
                                    onclick="focusAnimal(<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>, <?= $r['id'] ?>)"
                                    style="background:none;border:none;padding:0;color:var(--accent);font-size:11px;cursor:pointer;text-decoration:underline">
                                📍 Ver no mapa
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Aba: Mapa ──────────────────────────────────────────── -->
    <div class="anim-tab-panel <?= $aba === 'mapa' ? 'active' : '' ?>" id="tab-mapa">
        <?php
        $com_coords = array_filter($registros, fn($r) => $r['latitude'] && $r['longitude']);
        ?>
        <?php if (empty($com_coords)): ?>
        <div class="empty-state">
            <div class="emoji">🗺</div>
            <p style="font-family:var(--hand);font-size:1.2rem;margin-bottom:4px">Nenhum pin no mapa ainda</p>
            <p style="font-size:.9rem">Ao registrar um animal, clique em <strong>"Marcar localização no mapa"</strong> para que ele apareça aqui.</p>
        </div>
        <?php else: ?>
        <div class="map-wrapper"><div id="anim-map"></div></div>
        <div class="map-list">
            <?php foreach ($com_coords as $r):
                $emoji = $especies_emoji[$r['especie']] ?? '🐾';
                $cor_pin = $r['status'] === 'encontrado' ? '#4a8c5c' : '#c4952a';
            ?>
            <button type="button" class="map-list-item"
                    onclick="focusAnimal(<?= (float)$r['latitude'] ?>, <?= (float)$r['longitude'] ?>, <?= $r['id'] ?>)"
                    id="mli-<?= $r['id'] ?>">
                <span class="map-list-dot" style="background:<?= $cor_pin ?>"></span>
                <span class="map-list-info">
                    <strong><?= $emoji ?> <?= $r['nome_animal'] ? htmlspecialchars($r['nome_animal']) : ucfirst($r['especie']) ?></strong>
                    <span><?= htmlspecialchars($r['ultimo_local']) ?></span>
                </span>
                <span style="font-size:12px;color:var(--muted)">📍 Ver</span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// ── Tabs ──────────────────────────────────────────────────────────────────────
document.querySelectorAll('.anim-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.anim-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.anim-tab-panel').forEach(p => p.classList.remove('active'));
        const panel = document.getElementById('tab-' + btn.dataset.tab);
        if (panel) panel.classList.add('active');

        if (btn.dataset.tab === 'mapa') {
            requestAnimationFrame(() => requestAnimationFrame(() => {
                if (!_mapReady) initAnimMap();
                else _map?.invalidateSize({ animate: false });
            }));
        }

        const url = new URL(window.location);
        url.searchParams.set('aba', btn.dataset.tab);
        history.replaceState(null, '', url);
    });
});

if (document.getElementById('tab-mapa')?.classList.contains('active')) {
    requestAnimationFrame(() => requestAnimationFrame(initAnimMap));
}

// ── Dados ─────────────────────────────────────────────────────────────────────
const _pins = <?= json_encode(array_values(array_filter(array_map(fn($r) => (!$r['latitude'] || !$r['longitude']) ? null : [
    'id'      => $r['id'],
    'especie' => $r['especie'],
    'nome'    => $r['nome_animal'] ?: ucfirst($r['especie']),
    'local'   => $r['ultimo_local'],
    'cor'     => $r['cor'],
    'porte'   => $r['porte'],
    'sexo'    => $r['sexo'],
    'telefone'=> $r['telefone_contato'],
    'recomp'  => $r['recompensa'],
    'status'  => $r['status'],
    'lat'     => (float)$r['latitude'],
    'lng'     => (float)$r['longitude'],
], $registros)))) ?>;

const _emojis = { cachorro:'🐕', gato:'🐈', passaro:'🐦', coelho:'🐇', hamster:'🐹', reptil:'🦎', outro:'🐾' };
let _map = null, _markers = {}, _mapReady = false;

// ── Mapa Leaflet ──────────────────────────────────────────────────────────────
function initAnimMap() {
    if (typeof L === 'undefined' || _mapReady || _pins.length === 0) return;
    _mapReady = true;

    _map = L.map('anim-map', { zoomControl: true });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        maxZoom: 18,
    }).addTo(_map);

    const bounds = [];

    _pins.forEach(p => {
        const emoji = _emojis[p.especie] || '🐾';
        const color = p.status === 'encontrado' ? '#4a8c5c' : '#c4952a';

        const pinHtml = `
            <div style="position:relative;width:36px;height:48px">
                <div style="position:absolute;top:0;left:0;width:36px;height:36px;border-radius:50% 50% 50% 0;
                    transform:rotate(-45deg);background:${color};border:3px solid #fff;
                    box-shadow:0 2px 8px rgba(0,0,0,.35)"></div>
                <div style="position:absolute;top:4px;left:4px;width:28px;height:28px;border-radius:50%;
                    display:flex;align-items:center;justify-content:center;font-size:16px;line-height:1">
                    ${emoji}
                </div>
                <div style="position:absolute;bottom:0;left:50%;transform:translateX(-50%);
                    width:0;height:0;border-left:7px solid transparent;border-right:7px solid transparent;
                    border-top:12px solid ${color}"></div>
            </div>`;

        const icon = L.divIcon({ className:'', html:pinHtml, iconSize:[36,48], iconAnchor:[18,48], popupAnchor:[0,-50] });

        const statusBadge = p.status === 'encontrado'
            ? '<span style="background:#d9e7d2;color:#4a8c5c;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">✓ Encontrado</span>'
            : '<span style="background:#fef3d0;color:#c4952a;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">⚠ Perdido</span>';

        const sexoLabel = { macho:'♂ Macho', femea:'♀ Fêmea', desconhecido:'' }[p.sexo] || '';
        const porteLabel = { pequeno:'Pequeno', medio:'Médio', grande:'Grande' }[p.porte] || '';
        const recompLabel = p.recomp ? `<div style="margin-top:4px"><span style="background:#fff3d0;color:#a07000;border-radius:10px;padding:2px 8px;font-size:11px;font-weight:600">💰 Recompensa R$ ${parseFloat(p.recomp).toFixed(2).replace('.',',')}</span></div>` : '';
        const mapsUrl = `https://www.google.com/maps/search/?api=1&query=${encodeURIComponent(p.local)}`;

        const popup = `
            <div style="font-family:Inter,sans-serif;min-width:210px;max-width:250px">
                <strong style="font-size:14px;display:block;margin-bottom:4px">${emoji} ${escHtml(p.nome)}</strong>
                <div style="margin-bottom:6px">${statusBadge}${recompLabel}</div>
                <div style="font-size:12px;color:#8d8579;margin-bottom:6px">
                    ${p.cor ? '<div><b>Cor:</b> '+escHtml(p.cor)+'</div>' : ''}
                    ${porteLabel ? '<div><b>Porte:</b> '+porteLabel+'</div>' : ''}
                    ${sexoLabel ? '<div>'+sexoLabel+'</div>' : ''}
                    <div><b>Local:</b> ${escHtml(p.local)}</div>
                    <div><b>Contato:</b> ${escHtml(p.telefone)}</div>
                </div>
                <a href="${mapsUrl}" target="_blank" rel="noopener"
                   style="font-size:12px;color:#cf6a44;text-decoration:underline">
                   🗺 Ver no Google Maps →
                </a>
            </div>`;

        _markers[p.id] = L.marker([p.lat, p.lng], { icon })
            .bindPopup(popup, { maxWidth: 270, offset: [0, -10] })
            .addTo(_map);

        bounds.push([p.lat, p.lng]);
    });

    if (bounds.length > 0) {
        _map.fitBounds(L.latLngBounds(bounds).pad(0.25));
    } else {
        _map.setView([-19.92, -43.94], 12);
    }
}

function focusAnimal(lat, lng, id) {
    const mapaBtn = document.querySelector('.anim-tabs .tab-btn[data-tab="mapa"]');
    const mapaTab = document.getElementById('tab-mapa');

    if (!mapaTab?.classList.contains('active')) {
        mapaBtn?.click();
    }

    const doFocus = () => {
        if (!_map) { setTimeout(doFocus, 80); return; }
        _map.setView([lat, lng], 16, { animate: true });
        setTimeout(() => _markers[id]?.openPopup(), 300);
        document.querySelectorAll('.map-list-item').forEach(el => el.classList.remove('active'));
        document.getElementById('mli-' + id)?.classList.add('active');
        document.getElementById('anim-map')?.scrollIntoView({ behavior:'smooth', block:'start' });
    };
    doFocus();
}

// ── Cascata estado → cidade → bairro ─────────────────────────────────────────
async function carregarCidadesAnim(estadoId) {
    const sel = document.getElementById('sel-cidade-anim');
    const dl  = document.getElementById('lista-bairros-anim');
    const inp = document.getElementById('inp-bairro-anim');
    if (dl)  dl.innerHTML = '';
    if (inp) inp.value = '';
    if (!sel) return;
    sel.disabled = true;
    sel.innerHTML = '<option value="">Carregando...</option>';
    if (!estadoId) { sel.innerHTML = '<option value="">Selecione o estado...</option>'; return; }
    try {
        const cidades = await fetch('busca_localidade.php?acao=cidades&estado_id=' + estadoId).then(r => r.json());
        sel.innerHTML = '<option value="">Selecione a cidade...</option>';
        cidades.forEach(c => {
            const o = document.createElement('option');
            o.value = c.id; o.textContent = c.cidade; sel.appendChild(o);
        });
        sel.disabled = false;
    } catch { sel.innerHTML = '<option value="">Erro ao carregar</option>'; }
}

async function carregarBairrosAnim(cidadeId) {
    const dl = document.getElementById('lista-bairros-anim');
    if (!dl || !cidadeId) return;
    dl.innerHTML = '';
    try {
        const bairros = await fetch('busca_localidade.php?acao=bairros&cidade_id=' + cidadeId).then(r => r.json());
        bairros.forEach(b => { const o = document.createElement('option'); o.value = b; dl.appendChild(o); });
    } catch {}
}

document.getElementById('sel-estado-anim')?.addEventListener('change', function() {
    carregarCidadesAnim(this.value);
});
document.getElementById('sel-cidade-anim')?.addEventListener('change', function() {
    carregarBairrosAnim(this.value);
});

// ── Geolocalização (GPS + reverse geocoding) ──────────────────────────────────
function normStr(s) {
    return (s || '').normalize('NFD').replace(/[̀-ͯ]/g,'').toLowerCase().trim();
}

document.getElementById('btn-geolocate-anim')?.addEventListener('click', async function() {
    if (!navigator.geolocation) { alert('Geolocalização não suportada neste dispositivo.'); return; }
    const btn    = this;
    const status = document.getElementById('geocode-status-anim');
    btn.textContent = '⏳ Buscando…';
    btn.disabled = true;
    status.textContent = '';

    try {
        const pos = await new Promise((res, rej) => navigator.geolocation.getCurrentPosition(res, rej));
        const { latitude: lat, longitude: lng } = pos.coords;
        document.getElementById('inp-lat').value = lat.toFixed(6);
        document.getElementById('inp-lng').value = lng.toFixed(6);

        const data = await fetch(
            'https://nominatim.openstreetmap.org/reverse?lat=' + lat +
            '&lon=' + lng + '&format=json&accept-language=pt-BR'
        ).then(r => r.json());
        const addr = data.address || {};

        // Rua
        const partsRua = [addr.road, addr.house_number].filter(Boolean);
        if (partsRua.length) document.getElementById('inp-rua-anim').value = partsRua.join(', ');

        // Estado
        const estadoNome = addr.state || '';
        const selEstado  = document.getElementById('sel-estado-anim');
        if (selEstado && estadoNome) {
            const optEst = Array.from(selEstado.options).find(o => normStr(o.text) === normStr(estadoNome));
            if (optEst) {
                selEstado.value = optEst.value;
                await carregarCidadesAnim(optEst.value);
                // Cidade
                const cidNome  = addr.city || addr.town || addr.village || addr.county || '';
                const selCidade = document.getElementById('sel-cidade-anim');
                if (cidNome && selCidade) {
                    const optCid = Array.from(selCidade.options).find(o => normStr(o.text) === normStr(cidNome));
                    if (optCid) {
                        selCidade.value = optCid.value;
                        await carregarBairrosAnim(optCid.value);
                    }
                }
            }
        }

        // Bairro
        const bairroNome = addr.suburb || addr.neighbourhood || addr.residential || '';
        if (bairroNome) document.getElementById('inp-bairro-anim').value = bairroNome;

        btn.textContent = '✓ Localização capturada';
        btn.style.color = 'var(--ok)';
        status.textContent = '✓ Campos preenchidos automaticamente';
        status.style.color = 'var(--ok)';
    } catch(e) {
        if (e?.code !== undefined) alert('Não foi possível obter sua localização. Verifique as permissões do navegador.');
        btn.textContent = '📍 Minha localização';
        btn.style.color = '';
    }
    btn.disabled = false;
});

// ── Foto ──────────────────────────────────────────────────────────────────────
(function() {
    const inp    = document.getElementById('inp-foto-anim');
    const btnCam = document.getElementById('btn-camera-anim');
    const btnUpl = document.getElementById('btn-upload-anim');
    const preview = document.getElementById('foto-preview-anim');
    const prevImg = document.getElementById('foto-preview-img-anim');
    const btnRem  = document.getElementById('btn-remove-foto-anim');

    btnCam?.addEventListener('click', () => { inp.setAttribute('capture','environment'); inp.click(); });
    btnUpl?.addEventListener('click', () => { inp.removeAttribute('capture'); inp.click(); });

    inp?.addEventListener('change', () => {
        const file = inp.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { prevImg.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
    });

    btnRem?.addEventListener('click', () => { inp.value = ''; prevImg.src = ''; preview.style.display = 'none'; });
})();

function escHtml(str) {
    if (!str) return '';
    return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once('includes/footer.php'); ?>
