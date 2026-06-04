<?php
session_start();
require_once('conexao.php');

$token = trim($_POST['token'] ?? '');
$senha = $_POST['senha']      ?? '';
$conf  = $_POST['senha_conf'] ?? '';

function redir_token(string $tok, string $msg): never {
    $_SESSION['msg_reset'] = $msg;
    header("Location: redefinir_senha.php?token=" . urlencode($tok));
    exit;
}

if (!$token)              redir_token($token, 'Token inválido.');
if (strlen($senha) < 8)   redir_token($token, 'A senha deve ter ao menos <strong>8 caracteres</strong>.');
if ($senha !== $conf)     redir_token($token, 'As senhas <strong>não coincidem</strong>.');

// Valida token
$stmt = $conn->prepare("SELECT rt.id AS tid, u.id AS uid
    FROM reset_tokens rt
    JOIN usuarios u ON u.id = rt.usuario_id
    WHERE rt.token = :token AND rt.usado = FALSE AND rt.expira_em > NOW()
    LIMIT 1");
$stmt->bindValue(':token', $token);
$stmt->execute();
$row = $stmt->fetch();

if (!$row) redir_token($token, 'Link inválido ou expirado. Solicite um novo.');

$conn->beginTransaction();
try {
    // Atualiza senha
    $stmt_up = $conn->prepare("UPDATE usuarios SET senha = :senha WHERE id = :id");
    $stmt_up->bindValue(':senha', sha1($senha));
    $stmt_up->bindValue(':id',    $row['uid'], PDO::PARAM_INT);
    $stmt_up->execute();

    // Marca token como usado
    $conn->prepare("UPDATE reset_tokens SET usado = TRUE WHERE id = :id")
         ->execute([':id' => $row['tid']]);

    $conn->commit();
} catch (PDOException $e) {
    $conn->rollBack();
    redir_token($token, 'Erro ao salvar a senha. Tente novamente.');
}

$_SESSION['msg_login'] = '<div class="alert alert-success">✓ Senha redefinida com sucesso! Faça login.</div>';
header('Location: login.php');
exit;
?>
