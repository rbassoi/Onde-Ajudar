<?php
session_start();
require_once('conexao.php');

$nome    = trim($_POST['nome']      ?? '');
$email   = trim($_POST['email']     ?? '');
$user    = trim($_POST['user']      ?? '');
$funcao  = (int)($_POST['funcao']   ?? 0);
$senha   = $_POST['senha']          ?? '';
$conf    = $_POST['senha_conf']     ?? '';

function redir(string $msg): never {
    $_SESSION['msg_registro'] = $msg;
    header('Location: registro.php');
    exit;
}

// Validações
if (!$nome)  redir('<strong>Nome</strong> é obrigatório.');
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) redir('Informe um <strong>e-mail válido</strong>.');
if (!$user || !preg_match('/^[a-zA-Z0-9._-]+$/', $user)) redir('Login inválido. Use apenas letras, números, <code>. _ -</code>.');
if (!$funcao) redir('Selecione sua <strong>função</strong>.');
if (strlen($senha) < 8) redir('A senha deve ter ao menos <strong>8 caracteres</strong>.');
if ($senha !== $conf)   redir('As senhas <strong>não coincidem</strong>.');

// Login/email já existe?
$chk = $conn->prepare("SELECT id FROM usuarios WHERE login = :u OR email = :e LIMIT 1");
$chk->bindValue(':u', $user);
$chk->bindValue(':e', $email);
$chk->execute();
if ($chk->fetch()) redir('Este <strong>login ou e-mail</strong> já está em uso.');

// Perfil: Autoridade Pública e MP ficam pendentes (bloqueado = 2), demais ativam imediatamente
$stmt_fn = $conn->prepare("SELECT funcao FROM funcao WHERE id = :id");
$stmt_fn->bindValue(':id', $funcao, PDO::PARAM_INT);
$stmt_fn->execute();
$nome_funcao = $stmt_fn->fetchColumn() ?: '';

$requer_aprovacao = in_array($nome_funcao, ['Autoridade Pública', 'Ministério Público']);
$bloqueado = $requer_aprovacao ? 2 : 1;  // 2 = pendente de aprovação, 1 = ativo

// Perfil padrão por tipo de função
$mapa_perfil = [
    'Autoridade Pública'     => 'Guarnição',
    'Ministério Público'     => 'MP',
    'Assistência Social'     => 'Central',
    'Saúde'                  => 'Central',
    'ONG / Voluntariado'     => 'Cidadão',
    'Cidadão'                => 'Cidadão',
    'Pesquisador / Academia' => 'Cidadão',
    'Imprensa / Comunicação' => 'Cidadão',
];
$perfil_nome = $mapa_perfil[$nome_funcao] ?? 'Cidadão';
$stmt_perf = $conn->prepare("SELECT id FROM perfil WHERE perfil = :p LIMIT 1");
$stmt_perf->bindValue(':p', $perfil_nome);
$stmt_perf->execute();
$perfil_id = (int)($stmt_perf->fetchColumn() ?: 1);

// Insere usuário
$senhasegredo = sha1($senha);
$stmt_ins = $conn->prepare("INSERT INTO usuarios
    (login, senha, email, nome, bloqueado, data_cadastro, perfil, usuario_cadastro, funcao)
    VALUES (:user, :senha, :email, :nome, :bloqueado, NOW(), :perfil, 'auto-registro', :funcao)");
$stmt_ins->bindValue(':user',      $user);
$stmt_ins->bindValue(':senha',     $senhasegredo);
$stmt_ins->bindValue(':email',     $email);
$stmt_ins->bindValue(':nome',      $nome);
$stmt_ins->bindValue(':bloqueado', $bloqueado, PDO::PARAM_INT);
$stmt_ins->bindValue(':perfil',    $perfil_id, PDO::PARAM_INT);
$stmt_ins->bindValue(':funcao',    $funcao,    PDO::PARAM_INT);
$stmt_ins->execute();

if ($requer_aprovacao) {
    $_SESSION['msg_registro'] = '✓ Conta criada! Como sua função requer verificação, um administrador irá ativar seu acesso em breve.';
} else {
    $_SESSION['msg_registro'] = '✓ Conta criada com sucesso! Faça login para entrar.';
}
header('Location: login.php');
exit;
?>
