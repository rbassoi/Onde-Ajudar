<?php
session_start();

if (!isset($_SESSION['id_usuario'])) {
    header('Location: login.php');
    exit;
}

require_once('conexao.php');

$acao = $_POST['acao'] ?? '';

// ── Atualizar status de avistamento existente ──────────────────
if ($acao === 'status') {
    $id     = (int)($_POST['id']     ?? 0);
    $status = $_POST['status'] ?? 'pendente';

    if (!in_array($status, ['urgente', 'pendente', 'atendido'], true)) {
        $status = 'pendente';
    }

    if ($id > 0) {
        $stmt = $conn->prepare("UPDATE abordagem SET status_avistamento = :status WHERE id = :id");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id',     $id,    PDO::PARAM_INT);
        $stmt->execute();
    }

    header('Location: avistamentos.php#feed');
    exit;
}

// ── Registrar novo avistamento ─────────────────────────────────
if ($acao === 'registrar') {

    $local    = trim($_POST['local']              ?? '');
    $cidade   = (int)($_POST['cidade']            ?? 0);
    $bairro   = trim($_POST['bairro']             ?? '');
    $pessoas  = max(1, min(99, (int)($_POST['pessoas_count'] ?? 1)));
    $needs    = $_POST['necessidades']            ?? [];
    $descricao= trim($_POST['descricao']          ?? '');
    $contato  = trim($_POST['contato']            ?? '');
    $status   = $_POST['status_avistamento']      ?? 'pendente';
    $lat      = !empty($_POST['latitude'])  ? (float)$_POST['latitude']  : null;
    $lng      = !empty($_POST['longitude']) ? (float)$_POST['longitude'] : null;

    // Foto upload
    $foto_nome = null;
    if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, ['jpg','jpeg','png','webp','gif'], true)) {
            $dir = __DIR__ . '/uploads/avistamentos/';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $foto_nome = uniqid('av_', true) . '.' . $ext;
            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $foto_nome)) {
                $foto_nome = null;
            }
        }
    }

    if (!in_array($status, ['urgente', 'pendente', 'atendido'], true)) {
        $status = 'pendente';
    }

    if (empty($local)) {
        $_SESSION['msg_avistamento'] = '⚠ O campo local é obrigatório.';
        header('Location: avistamentos.php#registrar');
        exit;
    }

    $necessidades_str = implode(', ', array_map('trim', $needs));

    $conn->beginTransaction();
    try {
        // Cria cadastro mínimo para satisfazer a FK id_morador
        $stmt_cad = $conn->prepare(
            "INSERT INTO cadastro (nome, situacao, cidade, data_cadastro)
             VALUES (:nome, 1, :cidade, NOW())"
        );
        $stmt_cad->bindValue(':nome',   'Avistamento — ' . ($cidade ? '' : $local));
        $stmt_cad->bindValue(':cidade', $cidade ?: null, $cidade ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt_cad->execute();
        $id_morador = (int)$conn->lastInsertId();

        // Registra a abordagem (avistamento)
        $stmt_ab = $conn->prepare(
            "INSERT INTO abordagem
                (id_morador, data_abordagem, cidade, bairro, endereco,
                 relato, pessoas_count, necessidades, latitude, longitude,
                 contato_registrante, status_avistamento, usuario_registro, foto_avistamento)
             VALUES
                (:id_morador, NOW(), :cidade, :bairro, :endereco,
                 :relato, :pessoas, :necessidades, :lat, :lng,
                 :contato, :status, :usuario, :foto)"
        );
        $stmt_ab->bindValue(':id_morador',   $id_morador,        PDO::PARAM_INT);
        $stmt_ab->bindValue(':cidade',       $cidade ?: null,    $cidade ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $stmt_ab->bindValue(':bairro',       $bairro);
        $stmt_ab->bindValue(':endereco',     $local);
        $stmt_ab->bindValue(':relato',       $descricao);
        $stmt_ab->bindValue(':pessoas',      $pessoas,           PDO::PARAM_INT);
        $stmt_ab->bindValue(':necessidades', $necessidades_str);
        $stmt_ab->bindValue(':lat',          $lat);
        $stmt_ab->bindValue(':lng',          $lng);
        $stmt_ab->bindValue(':contato',      $contato);
        $stmt_ab->bindValue(':status',       $status);
        $stmt_ab->bindValue(':usuario',      $_SESSION['login'] ?? '');
        $stmt_ab->bindValue(':foto',         $foto_nome);
        $stmt_ab->execute();

        $conn->commit();

        $_SESSION['msg_avistamento'] = '✓ Avistamento registrado com sucesso!';
        header('Location: avistamentos.php#feed');

    } catch (PDOException $e) {
        $conn->rollBack();
        $_SESSION['msg_avistamento'] = '✗ Erro ao registrar avistamento.';
        header('Location: avistamentos.php#registrar');
    }
    exit;
}

// Ação desconhecida
header('Location: avistamentos.php');
exit;
?>
