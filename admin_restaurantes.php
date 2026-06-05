<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
if ($_SESSION['permissao'] != 1)     { header('Location: home.php');  exit; }

require_once('conexao.php');

$page_title = 'Admin — Restaurantes Populares';

$edit_id = isset($_GET['editar']) ? (int)$_GET['editar'] : null;
$edit    = null;
if ($edit_id) {
    $s = $conn->prepare("SELECT * FROM restaurantes_populares WHERE id = :id");
    $s->execute([':id' => $edit_id]);
    $edit = $s->fetch();
}

$restaurantes = $conn->query(
    "SELECT * FROM restaurantes_populares ORDER BY estado, cidade, nome"
)->fetchAll();

require_once('includes/header.php');
?>

<style>
.adm-wrap { max-width: 1100px; margin: 0 auto; padding: 32px 20px 60px; }
.adm-form-card {
    background: var(--paper); border: 2px solid var(--line);
    border-radius: var(--radius-lg); padding: 28px 24px; margin-bottom: 36px;
}
.adm-form-card h2 { font-family: var(--hand); font-size: 1.4rem; margin-bottom: 20px; }
.form-row-3 { display: grid; grid-template-columns: 1fr 1fr 120px; gap: 14px; }
.form-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; }
.form-row-4 { display: grid; grid-template-columns: repeat(3, 1fr) 80px; gap: 14px; }
@media (max-width: 640px) {
    .form-row-3, .form-row-2, .form-row-4 { grid-template-columns: 1fr; }
}

