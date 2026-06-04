<?php
//Iniciando a sessão
session_start();

/*
// Verifica se existe os dados da sessão de login 
if(!isset($_SESSION["id_usuario"]) || !isset($_SESSION["nome_usuario"])) {

// Usuário não logado! Redireciona para a página de login 
header("Location: login.php"); 
exit; 
} 


//Capturando dados do usuário logado
  $login = $_SESSION["login"];
  $id_usuario = $_SESSION["id_usuario"];
  $nomeuser = $_SESSION["nome_usuario"]; 
  $permissao = $_SESSION["permissao"];
  $batalhaouser = $_SESSION["batalhao"]; 
  $emailuser = $_SESSION["email"];
  $rpmuser = $_SESSION["rpm"];
  $mpuser = $_SESSION["mp"];

*/

//Conectando-se ao banco de dados através do nosso arquivo de conexão. 
require_once("conexao.php"); 


?>



<?php 

//Recebendo os dados enviados pelo formulário vítima
	$id = $_SESSION['id'];
	$nome = addslashes($_POST['nome']);
  	$rg = addslashes($_POST['rg']);
	$cpf = addslashes($_POST['cpf']);
	$situacao = addslashes($_POST['situacao']);
	$datanascimento = addslashes($_POST['datanascimento']);
  	$estado = addslashes($_POST['estado']);
  	$cidade = addslashes($_POST['cidade']);
  	$escolaridade = addslashes($_POST['escolaridade']);
  	$deficiencia = addslashes($_POST['deficiencia']);
  	$tipodeficiencia = addslashes($_POST['tipodeficiencia']);
	$motivo = $_REQUEST['motivorua'];
  	$b = implode(' | ', array($motivo));
	$usuario = addslashes($_POST['usuario']);
  	$tipousuario = addslashes($_POST['tipousuario']);
  	$situacaorua = addslashes($_POST['situacaorua']);
  	$passagem = addslashes($_POST['passagem']);
	$tipopassagem = addslashes($_POST['tipopassagem']);
	$complemento = addslashes($_POST['complemento']);
  	


    	

//Testando se os valores preenchidos no formulário estão realmente sendo recebidos nas varáveis acima. Teste ok!

 echo $nome.'<br/>';
 echo $rg.'<br/>';
 echo $cpf.'<br/>';
 echo $situacao.'<br/>';
 echo $datanascimento.'<br/>';
 echo $estado.'<br/>';
 echo $cidade.'<br/>';
 echo $escolaridade.'<br/>';
 echo $deficiencia.'<br/>';
 echo $tipodeficiencia.'<br/>';
 echo $b.'<br/>';
 echo $usuario.'<br/>';
 echo $tipousuario.'<br/>';
 echo $situacaorua.'<br/>';
 echo $passagem.'<br/>';
 echo $tipopassagem.'<br/>';
 echo $complemento.'<br/>';

 
 if(empty($cidade)) { //verificando se o campo N° auto está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Você esqueceu do campo cidade!</strong>
  										</div>';
			
			header("Location: cad_editar.php?id=$id");
			
   } else {

try {	
		
		$sql_edita = $conn->prepare("UPDATE cadastro SET nome=:nome, rg=:rg, cpf=:cpf, situacao=:situacao, data_nascimento=:datanascimento, estado=:estado, cidade=:cidade, escolaridade=:escolaridade, situacao_rua=:situacaorua, motivo=:b, deficiencia=:deficiencia, tipo_deficiencia=:tipodeficiencia, usuario=:usuario, tipo_usuario=:tipousuario, passagem=:passagem, tipo_passagem=:tipopassagem, complemento=:complemento WHERE id=:id");
		
   			// $result_sql_edita = mysqli_query($conn, $sql_edita);
	$sql_edita->bindParam( ':id', $id );
	$sql_edita->bindParam( ':nome', $nome );
    $sql_edita->bindParam( ':rg', $rg );
    $sql_edita->bindParam( ':cpf', $cpf );
    $sql_edita->bindParam( ':situacao', $situacao );
    $sql_edita->bindParam( ':datanascimento', $datanascimento );
    $sql_edita->bindParam( ':estado', $estado );
    $sql_edita->bindParam( ':cidade', $cidade );
    $sql_edita->bindParam( ':escolaridade', $escolaridade );
    $sql_edita->bindParam( ':deficiencia', $deficiencia );
    $sql_edita->bindParam( ':tipodeficiencia', $tipodeficiencia );
    $sql_edita->bindParam( ':b', $b );
    $sql_edita->bindParam( ':usuario', $usuario );
    $sql_edita->bindParam( ':tipousuario', $tipousuario );
    $sql_edita->bindParam( ':situacaorua', $situacaorua );
    $sql_edita->bindParam( ':passagem', $passagem );
    $sql_edita->bindParam( ':tipopassagem', $tipopassagem );
    $sql_edita->bindParam( ':complemento', $complemento ); 
    
    
    $sql_edita->execute();

    var_dump($sql_edita);

    
		 //Verificando se a alteração modificou alguma linha
    	if( $sql_edita->rowCount() > 0 ) {

//Estamos usando a variável global $_SESSION['msg_registro'] para escreve uma mensagem se a inserção dos dados do formulário funcinou ou não e o header para direcionar de volta a pagina do formulário

			$_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Alteração registrada com sucesso!</strong>
  										</div>';
			
			header("Location: ver_cadastro.php?id=$id");
} else {

	echo "Não deu!!!";
}	
		
	} catch(PDOException $e) {

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! A alteração não pode ser registrada no banco de dados!</strong>
  										</div>';
			
	
			header("Location: cad_editar.php?id=$id");	
		}		
	}


?>
