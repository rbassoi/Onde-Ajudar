<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }

require_once('conexao.php');

$page_title = 'Pessoas Desaparecidas — Onde Ajudar';

$stmt = $conn->query("SELECT * FROM desaparecidos ORDER BY criado_em DESC LIMIT 50");
$registros = $stmt->fetchAll();

require_once('includes/header.php');
?>

<style>
.desap-wrap {
    max-width: 960px;
    margin: 0 auto;
    padding: 32px 20px 60px;
}

/* Tabs */
.desap-tabs {
    display: flex;
    gap: 4px;
    background: var(--paper-2);
    border: 2px solid var(--line);
    border-radius: var(--radius);
    padding: 4px;
    margin-bottom: 32px;
    width: fit-content;
}
.desap-tabs .tab-btn {
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
.desap-tabs .tab-btn.active {
    background: var(--accent);
    color: #fff;
    box-shadow: 0 2px 8px rgba(207,106,68,.3);
}

.tab-panel { display: none; }
.tab-panel.active { display: block; }

/* Form */
.desap-form-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    padding: 32px 28px;
    max-width: 640px;
}
.desap-form-card h2 {
    font-family: var(--hand);
    font-size: 1.5rem;
    margin-bottom: 4px;
}
.desap-form-card .sub {
    color: var(--muted);
    font-size: .9rem;
    margin-bottom: 28px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}
@media (max-width: 520px) {
    .form-row { grid-template-columns: 1fr; }
    .desap-form-card { padding: 24px 16px; }
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
.foto-btn:hover {
    border-color: var(--accent);
    background: var(--accent-soft);
}
.foto-btn .foto-icon { font-size: 2rem; line-height: 1; }

/* Cards do feed */
.desap-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 18px;
}
.desap-card {
    background: var(--paper);
    border: 2px solid var(--line);
    border-radius: var(--radius-lg);
    overflow: hidden;
    transition: box-shadow .15s, transform .12s;
}
.desap-card:hover {
    box-shadow: var(--shadow-lg);
    transform: translateY(-3px);
}
.desap-card-foto {
    width: 100%;
    height: 180px;
    object-fit: cover;
    display: block;
    background: var(--board);
}
.desap-card-no-foto {
    width: 100%;
    height: 180px;
    background: var(--board);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 3.5rem;
    color: var(--muted);
}
.desap-card-body {
    padding: 18px 16px;
}
.desap-card-body h3 {
    font-family: var(--hand);
    font-size: 1.2rem;
    margin-bottom: 6px;
}
.desap-card-meta {
    font-size: 13px;
    color: var(--muted);
    line-height: 1.7;
}
.desap-card-meta strong { color: var(--ink); }
.badge-aberto   { background: var(--warn-soft);   color: var(--warn);   padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
.badge-encontrado { background: var(--ok-soft);   color: var(--ok);     padding: 2px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }

.empty-state {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted);
}
.empty-state .emoji { font-size: 3rem; margin-bottom: 12px; }
</style>

