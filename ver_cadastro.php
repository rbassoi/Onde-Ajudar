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
if ($id < 1) { header('Location: index.php'); exit; }

// ── Dados do cadastro ──────────────────────────────────────────
$stmt = $conn->prepare("SELECT
    c.id, c.nome, c.rg, c.cpf,
    TO_CHAR(c.data_nascimento, 'DD/MM/YYYY') AS datanascimento,
    c.motivo, c.tipo_usuario, c.tipo_passagem, c.complemento,
    e.escolaridade,
    d.deficiente,     c.tipo_deficiencia,
    u.usuario         AS tipo_usuario_label,
    p.passagem        AS passagem_label,
    s.situacao_rua    AS situacao_rua_label,
    ci.cidade,
    es.estado
FROM cadastro c
LEFT JOIN cidade              ci ON ci.id = c.cidade
LEFT JOIN estados             es ON es.id = c.estado
LEFT JOIN cadastro_escolaridade e ON  e.id = c.escolaridade
LEFT JOIN deficiencia          d ON  d.id = c.deficiencia
LEFT JOIN usuario              u ON  u.id = c.usuario
LEFT JOIN passagem             p ON  p.id = c.passagem
LEFT JOIN situacao_rua         s ON  s.id = c.situacao_rua
WHERE c.id = :id");
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$cad = $stmt->fetch();
if (!$cad) { header('Location: index.php'); exit; }

// ── Fotos ──────────────────────────────────────────────────────
$stmt_fotos = $conn->prepare(
    "SELECT id AS idFoto, foto_nome AS nomeFoto, foto_perfil AS perfilFoto, foto_tipo AS tipoFoto
     FROM fotos WHERE id_morador = :id");
$stmt_fotos->bindValue(':id', $id, PDO::PARAM_INT);
$stmt_fotos->execute();
$fotos = $stmt_fotos->fetchAll();

// ── Dados para o formulário de abordagem ──────────────────────
$cidades      = $conn->query("SELECT id, cidade   FROM cidade ORDER BY cidade")->fetchAll();
$encaminhos   = $conn->query("SELECT id, descricao FROM encaminhamento ORDER BY id")->fetchAll();
$locais       = $conn->query("SELECT id, local     FROM local  ORDER BY id")->fetchAll();

// ── Histórico de abordagens ────────────────────────────────────
$pesquisa = trim($_POST['pesquisa'] ?? '');
$stmt_msg = $conn->prepare("SELECT
    a.id,
    TO_CHAR(a.data_abordagem, 'DD/MM/YYYY HH24:MI') AS datacadastro,
    a.bairro, a.endereco, a.tempo_cidade, a.tempo_ficar,
    a.aceitou_encaminhamento, a.tipo_curso,
    a.porta_objetos, a.objetos, a.relato,
    a.limpeza_ambiente, a.complemento, a.responsavel_abordagem,
    a.usuario_registro, a.funcao, a.matricula,
    ci.cidade,
    am.condicao,
    l.local,
    e.descricao AS encaminhamento_label
FROM abordagem a
LEFT JOIN cidade       ci ON ci.id = a.cidade
LEFT JOIN ambiente     am ON am.id = a.condicao_ambiente
LEFT JOIN local         l ON  l.id = a.local_abordagem
LEFT JOIN encaminhamento e ON  e.id = a.tipo_encaminhamento
WHERE a.id_morador = :id
  AND (:pesquisa = '' OR a.relato ILIKE :like
    OR ci.cidade ILIKE :like OR l.local ILIKE :like
    OR a.endereco ILIKE :like OR a.responsavel_abordagem ILIKE :like)
ORDER BY a.data_abordagem DESC");
$stmt_msg->bindValue(':id',       $id, PDO::PARAM_INT);
$stmt_msg->bindValue(':pesquisa', $pesquisa);
$stmt_msg->bindValue(':like',     '%' . $pesquisa . '%');
$stmt_msg->execute();
$abordagens = $stmt_msg->fetchAll();

$_SESSION['id'] = $id;

$page_title = 'Cadastro — ' . htmlspecialchars($cad['nome']);
include 'includes/header.php';
?>

<!-- Page header -->
<div class="page-header">
    <div class="container">
        <div class="flex items-center justify-between gap-2 flex-wrap">
            <div>
                <h1><?= htmlspecialchars($cad['nome']) ?></h1>
                <p class="sub">CPF <?= htmlspecialchars($cad['cpf'] ?? '—') ?> · RG <?= htmlspecialchars($cad['rg'] ?? '—') ?></p>
            </div>
            <div class="flex gap-2 flex-wrap">
                <a href="cad_editar.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">✏ Editar cadastro</a>
                <a href="index.php" class="btn btn-ghost btn-sm">← Voltar</a>
            </div>
        </div>
    </div>
</div>

<div class="container" style="padding-top:20px;padding-bottom:60px">

<?php if (isset($_SESSION['msg_registro'])): ?>
    <div class="alert alert-<?= strpos($_SESSION['msg_registro'], 'sucesso') !== false ? 'success' : 'danger' ?> mb-3" data-dismiss="auto">
        <?= $_SESSION['msg_registro'] ?>
    </div>
    <?php unset($_SESSION['msg_registro']); ?>
<?php endif; ?>

<!-- ── Tabs ─────────────────────────────────────────────────── -->
<div class="avista-tabs" style="position:static;border-radius:var(--radius);margin-bottom:20px;padding:10px 14px">
    <button class="tab-btn active" data-tab="dados">📋 Dados</button>
    <button class="tab-btn"        data-tab="fotos">🖼 Fotos</button>
    <button class="tab-btn"        data-tab="abordagem">＋ Nova abordagem</button>
    <button class="tab-btn"        data-tab="historico">📅 Histórico (<?= count($abordagens) ?>)</button>
</div>

<!-- ════════════ TAB: DADOS ════════════ -->
<div id="tab-dados" class="tab-panel active">
<div class="card">
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px">
        <?php
        $campos = [
            'Data de Nascimento' => $cad['datanascimento'] ?? '—',
            'Estado de Origem'   => $cad['estado']         ?? '—',
            'Cidade de Origem'   => $cad['cidade']         ?? '—',
            'Escolaridade'       => $cad['escolaridade']   ?? '—',
            'Situação de Rua'    => $cad['situacao_rua_label'] ?? '—',
            'Motivo'             => $cad['motivo']         ?? '—',
            'Uso de Álcool/Drogas' => $cad['tipo_usuario_label'] ?? '—',
            'Tipo de Dependência'  => $cad['tipo_usuario']    ?? '—',
            'Deficiência'        => $cad['deficiente']     ?? '—',
            'Tipo de Deficiência'=> $cad['tipo_deficiencia'] ?? '—',
            'Passagem Criminal'  => $cad['passagem_label'] ?? '—',
            'Tipo de Passagem'   => $cad['tipo_passagem']  ?? '—',
        ];
        foreach ($campos as $label => $valor): ?>
        <div>
            <div class="text-muted text-sm" style="margin-bottom:2px"><?= $label ?></div>
            <div style="font-size:15px"><?= htmlspecialchars($valor) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($cad['complemento']): ?>
    <div style="margin-top:16px;padding-top:16px;border-top:1px solid rgba(58,52,44,.1)">
        <div class="text-muted text-sm mb-1">Dados Complementares</div>
        <p><?= nl2br(htmlspecialchars($cad['complemento'])) ?></p>
    </div>
    <?php endif; ?>
</div>
</div><!-- /tab-dados -->

<!-- ════════════ TAB: FOTOS ════════════ -->
<div id="tab-fotos" class="tab-panel">
<div class="card">
    <h3 style="margin-bottom:16px">Fotos e Documentos</h3>

    <?php if ($fotos): ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;margin-bottom:20px">
        <?php foreach ($fotos as $f): ?>
        <div class="card" style="padding:10px;text-align:center">
            <div style="font-size:2rem;margin-bottom:6px">🖼</div>
            <div style="font-size:13px;font-weight:500"><?= htmlspecialchars($f['nomeFoto']) ?></div>
            <div class="text-muted text-sm mb-2"><?= htmlspecialchars($f['tipoFoto']) ?></div>
            <div class="flex gap-1 justify-between">
                <a href="ver_arquivo.php?codigo=<?= (int)$f['idFoto'] ?>" target="_blank" class="btn btn-ghost btn-sm">Ver</a>
                <a href="deleta.php?codigo=<?= (int)$f['idFoto'] ?>" class="btn btn-sm" style="color:var(--danger);border-color:var(--danger)" onclick="return confirm('Deletar?')">Del</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted">Nenhuma foto ou documento enviado.</p>
    <?php endif; ?>

    <div style="border-top:1px solid rgba(58,52,44,.1);padding-top:16px">
        <h3 style="margin-bottom:12px">Enviar arquivo</h3>
        <form action="upload_binario.php" method="post" enctype="multipart/form-data" data-validate>
            <div class="grid-2 mb-2">
                <div class="form-group">
                    <label class="form-label">Nome do arquivo <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="NmArquivo" class="form-control" required placeholder="Ex.: Foto de frente">
                </div>
                <div class="form-group">
                    <label class="form-label">Arquivo (JPG) <span style="color:var(--danger)">*</span></label>
                    <input type="file" name="file" class="form-control" accept=".jpg,image/jpeg" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">⬆ Enviar</button>
        </form>
    </div>
</div>
</div><!-- /tab-fotos -->

<!-- ════════════ TAB: NOVA ABORDAGEM ════════════ -->
<div id="tab-abordagem" class="tab-panel">
<div class="card">
    <h3 style="margin-bottom:16px">Registrar nova abordagem</h3>

    <form method="post" action="ver_cadastro_processa.php" data-validate>
        <input type="hidden" name="id" value="<?= $id ?>">

        <div class="grid-2 mb-2">
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

        <div class="form-group mb-2">
            <label class="form-label">Endereço / Rua</label>
            <input type="text" name="rua" class="form-control" placeholder="Rua, número">
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Tempo na cidade</label>
                <input type="text" name="tempo_cidade" class="form-control" placeholder="Ex.: 3 meses">
            </div>
            <div class="form-group">
                <label class="form-label">Pretende ficar</label>
                <input type="text" name="tempo_ficar" class="form-control" placeholder="Ex.: indefinido">
            </div>
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Aceita encaminhamento? <span style="color:var(--danger)">*</span></label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="aceitou_encaminhamento" id="enc_sim" value="Sim" onclick="document.getElementById('tipo_encaminhamento').disabled=false"> Sim</label>
                    <label><input type="radio" name="aceitou_encaminhamento" id="enc_nao" value="Não" onclick="document.getElementById('tipo_encaminhamento').disabled=true" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Tipo de encaminhamento</label>
                <select name="tipo_encaminhamento" id="tipo_encaminhamento" class="form-control" disabled>
                    <option value="">Selecione...</option>
                    <?php foreach ($encaminhos as $e): ?>
                    <option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['descricao']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Tipo de curso</label>
            <input type="text" name="tipo_curso" class="form-control" placeholder="Se houve encaminhamento a curso">
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Porta pertences?</label>
                <div style="display:flex;gap:16px;padding-top:8px">
                    <label><input type="radio" name="porta_objetos" id="obj_sim" value="Sim" onclick="document.getElementById('objetos').disabled=false"> Sim</label>
                    <label><input type="radio" name="porta_objetos" id="obj_nao" value="Não" onclick="document.getElementById('objetos').disabled=true" checked> Não</label>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Descrição dos pertences</label>
                <input type="text" name="objetos" id="objetos" class="form-control" placeholder="Relação de pertences..." disabled>
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Relato do entrevistador</label>
            <textarea name="relato" class="form-control" rows="4" placeholder="Descreva a abordagem..."></textarea>
        </div>

        <div class="grid-2 mb-2">
            <div class="form-group">
                <label class="form-label">Local onde se encontra <span style="color:var(--danger)">*</span></label>
                <select name="local_abordagem" class="form-control" required>
                    <option value="">Selecione...</option>
                    <?php foreach ($locais as $l): ?>
                    <option value="<?= (int)$l['id'] ?>"><?= htmlspecialchars($l['local']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Condições do ambiente</label>
                <div style="display:flex;flex-direction:column;gap:6px;padding-top:8px">
                    <label><input type="radio" name="condicao_ambiente" value="1"> Salubre</label>
                    <label><input type="radio" name="condicao_ambiente" value="2"> Insalubre</label>
                </div>
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Foi necessária limpeza do ambiente?</label>
            <div style="display:flex;gap:16px;padding-top:6px">
                <label><input type="radio" name="limpeza_ambiente" value="Sim"> Sim</label>
                <label><input type="radio" name="limpeza_ambiente" value="Não"> Não</label>
            </div>
        </div>

        <div class="form-group mb-2">
            <label class="form-label">Status do atendimento</label>
            <div style="display:flex;flex-direction:column;gap:8px;padding-top:6px">
                <label style="display:flex;align-items:center;gap:8px">
                    <input type="radio" name="cor" value="1">
                    <span class="badge badge-ok">Colaborativo — abordado pacífico</span>
                </label>
                <label style="display:flex;align-items:center;gap:8px">
                    <input type="radio" name="cor" value="2">
                    <span class="badge badge-urg">Agressivo — possível efeito de drogas</span>
                </label>
            </div>
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Responsável pela abordagem</label>
            <input type="text" name="responsavel_abordagem" class="form-control" placeholder="Nome do responsável">
        </div>

        <div class="form-group mb-3">
            <label class="form-label">Dados complementares</label>
            <textarea name="complemento" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">💾 Registrar abordagem</button>
    </form>
</div>
</div><!-- /tab-abordagem -->

<!-- ════════════ TAB: HISTÓRICO ════════════ -->
<div id="tab-historico" class="tab-panel">

    <!-- Busca no histórico -->
    <form method="post" action="ver_cadastro.php?id=<?= $id ?>#tab-historico" class="search-panel mb-3" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end">
        <div class="form-group" style="flex:1;min-width:200px;margin:0">
            <label class="form-label">Buscar no histórico</label>
            <input type="search" name="pesquisa" class="form-control"
                   value="<?= htmlspecialchars($pesquisa) ?>" placeholder="Relato, cidade, local, responsável...">
        </div>
        <button type="submit" class="btn btn-primary btn-sm">🔍</button>
        <?php if ($pesquisa): ?>
        <a href="ver_cadastro.php?id=<?= $id ?>" class="btn btn-ghost btn-sm">Limpar</a>
        <?php endif; ?>
    </form>

    <?php if (empty($abordagens)): ?>
        <div class="card text-center" style="padding:30px">
            <p class="text-muted">Nenhum registro de abordagem encontrado.</p>
        </div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:14px">
    <?php foreach ($abordagens as $ab): ?>
    <div class="card">
        <!-- Cabeçalho da abordagem -->
        <div class="flex items-center justify-between flex-wrap gap-2 mb-2">
            <div style="font-family:var(--hand);font-size:1.1rem">
                📅 <?= htmlspecialchars($ab['datacadastro']) ?>
                <?php if ($ab['cidade']): ?> · <?= htmlspecialchars($ab['cidade']) ?><?php endif; ?>
                <?php if ($ab['bairro']): ?> — <?= htmlspecialchars($ab['bairro']) ?><?php endif; ?>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px;font-size:14px">
            <?php
            $campos_ab = [
                'Endereço'         => $ab['endereco'],
                'Tempo na cidade'  => $ab['tempo_cidade'],
                'Pretende ficar'   => $ab['tempo_ficar'],
                'Encaminhamento'   => $ab['aceitou_encaminhamento'],
                'Tipo encaminh.'   => $ab['encaminhamento_label'],
                'Curso'            => $ab['tipo_curso'],
                'Pertences'        => $ab['porta_objetos'],
                'Descrição pertences' => $ab['objetos'],
                'Local'            => $ab['local'],
                'Condição ambiente'=> $ab['condicao'],
                'Limpeza'          => $ab['limpeza_ambiente'],
                'Responsável'      => $ab['responsavel_abordagem'],
                'Usuário registro' => $ab['usuario_registro'],
                'Função'           => $ab['funcao'],
                'Matrícula'        => $ab['matricula'],
            ];
            foreach ($campos_ab as $k => $v):
                if (!$v) continue; ?>
            <div>
                <div class="text-muted" style="font-size:12px"><?= $k ?></div>
                <div><?= htmlspecialchars($v) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($ab['relato']): ?>
        <div style="margin-top:10px;padding-top:10px;border-top:1px solid rgba(58,52,44,.08)">
            <div class="text-muted text-sm mb-1">Relato</div>
            <p style="margin:0;font-size:14px"><?= nl2br(htmlspecialchars($ab['relato'])) ?></p>
        </div>
        <?php endif; ?>

        <?php if ($ab['complemento']): ?>
        <div style="margin-top:8px">
            <div class="text-muted text-sm mb-1">Complemento</div>
            <p style="margin:0;font-size:14px"><?= nl2br(htmlspecialchars($ab['complemento'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div><!-- /tab-historico -->

</div><!-- /container -->

<?php include 'includes/footer.php'; ?>
