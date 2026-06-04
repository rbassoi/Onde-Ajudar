<?php
require_once('conexao.php');
session_start();

$nomeArquivo = isset($_POST['NmArquivo']) ? $_POST['NmArquivo'] : '';
$file_tmp    = isset($_FILES["file"]["tmp_name"]) ? $_FILES["file"]["tmp_name"] : '';
$file_name   = isset($_FILES["file"]["name"])     ? $_FILES["file"]["name"]     : '';
$file_size   = isset($_FILES["file"]["size"])     ? (int)$_FILES["file"]["size"] : 0;
$file_type   = isset($_FILES["file"]["type"])     ? $_FILES["file"]["type"]     : '';
$idVitima    = $_SESSION['id'];

if (empty($nomeArquivo)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Atenção!! O campo Nome do Arquivo é de preenchimento obrigatório!</strong>
    </div>';
    header("Location: ver_cadastro.php");
    exit;
}

$permitidos = ["image/jpeg"];
if (!in_array($file_type, $permitidos)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Extensão do arquivo inválida. Envie apenas arquivos .JPG!</strong>
    </div>';
    header("Location: ver_cadastro.php");
    exit;
}

$binario = file_get_contents($file_tmp);

try {
    $sql = $conn->prepare("INSERT INTO arquivos (nmarquivo, descricao, arquivo, tipo, tamanho, dthrenvio, idvitima)
                           VALUES (:nomeArquivo, :file_name, :binario, :file_type, :file_size, NOW(), :idVitima)");
    $sql->bindParam(':nomeArquivo', $nomeArquivo);
    $sql->bindParam(':file_name',   $file_name);
    $sql->bindParam(':binario',     $binario,    PDO::PARAM_LOB);
    $sql->bindParam(':file_type',   $file_type);
    $sql->bindParam(':file_size',   $file_size,  PDO::PARAM_INT);
    $sql->bindParam(':idVitima',    $idVitima,   PDO::PARAM_INT);
    $sql->execute();

    $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Arquivo registrado com sucesso!</strong>
    </div>';
    header("Location: ver_cadastro.php");

} catch (PDOException $e) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Não foi possível registrar o arquivo!</strong>
    </div>';
    header("Location: ver_cadastro.php");
}
?>