<div class="desap-wrap">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
        <a href="home.php" style="color:var(--muted);font-size:13px">← Início</a>
        <span style="color:var(--line)">·</span>
        <h1 style="margin:0;font-size:1.6rem">🔍 Pessoas Desaparecidas</h1>
    </div>

    <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" data-dismiss="auto" style="margin-bottom:20px">
        Registro enviado com sucesso. Que essa pessoa seja encontrada logo!
    </div>
    <?php endif; ?>

    <div class="desap-tabs">
        <button class="tab-btn active" data-tab="registrar">＋ Registrar desaparecido</button>
        <button class="tab-btn"        data-tab="feed">Feed (<?= count($registros) ?>)</button>
    </div>

    <!-- ── Aba: Registrar ─────────────────────────────────────── -->
    <div class="tab-panel active" id="tab-registrar">
        <div class="desap-form-card">
            <h2>Registrar pessoa desaparecida</h2>
            <p class="sub">Preencha o máximo de informações possível para ajudar na busca.</p>

            <form method="post" action="desaparecidos_processa.php" enctype="multipart/form-data">

                <!-- Nome + Idade -->
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome completo *</label>
                        <input type="text" name="nome" class="form-control"
                               placeholder="Nome da pessoa" required maxlength="200">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Idade</label>
                        <input type="number" name="idade" class="form-control"
                               placeholder="Ex: 32" min="0" max="120">
                    </div>
                </div>

                <!-- Origem -->
                <div class="form-group">
                    <label class="form-label">Cidade / Estado de origem</label>
                    <input type="text" name="origem" class="form-control"
                           placeholder="Ex: Florianópolis — SC" maxlength="200">
                </div>

                <!-- Último local visto -->
                <div class="form-group">
                    <label class="form-label">Último local visto *</label>
                    <input type="text" name="ultimo_local" class="form-control"
                           placeholder="Ex: Rua XV de Novembro, Centro — Curitiba/PR" required maxlength="300">
                </div>

                <!-- Telefone -->
                <div class="form-group">
                    <label class="form-label">Telefone de contato *</label>
                    <input type="tel" name="telefone_contato" class="form-control"
                           placeholder="(48) 9 9999-9999" required maxlength="30">
                </div>

                <!-- Descrição -->
                <div class="form-group">
                    <label class="form-label">Descrição <span class="text-muted text-sm">(opcional)</span></label>
                    <textarea name="descricao" class="form-control" rows="3"
                              placeholder="Cor do cabelo, roupas, marcas, sinais particulares..."></textarea>
                </div>

                <!-- Foto -->
                <div class="form-group">
                    <label class="form-label">Foto da pessoa <span class="text-muted text-sm">(opcional)</span></label>
                    <input type="file" name="foto" id="inp-foto-desap" accept="image/*" capture="environment" style="display:none">
                    <div class="foto-area">
                        <button type="button" id="btn-camera-desap" class="foto-btn">
                            <span class="foto-icon">📷</span>
                            Tirar foto
                        </button>
                        <button type="button" id="btn-upload-desap" class="foto-btn">
                            <span class="foto-icon">📁</span>
                            Galeria
                        </button>
                    </div>
                    <div id="foto-preview-desap" style="display:none;margin-top:12px;position:relative;width:fit-content">
                        <img id="foto-preview-img-desap" src="" alt="Prévia"
                             style="max-width:100%;max-height:220px;border-radius:10px;border:2px solid var(--line)">
                        <button type="button" id="btn-remove-foto-desap" title="Remover foto"
                                style="position:absolute;top:-10px;right:-10px;background:var(--accent);color:#fff;
                                       border:none;border-radius:50%;width:28px;height:28px;cursor:pointer;
                                       font-size:16px;line-height:28px;text-align:center;padding:0">×</button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="font-size:1.1rem;padding:14px">
                    Registrar desaparecido →
                </button>

            </form>
        </div>
    </div>

    <!-- ── Aba: Feed ──────────────────────────────────────────── -->
    <div class="tab-panel" id="tab-feed">
        <?php if (empty($registros)): ?>
        <div class="empty-state">
            <div class="emoji">🔍</div>
            <p style="font-family:var(--hand);font-size:1.2rem;margin-bottom:4px">Nenhum registro ainda</p>
            <p>Seja o primeiro a registrar um desaparecido.</p>
        </div>
        <?php else: ?>
        <div class="desap-grid">
            <?php foreach ($registros as $r): ?>
            <div class="desap-card">
                <?php if ($r['foto']): ?>
                <img class="desap-card-foto"
                     src="uploads/desaparecidos/<?= htmlspecialchars($r['foto']) ?>"
                     alt="Foto de <?= htmlspecialchars($r['nome']) ?>">
                <?php else: ?>
                <div class="desap-card-no-foto">👤</div>
                <?php endif; ?>
                <div class="desap-card-body">
                    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:8px;margin-bottom:6px">
                        <h3><?= htmlspecialchars($r['nome']) ?></h3>
                        <span class="badge-<?= $r['status'] ?>">
                            <?= $r['status'] === 'encontrado' ? '✓ encontrado' : '⚠ em busca' ?>
                        </span>
                    </div>
                    <div class="desap-card-meta">
                        <?php if ($r['idade']): ?>
                        <div><strong>Idade:</strong> <?= (int)$r['idade'] ?> anos</div>
                        <?php endif; ?>
                        <?php if ($r['origem']): ?>
                        <div><strong>Origem:</strong> <?= htmlspecialchars($r['origem']) ?></div>
                        <?php endif; ?>
                        <div><strong>Último local:</strong> <?= htmlspecialchars($r['ultimo_local']) ?></div>
                        <div><strong>Contato:</strong> <?= htmlspecialchars($r['telefone_contato']) ?></div>
                        <?php if ($r['descricao']): ?>
                        <div style="margin-top:6px;color:var(--ink)"><?= nl2br(htmlspecialchars($r['descricao'])) ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

</div>

<script>
// Tabs
document.querySelectorAll('.desap-tabs .tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.desap-tabs .tab-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
        document.getElementById('tab-' + btn.dataset.tab)?.classList.add('active');
    });
});

// Foto
(function() {
    const inp      = document.getElementById('inp-foto-desap');
    const btnCam   = document.getElementById('btn-camera-desap');
    const btnUpl   = document.getElementById('btn-upload-desap');
    const preview  = document.getElementById('foto-preview-desap');
    const prevImg  = document.getElementById('foto-preview-img-desap');
    const btnRem   = document.getElementById('btn-remove-foto-desap');

    btnCam?.addEventListener('click', () => { inp.setAttribute('capture','environment'); inp.click(); });
    btnUpl?.addEventListener('click', () => { inp.removeAttribute('capture'); inp.click(); });

    inp?.addEventListener('change', () => {
        const file = inp.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => { prevImg.src = e.target.result; preview.style.display = 'block'; };
        reader.readAsDataURL(file);
    });

    btnRem?.addEventListener('click', () => {
        inp.value = '';
        prevImg.src = '';
        preview.style.display = 'none';
    });
})();
</script>

<?php require_once('includes/footer.php'); ?>
