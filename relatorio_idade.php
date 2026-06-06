<?php
session_start();
header('Location: dashboard.php');
exit;

<div class="page-header">
    <div class="container">
        <h1>Relatório por Faixa Etária</h1>
        <p class="sub">Distribuição de moradores por idade</p>
    </div>
</div>

<div class="container" style="padding-top:20px;padding-bottom:60px">
<br/>
<div>
    <form name="filtrar" method="GET" class="form-inline">
        <div class="form-group">
            <label for="idade">Faixa Etária</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
            <select name="idade" class="form-control">
                <option value="">Selecione</option>
                <?php
                $stmt = $conn->prepare("SELECT * FROM intervalo ORDER BY descricao");
                $stmt->execute();
                while ($row = $stmt->fetch()) {
                    echo '<option value="' . $row['id'] . '">' . $row['descricao'] . '</option>';
                }
                ?>
            </select>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
        </div>
        <input type="submit" value="Pesquisar" class="btn btn-primary"/>
    </form>
</div>
<br/>
<div class="fontecontainer">
  <div class="panel panel-default">
    <div class="panel-heading">
        <p>📊 Registros por Idade</p>
    </div>
    <div class="panel-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Faixa Etária</th>
                    <th>Quantidade</th>
                </tr>
            </thead>
<?php
$idade = isset($_GET['idade']) ? $_GET['idade'] : '';

$selecionado = "SELECT
                    i.descricao,
                    COUNT(c.id) AS total
                FROM cadastro c
                INNER JOIN intervalo i
                    ON DATE_PART('year', AGE(c.data_nascimento)) BETWEEN i.idademinima AND i.idademaxima
                WHERE (:idade = '' OR i.id = :idadeid)
                GROUP BY i.descricao
                ORDER BY i.descricao";

$result_sql = $conn->prepare($selecionado);
$result_sql->bindValue(':idade',   $idade);
$result_sql->bindValue(':idadeid', $idade ?: 0, PDO::PARAM_INT);
$result_sql->execute();

while ($resultado = $result_sql->fetch()) {
    echo '<tbody><tr>';
    echo '<td>' . $resultado['descricao'] . '</td>';
    echo '<td>' . $resultado['total']     . '</td>';
    echo '</tr></tbody>';
}
?>
        </table>
    </div>
  </div>
</div>
</div>
<?php include 'includes/footer.php'; ?>
