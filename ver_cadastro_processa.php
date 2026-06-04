<?php
//Iniciando a sessão
session_start();


//Define id como zero e fazer a Verificação para pegar o id da vítima de verdade na url da pagina
    
    $id = 0;
    
    if(isset($_GET['id']) && empty($_GET['id']) == false) {
      $_SESSION['id'] = addslashes($_GET['id']);
    }  

    $id = $_SESSION['id'];


//Conectando-se ao banco de dados através do nosso arquivo de conexão. 
require_once("conexao.php"); 


?>



<?php 
//Recebendo os dados enviados pelo formulário vítima
    
    $cidade = addslashes($_POST['cidade']);
    $bairro = addslashes($_POST['bairro']);
    $rua = addslashes($_POST['rua']);
    $tempocidade = addslashes($_POST['tempo_cidade']);
    $tempoficar = addslashes($_POST['tempo_ficar']);
    $encaminhamento = addslashes($_POST['aceitou_encaminhamento']);
    $tipoencaminhamento = addslashes($_POST['tipo_encaminhamento']);
    $tipocurso = addslashes($_POST['tipo_curso']);
    $portaobjetos = addslashes($_POST['porta_objetos']);
    $objetos = addslashes($_POST['objetos']);
    $relato = addslashes($_POST['relato']);
    $localabordagem = addslashes($_POST['local_abordagem']);
    $condicaoambiente = addslashes($_POST['condicao_ambiente']);
    $limpezaambiente = addslashes($_POST['limpeza_ambiente']);
    $complemento = addslashes($_POST['complemento']);
    $responsavelabordagem = addslashes($_POST['responsavel_abordagem']);
    $cor = addslashes($_POST['cor']);
    
    

/*Testando se os valores preenchidos no formulário estão realmente sendo recebidos nas varáveis acima. Teste ok!
 echo $situacao.'<br/>';
 echo $mensagem.'<br/>';
 echo $cor.'<br/>';
 echo $data_visita.'<br/>';
 echo $guarnicao.'<br/>';
 
 */
    

//Verificando se o campo idauto está vazio, este é um campo obrigatório, se estiver vazio enviar usuário para pagina do formulário e solicitar preenchimento.  
if(empty($encaminhamento)) { //verificando se o campo login está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo Situação é de preenchimento obrigatório!</strong>
  										</div>';
			
			header("Location: ver_cadastro.php?id=$id");
			exit();

		} else if(empty($localabordagem)) { //verificando se o campo senha está vazio, campo obrigatório

			$_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
  										<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
  										<strong>Atenção!! O campo Observação é de preenchimento obrigatório!</strong>
  										</div>';

  			header("Location: ver_cadastro.php?id=$id");
  			exit();

   		           
    } else { //Se os campos anteriores estiverm preenchidos no formulário, então, inserir os dados do formulário no Bando de Dados

try {
//Inserindo os dados selecionados no formulário, e a data-time do cadastro no banco de dados
    $sql_inserir = $conn->prepare("INSERT INTO abordagem (id_morador, data_abordagem, cidade, bairro, endereco, tempo_cidade, tempo_ficar, aceitou_encaminhamento, tipo_encaminhamento, tipo_curso, porta_objetos, objetos, relato, local_abordagem, condicao_ambiente, limpeza_ambiente, complemento, responsavel_abordagem) VALUES (:id, NOW(), :cidade, :bairro, :rua, :tempocidade, :tempoficar, :encaminhamento, :tipoencaminhamento, :tipocurso, :portaobjetos, :objetos, :relato, :localabordagem, :condicaoambiente, :limpezaambiente, :complemento, :responsavelabordagem)");
    
    $sql_inserir->bindParam( ':id', $id );
    $sql_inserir->bindParam( ':cidade', $cidade );
    $sql_inserir->bindParam( ':bairro', $bairro );
    $sql_inserir->bindParam( ':rua', $rua );
    $sql_inserir->bindParam( ':tempocidade', $tempocidade );
    $sql_inserir->bindParam( ':tempoficar', $tempoficar );
    $sql_inserir->bindParam( ':encaminhamento', $encaminhamento );
    $sql_inserir->bindParam( ':tipoencaminhamento', $tipoencaminhamento );
    $sql_inserir->bindParam( ':tipocurso', $tipocurso );
    $sql_inserir->bindParam( ':portaobjetos', $portaobjetos );
    $sql_inserir->bindParam( ':objetos', $objetos );
    $sql_inserir->bindParam( ':relato', $relato );
    $sql_inserir->bindParam( ':localabordagem', $localabordagem );
    $sql_inserir->bindParam( ':condicaoambiente', $condicaoambiente );
    $sql_inserir->bindParam( ':limpezaambiente', $limpezaambiente );
    $sql_inserir->bindParam( ':complemento', $complemento );
    $sql_inserir->bindParam( ':responsavelabordagem', $responsavelabordagem );
    
    $sql_inserir->execute();

    if ($sql_inserir->rowCount() > 0) {
    
      
    $sql_altera_cor = $conn->prepare("UPDATE abordagem SET cor=:cor WHERE id_morador = :idmorador");
    $sql_altera_cor->bindValue(':idmorador', $id, PDO::PARAM_INT);

    $sql_altera_cor->bindParam( ':cor', $cor );
    $sql_altera_cor->execute();

    $_SESSION['msg_registro'] = '<div class="alert alert-success alert-dismissable">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      <strong>Visita registrada com sucesso!</strong>
                      </div>';
      
      header("Location: ver_cadastro.php?id=$id");
  }

  } 

  catch(PDOException $e) {

      $_SESSION['msg_registro'] = '<div class="alert alert-danger alert-dismissable">
                      <a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>
                      <strong>Não foi possível cadastrar no banco de dados!</strong>
                      </div>';
      
      header("Location: ver_cadastro.php?id=$id");
      exit();
  }
}


?>