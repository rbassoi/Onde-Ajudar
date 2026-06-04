<?php
session_start();
require_once('conexao.php');

if (isset($_GET['id']) && !empty($_GET['id'])) {
    $_SESSION['id'] = (int)$_GET['id'];
}
$id = (int)($_SESSION['id'] ?? 0);

$cidade               = trim($_POST['cidade']               ?? '');
$bairro               = trim($_POST['bairro']               ?? '');
$rua                  = trim($_POST['rua']                  ?? '');
$tempocidade          = trim($_POST['tempo_cidade']         ?? '');
$tempoficar           = trim($_POST['tempo_ficar']          ?? '');
$encaminhamento       = trim($_POST['aceitou_encaminhamento'] ?? '');
$tipoencaminhamento   = trim($_POST['tipo_encaminhamento']  ?? '');
$tipocurso            = trim($_POST['tipo_curso']           ?? '');
$portaobjetos         = trim($_POST['porta_objetos']        ?? '');
$objetos              = trim($_POST['objetos']              ?? '');
$relato               = trim($_POST['relato']               ?? '');
$localabordagem       = trim($_POST['local_abordagem']      ?? '');
$condicaoambiente     = trim($_POST['condicao_ambiente']    ?? '');
$limpezaambiente      = trim($_POST['limpeza_ambiente']     ?? '');
$complemento          = trim($_POST['complemento']          ?? '');
$responsavelabordagem = trim($_POST['responsavel_abordagem'] ?? '');
$cor                  = trim($_POST['cor']                  ?? '');

if (empty($encaminhamento)) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>O campo Encaminhamento é obrigatório!</strong></div>';
    header("Location: ver_cadastro.php?id=$id");
    exit;
}

try {
    $stmt = $conn->prepare(
        "INSERT INTO abordagem
            (id_morador, data_abordagem, cidade, bairro, endereco, tempo_cidade, tempo_ficar,
             aceitou_encaminhamento, tipo_encaminhamento, tipo_curso, porta_objetos, objetos,
             relato, local_abordagem, condicao_ambiente, limpeza_ambiente, complemento,
             responsavel_abordagem, cor)
         VALUES
            (:id, NOW(), :cidade, :bairro, :rua, :tempocidade, :tempoficar,
             :encaminhamento, :tipoencaminhamento, :tipocurso, :portaobjetos, :objetos,
             :relato, :localabordagem, :condicaoambiente, :limpezaambiente, :complemento,
             :responsavelabordagem, :cor)"
    );
    $stmt->bindValue(':id',                   $id,                    PDO::PARAM_INT);
    $stmt->bindValue(':cidade',               $cidade ?: null,        $cidade ? PDO::PARAM_STR : PDO::PARAM_NULL);
    $stmt->bindValue(':bairro',               $bairro);
    $stmt->bindValue(':rua',                  $rua);
    $stmt->bindValue(':tempocidade',          $tempocidade);
    $stmt->bindValue(':tempoficar',           $tempoficar);
    $stmt->bindValue(':encaminhamento',       $encaminhamento);
    $stmt->bindValue(':tipoencaminhamento',   $tipoencaminhamento ?: null);
    $stmt->bindValue(':tipocurso',            $tipocurso);
    $stmt->bindValue(':portaobjetos',         $portaobjetos);
    $stmt->bindValue(':objetos',              $objetos);
    $stmt->bindValue(':relato',               $relato);
    $stmt->bindValue(':localabordagem',       $localabordagem ?: null);
    $stmt->bindValue(':condicaoambiente',     $condicaoambiente ?: null);
    $stmt->bindValue(':limpezaambiente',      $limpezaambiente);
    $stmt->bindValue(':complemento',          $complemento);
    $stmt->bindValue(':responsavelabordagem', $responsavelabordagem);
    $stmt->bindValue(':cor',                  $cor ?: null);
    $stmt->execute();

    $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Visita registrada com sucesso!</strong></div>';
    header("Location: ver_cadastro.php?id=$id");

} catch (PDOException $e) {
    $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
        <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
        <strong>Não foi possível registrar a visita.</strong></div>';
    header("Location: ver_cadastro.php?id=$id");
    exit;
}
?>
