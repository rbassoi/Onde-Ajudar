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
  $rpmuser = $_SESSION["rpm"];
  $mpuser = $_SESSION["mp"];
  $emailuser = $_SESSION["email"];
*/
//Conectando-se ao banco de dados através do nosso arquivo de conexão. 
require_once("conexao.php"); 


?>



<?php 


//Recebendo os dados enviados pelo formulário vítima
	$nome = addslashes($_POST['nome']);
	$sexo = addslashes($_POST['sexo']);
  $rg = addslashes($_POST['rg']);
	$cpf = addslashes($_POST['cpf']);
	$situacao = 1;
	$datanascimento = addslashes($_POST['datanascimento']);
  $estado = addslashes($_POST['estado']);
  $cidade = addslashes($_POST['cidade']);
  $escolaridade = addslashes($_POST['escolaridade']);
  $tipodeficiencia = addslashes($_POST['tipodeficiencia']);
  $deficiencia = addslashes($_POST['deficiencia']);
	$motivo = $_REQUEST['motivorua'];
  $b = implode(" | ", $motivo);
	$usuario = addslashes($_POST['usuario']);
  $tipousuario = addslashes($_POST['tipousuario']);
  $situacaorua = addslashes($_POST['situacaorua']);
  $passagem = addslashes($_POST['passagem']);
	$tipopassagem = addslashes($_POST['tipopassagem']);
	$complemento = addslashes($_POST['complemento']);
  $cor = 4;
  
 
//Testando se os valores preenchidos no formulário estão realmente sendo recebidos nas varáveis acima. Teste ok!
 echo $nome.'<br/>';
 echo $sexo.'<br/>';
 echo $rg.'<br/>';
 echo $cpf.'<br/>';
 echo $situacao.'<br/>';
 echo $datanascimento.'<br/>';
 echo $deficiencia.'<br/>';
 echo $estado.'<br/>';
 echo $cidade.'<br/>';
 echo $escolaridade.'<br/>';
 echo $situacaorua.'<br/>';
 echo $passagem.'<br/>';
 echo $tipopassagem.'<br/>';
 echo $b.'<br/>';
 echo $usuario.'<br/>';
 echo $tipousuario.'<br/>';
 echo $cor.'<br/>';
 echo $complemento.'<br/>';
 



//Verificando se o campo nautos está vazio, este é um campo obrigatório, se estiver vazio enviar usuário para pagina do formulário e solicitar preenchimento.  
if(empty($nome)) { //verificando se o campo Nome está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo Nome é de preenchimento obrigatório!</strong>
  										</div>';

  			header("Location: cad.php");							

  		} else if(empty($rg)) { //verificando se o campo rg está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo RG é de preenchimento obrigatório!</strong>
  										</div>';

  			header("Location: cad.php");

   		} else if(empty($cpf)) { //verificando se o campo cpf está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo CPF é de preenchimento obrigatório!</strong>
  										</div>';

  			header("Location: cad.php");


   		} else if(empty($datanascimento)) { //verificando se o campo data nascimento está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo Data de Nascimento é de preenchimento obrigatório!</strong>
  										</div>';

  			header("Location: cad.php");

      } else { //Se os campos anteriores estiverm preenchidos no formulário, então, inserir os dados do formulário no Bando de Dados

    try {    

    $result_sql_inserir = $conn->prepare("INSERT INTO cadastro (nome, sexo, rg, cpf, situacao, data_nascimento, estado, cidade, escolaridade, deficiencia, tipo_deficiencia, motivo, usuario, tipo_usuario, situacao_rua, passagem, tipo_passagem, complemento, data_cadastro, cor) VALUES (:nome, :sexo, :rg, :cpf, :situacao, :datanascimento, :estado, :cidade, :escolaridade, :deficiencia, :tipodeficiencia, :b, :usuario, :tipousuario, :situacaorua, :passagem, :tipopassagem, :complemento, NOW(), :cor)");
  
      
    $result_sql_inserir->bindParam( ':nome', $nome );
    $result_sql_inserir->bindParam( ':sexo', $sexo );
    $result_sql_inserir->bindParam( ':rg', $rg );
    $result_sql_inserir->bindParam( ':cpf', $cpf );
    $result_sql_inserir->bindParam( ':situacao', $situacao );
    $result_sql_inserir->bindParam( ':datanascimento', $datanascimento );
    $result_sql_inserir->bindParam( ':estado', $estado );
    $result_sql_inserir->bindParam( ':cidade', $cidade );
    $result_sql_inserir->bindParam( ':escolaridade', $escolaridade );
    $result_sql_inserir->bindParam( ':deficiencia', $deficiencia );
    $result_sql_inserir->bindParam( ':tipodeficiencia', $tipodeficiencia );
    $result_sql_inserir->bindParam( ':b', $b );
    $result_sql_inserir->bindParam( ':usuario', $usuario );
    $result_sql_inserir->bindParam( ':tipousuario', $tipousuario );
    $result_sql_inserir->bindParam( ':situacaorua', $situacaorua );
    $result_sql_inserir->bindParam( ':passagem', $passagem );
    $result_sql_inserir->bindParam( ':tipopassagem', $tipopassagem );
    $result_sql_inserir->bindParam( ':complemento', $complemento ); 
    $result_sql_inserir->bindParam( ':cor', $cor );
    
    $result_sql_inserir->execute();

    var_dump($result_sql_inserir);

    if ($result_sql_inserir->rowCount() > 0) {

//Estamos usando a variável global $_SESSION['msg_registro'] para escreve uma mensagem se a inserção dos dados do formulário funcinou ou não e o header para direcionar de volta a pagina do formulário

      $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      <strong>Cadastro registrado com sucesso!</strong>
                      </div>';
      
      header("Location: cad.php");

   } 
  } 

  catch(PDOException $e) {

      $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      <strong>Não foi possível cadastrar no banco de dados!</strong>
                      </div>';
      
      header("Location: cad.php");   
  }

}

exit ();

?>