.rest-table { width: 100%; border-collapse: collapse; background: var(--paper); border-radius: var(--radius-lg); overflow: hidden; border: 2px solid var(--line); }
.rest-table th { background: var(--paper-2); padding: 10px 14px; text-align: left; font-size: 13px; font-weight: 600; color: var(--muted); border-bottom: 2px solid var(--line); }
.rest-table td { padding: 12px 14px; border-bottom: 1px solid var(--board); font-size: 14px; vertical-align: middle; }
.rest-table tr:last-child td { border-bottom: none; }
.rest-table tr:hover td { background: var(--board); }
.badge-ativo   { background: var(--ok-soft);    color: var(--ok);     padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-obras   { background: var(--warn-soft);  color: var(--warn);   padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.badge-fechado { background: var(--danger-soft);color: var(--danger); padding: 2px 8px; border-radius: 20px; font-size: 11px; font-weight: 600; }
.btn-sm-table { padding: 4px 12px; font-size: 12px; }
</style>

<div class="adm-wrap">

    <div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
        <a href="restaurantes.php" style="color:var(--muted);font-size:13px">← Ver restaurantes</a>
        <span style="color:var(--line)">·</span>
        <h1 style="margin:0;font-size:1.6rem">⚙ Administrar Restaurantes</h1>
    </div>

    <?php if (isset($_GET['ok'])): ?>
    <div class="alert alert-success" data-dismiss="auto" style="margin-bottom:20px">
        <?= $_GET['ok'] === 'salvo' ? 'Restaurante salvo com sucesso.' : 'Restaurante excluído.' ?>
    </div>
    <?php endif; ?>

    <!-- Formulário -->
    <div class="adm-form-card">
        <h2><?= $edit ? '✏️ Editar restaurante' : '＋ Novo restaurante' ?></h2>
        <form method="post" action="admin_restaurantes_processa.php">
            <?php if ($edit): ?>
            <input type="hidden" name="id" value="<?= $edit['id'] ?>">
            <?php endif; ?>

            <div class="form-row-3" style="margin-bottom:0">
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-control" required maxlength="200"
                           value="<?= htmlspecialchars($edit['nome'] ?? '') ?>"
                           placeholder="Ex: Restaurante Popular I – Herbert de Souza">
                </div>
                <div class="form-group">
                    <label class="form-label">Cidade *</label>
                    <input type="text" name="cidade" class="form-control" required maxlength="100"
                           value="<?= htmlspecialchars($edit['cidade'] ?? '') ?>"
                           placeholder="Belo Horizonte">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado *</label>
                    <input type="text" name="estado" class="form-control" required maxlength="2"
                           value="<?= htmlspecialchars($edit['estado'] ?? '') ?>"
                           placeholder="MG" style="text-transform:uppercase">
                </div>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Endereço *</label>
                    <input type="text" name="endereco" class="form-control" required maxlength="300"
                           value="<?= htmlspecialchars($edit['endereco'] ?? '') ?>"
                           placeholder="Rua, número">
                </div>
                <div class="form-group">
                    <label class="form-label">Bairro</label>
                    <input type="text" name="bairro" class="form-control" maxlength="100"
                           value="<?= htmlspecialchars($edit['bairro'] ?? '') ?>"
                           placeholder="Centro">
                </div>
            </div>

            <div class="form-row-4">
                <div class="form-group">
                    <label class="form-label">☕ Café da manhã</label>
                    <input type="text" name="horario_cafe" class="form-control" maxlength="80"
                           value="<?= htmlspecialchars($edit['horario_cafe'] ?? '') ?>"
                           placeholder="7h às 8h">
                </div>
                <div class="form-group">
                    <label class="form-label">🍽️ Almoço</label>
                    <input type="text" name="horario_almoco" class="form-control" maxlength="80"
                           value="<?= htmlspecialchars($edit['horario_almoco'] ?? '') ?>"
                           placeholder="11h às 14h">
                </div>
                <div class="form-group">
                    <label class="form-label">🌙 Jantar</label>
                    <input type="text" name="horario_jantar" class="form-control" maxlength="80"
                           value="<?= htmlspecialchars($edit['horario_jantar'] ?? '') ?>"
                           placeholder="17h às 21h">
                </div>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control">
                        <option value="ativo"   <?= ($edit['status'] ?? 'ativo') === 'ativo'   ? 'selected' : '' ?>>Ativo</option>
                        <option value="obras"   <?= ($edit['status'] ?? '') === 'obras'   ? 'selected' : '' ?>>Obras</option>
                        <option value="fechado" <?= ($edit['status'] ?? '') === 'fechado' ? 'selected' : '' ?>>Fechado</option>
                    </select>
                </div>
            </div>

            <div class="form-row-3">
                <div class="form-group">
                    <label class="form-label">Preço café (R$)</label>
                    <input type="number" name="preco_cafe" class="form-control" step="0.01" min="0"
                           value="<?= $edit['preco_cafe'] ?? '' ?>" placeholder="0.75">
                </div>
                <div class="form-group">
                    <label class="form-label">Preço almoço (R$)</label>
                    <input type="number" name="preco_almoco" class="form-control" step="0.01" min="0"
                           value="<?= $edit['preco_almoco'] ?? '' ?>" placeholder="3.00">
                </div>
                <div class="form-group">
                    <label class="form-label">Preço jantar (R$)</label>
                    <input type="number" name="preco_jantar" class="form-control" step="0.01" min="0"
                           value="<?= $edit['preco_jantar'] ?? '' ?>" placeholder="1.50">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Observações</label>
                <textarea name="observacoes" class="form-control" rows="2"
                          placeholder="Informações adicionais, restrições, obras..."><?= htmlspecialchars($edit['observacoes'] ?? '') ?></textarea>
            </div>

            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Latitude <span style="font-size:11px;color:var(--muted)">(preenchido automaticamente)</span></label>
                    <input type="number" name="latitude" id="inp-lat" class="form-control" step="0.000001"
                           value="<?= $edit['latitude'] ?? '' ?>" placeholder="-19.928400">
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="number" name="longitude" id="inp-lng" class="form-control" step="0.000001"
                           value="<?= $edit['longitude'] ?? '' ?>" placeholder="-43.942700">
                </div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-primary">
                    <?= $edit ? '💾 Salvar alterações' : '＋ Adicionar restaurante' ?>
                </button>
                <button type="button" id="btn-geocode" class="btn btn-ghost">📍 Geocodificar endereço</button>
                <?php if ($edit): ?>
                <a href="admin_restaurantes.php" class="btn btn-ghost">Cancelar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <h2 style="font-family:var(--hand);font-size:1.3rem;margin-bottom:16px">
        Restaurantes cadastrados (<?= count($restaurantes) ?>)
    </h2>

    <?php if (empty($restaurantes)): ?>
    <p style="color:var(--muted)">Nenhum restaurante cadastrado ainda.</p>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="rest-table">
        <thead>
            <tr>
                <th>Nome</th>
                <th>Cidade/UF</th>
                <th>Bairro</th>
                <th>Almoço</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($restaurantes as $r): ?>
        <tr>
            <td><strong><?= htmlspecialchars($r['nome']) ?></strong><br>
                <span style="font-size:12px;color:var(--muted)"><?= htmlspecialchars($r['endereco']) ?></span></td>
            <td><?= htmlspecialchars($r['cidade']) ?>/<?= htmlspecialchars($r['estado']) ?></td>
            <td><?= htmlspecialchars($r['bairro'] ?? '—') ?></td>
            <td><?= htmlspecialchars($r['horario_almoco'] ?? '—') ?></td>
            <td>
                <?php
                echo match($r['status']) {
                    'obras'   => '<span class="badge-obras">🔧 Obras</span>',
                    'fechado' => '<span class="badge-fechado">✕ Fechado</span>',
                    default   => '<span class="badge-ativo">✓ Ativo</span>',
                };
                ?>
            </td>
            <td>
                <div style="display:flex;gap:6px">
                    <a href="admin_restaurantes.php?editar=<?= $r['id'] ?>"
                       class="btn btn-ghost btn-sm-table">✏️ Editar</a>
                    <form method="post" action="admin_restaurantes_processa.php"
                          onsubmit="return confirm('Excluir este restaurante?')">
                        <input type="hidden" name="id"     value="<?= $r['id'] ?>">
                        <input type="hidden" name="acao"   value="excluir">
                        <button type="submit" class="btn btn-ghost btn-sm-table"
                                style="color:var(--danger)">✕</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.getElementById('btn-geocode')?.addEventListener('click', async () => {
    const endereco  = document.querySelector('[name="endereco"]').value.trim();
    const bairro    = document.querySelector('[name="bairro"]').value.trim();
    const cidade    = document.querySelector('[name="cidade"]').value.trim();
    const estado    = document.querySelector('[name="estado"]').value.trim();
    const btn       = document.getElementById('btn-geocode');

    if (!endereco || !cidade) {
        alert('Preencha pelo menos o endereço e a cidade antes de geocodificar.');
        return;
    }

    const query = [endereco, bairro, cidade, estado, 'Brasil'].filter(Boolean).join(', ');
    btn.textContent = '⏳ Buscando...';
    btn.disabled = true;

    try {
        const url = `https://nominatim.openstreetmap.org/search?format=json&limit=1&q=${encodeURIComponent(query)}`;
        const resp = await fetch(url, { headers: { 'Accept-Language': 'pt-BR' } });
        const data = await resp.json();
        if (data.length > 0) {
            document.getElementById('inp-lat').value = parseFloat(data[0].lat).toFixed(6);
            document.getElementById('inp-lng').value = parseFloat(data[0].lon).toFixed(6);
            btn.textContent = '✓ Coordenadas encontradas';
            btn.style.color = 'var(--ok)';
        } else {
            btn.textContent = '⚠ Não encontrado — tente ajustar o endereço';
            btn.style.color = 'var(--warn)';
        }
    } catch (e) {
        btn.textContent = '✗ Erro na geocodificação';
        btn.style.color = 'var(--danger)';
    } finally {
        btn.disabled = false;
        setTimeout(() => {
            btn.textContent = '📍 Geocodificar endereço';
            btn.style.color = '';
        }, 4000);
    }
});
</script>

<?php require_once('includes/footer.php'); ?>
