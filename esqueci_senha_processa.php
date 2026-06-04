<?php
session_start();
require_once('conexao.php');

$email = trim($_POST['email'] ?? '');

// Sempre mostra mensagem genérica (não revela se e-mail existe)
$msg_ok = '✓ Se esse e-mail estiver cadastrado, você receberá o link em instantes. Verifique também sua caixa de spam.';

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['msg_reset'] = 'Informe um e-mail válido.';
    header('Location: esqueci_senha.php');
    exit;
}

$stmt = $conn->prepare("SELECT id, nome, email FROM usuarios WHERE email = :email AND bloqueado = 1 LIMIT 1");
$stmt->bindValue(':email', $email);
$stmt->execute();
$usr = $stmt->fetch();

if ($usr) {
    // Gera token seguro (64 hex chars = 32 bytes)
    $token    = bin2hex(random_bytes(32));
    $expira   = date('Y-m-d H:i:s', time() + 3600 * 6); // 6 horas

    // Invalida tokens anteriores do mesmo usuário
    $conn->prepare("UPDATE reset_tokens SET usado = TRUE WHERE usuario_id = :id AND usado = FALSE")
         ->execute([':id' => $usr['id']]);

    // Salva novo token
    $stmt_tok = $conn->prepare(
        "INSERT INTO reset_tokens (usuario_id, token, expira_em) VALUES (:id, :token, :expira)"
    );
    $stmt_tok->bindValue(':id',     $usr['id'], PDO::PARAM_INT);
    $stmt_tok->bindValue(':token',  $token);
    $stmt_tok->bindValue(':expira', $expira);
    $stmt_tok->execute();

    // --- Em produção: enviar e-mail com o link abaixo ---
    // $link = "https://seudominio.com/redefinir_senha.php?token=$token";
    // mail($usr['email'], "Redefinir senha — Onde Ajudar", "Acesse: $link");

    // Em desenvolvimento: guarda token na sessão para exibir na tela
    $_SESSION['_dev_reset_token'] = $token;
    $_SESSION['_dev_reset_email'] = $usr['email'];
}

$_SESSION['msg_reset'] = $msg_ok;
header('Location: esqueci_senha_confirmado.php');
exit;
?>
