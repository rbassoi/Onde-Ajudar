<?php
require_once('conexao.php');

session_start();

//Recuperar o codigo do arquivo atraves do metodo GET
$codigo= $_GET['codigo'];


//Deleta o registro referente a id 
$codigo = (int)$codigo;
$sql = "DELETE FROM arquivos WHERE codigo = :codigo";

$deleta = $conn->prepare($sql);
$deleta->bindParam(':codigo', $codigo, PDO::PARAM_INT);
$deleta->execute();

$_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Arquivo deletado com sucesso!</strong>
  										</div>';
			
			header("Location: boletim.php");
?>