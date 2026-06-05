<?php
session_start();
if (!isset($_SESSION['id_usuario'])) { header('Location: login.php'); exit; }
require_once('conexao.php');

$resultados = [];

// Lista de scripts a aplicar em ordem
$scripts = [
    'migrate_avistamentos'   => 'banco/migrate_avistamentos.sql',
    'migrate_funcao'         => 'banco/migrate_funcao.sql',
    'migrate_foto'           => 'banco/migrate_foto_avistamento.sql',
    'migrate_desaparecidos'  => 'banco/migrate_desaparecidos.sql',
    'seeds_cidades'          => 'banco/seeds_cidades_brasil.sql',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($scripts as $nome => $arquivo) {
        $path = __DIR__ . '/' . $arquivo;
        if (!file_exists($path)) {
            $resultados[] = ['nome' => $nome, 'ok' => false, 'msg' => "Arquivo não encontrado: $arquivo"];
            continue;
        }
        $sql = file_get_contents($path);
        try {
            $conn->exec($sql);
            $resultados[] = ['nome' => $nome, 'ok' => true, 'msg' => 'OK'];
        } catch (PDOException $e) {
            $resultados[] = ['nome' => $nome, 'ok' => false, 'msg' => $e->getMessage()];
        }
    }
}

// Status atual do banco
$stmt_por_estado = $conn->query(
    "SELECT e.estado, COUNT(c.id) AS qtd
     FROM estados e LEFT JOIN cidade c ON c.estado_id = e.id
     GROUP BY e.id, e.estado ORDER BY e.estado"
);
$por_estado = $stmt_por_estado->fetchAll();

// Verifica se coluna foto_avistamento existe
$stmt_col = $conn->query(
    "SELECT column_name FROM information_schema.columns
     WHERE table_name='abordagem' AND column_name='foto_avistamento'"
);
$tem_foto_col = $stmt_col->fetchColumn() !== false;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Setup — Migrations e Seeds</title>
    <link rel="stylesheet" href="estilos/css/app.css">
    <style>
        body { max-width: 800px; margin: 40px auto; padding: 0 20px; font-family: Inter, sans-serif; }
        h1   { margin-bottom: 4px; }
        .sub { color: #888; margin-bottom: 20px; }
        .result { margin: 4px 0; padding: 8px 12px; border-radius: 6px; }
        .result.ok  { background: #d9e7d2; }
        .result.err { background: #fde8e6; font-size: .85rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { text-align: left; padding: 6px 10px; border-bottom: 1px solid var(--line); }
        th { background: var(--paper-2); }
        .zero { color: var(--accent); font-weight: 600; }
        .status-ok  { color: #3a8a2e; font-weight: 600; }
        .status-err { color: var(--accent); font-weight: 600; }
        .badge { display:inline-block; padding:2px 10px; border-radius:20px; font-size:.8rem; }
        .badge-ok  { background:#d9e7d2; color:#3a8a2e; }
        .badge-err { background:#fde8e6; color:#c0392b; }
    </style>
</head>
<body>
<h1>Setup — Migrations e Seeds</h1>
<p class="sub">Aplica todas as migrations e importa as cidades brasileiras no banco.</p>

<?php if (!empty($resultados)): ?>
<div style="margin-bottom:20px">
    <?php foreach ($resultados as $r): ?>
    <div class="result <?= $r['ok'] ? 'ok' : 'err' ?>">
        <strong><?= htmlspecialchars($r['nome']) ?></strong>:
        <?= $r['ok'] ? '✓ ' : '✗ ' ?><?= htmlspecialchars($r['msg']) ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-bottom:24px">
    <form method="post" style="display:inline">
        <button type="submit" class="btn btn-primary">▶ Aplicar migrations e importar cidades</button>
    </form>
    <a href="avistamentos.php" class="btn btn-ghost">← Voltar para avistamentos</a>
</div>

<h2>Status do banco</h2>
<p>
    Coluna <code>foto_avistamento</code> na tabela abordagem:
    <span class="badge <?= $tem_foto_col ? 'badge-ok' : 'badge-err' ?>">
        <?= $tem_foto_col ? '✓ existe' : '✗ não existe — clique em Aplicar' ?>
    </span>
</p>

<h3>Cidades por estado</h3>
<table>
    <thead><tr><th>Estado</th><th>Qtd. cidades</th></tr></thead>
    <tbody>
    <?php foreach ($por_estado as $row): ?>
    <tr>
        <td><?= htmlspecialchars($row['estado']) ?></td>
        <td class="<?= $row['qtd'] == 0 ? 'zero' : '' ?>"><?= (int)$row['qtd'] ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</body>
</html>
