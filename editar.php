<?php include('src/server.php'); //Para garantir que haja conexão com o banco
//Conexão com o banco e seleção dos arquivos a serem mostrados na tabela
$db = mysqli_connect('localhost', 'root', '', 'ftp');
mysqli_select_db($db, 'ftp');
$id = $_SESSION['id'];
$sql = "SELECT * FROM `files` WHERE id_user='$id'";
$con = mysqli_query($db, $sql);
$dado = $con->fetch_array();
$cod_arquivo = $_GET['codigo'];
?>
<!DOCTYPE html>
<html>

<head>
    <title>OPEN FTP</title>
    <!-- Esse link está apontando para o style.css para fazer a estilização das páginas -->
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="sortcut icon" href="thumbnails/openftplogo.ico" type="image/x-icon" />
</head>

<body>
    <div class="header">
        <img src="thumbnails/openftplogo.png" alt="Logomarca OPENFTP" width=50 height=50 class="img">
        <h2>Editar Arquivo</h2>
    </div>





    <!--Formulário de edição de arquivo -->
    <form method="post" action="home.php">
        <!-- Mostra os erros de validação aqui -->
        <?php include('src/errors.php'); ?>

        <div class="input-group">
            <label>Digite o novo nome do arquivo</label>
            <input type="name" name="file_past"
                value='<?php echo $dado['name'] ?>'
                class="file_id">
            <input type="name" name="file">
            <input type="number" name="codigo"
                value='<?php echo $cod_arquivo ?>' class="file_id">
        </div>
        <div class="input-group">
            <button type="submit" name="confirmar" class="btn">Confirmar</button>
            <button type="submit" name="cancelar" class="btn">Cancelar</button>
        </div>

    </form>

    <div class="view">
        <table class="table" border="5">
            <tr>
                <td>Código</td>
                <td>Arquivo</td>
                <td>Caminho</td>

            </tr>

            <tr>
                <td> <?php echo $dado["id"];?>
                </td>
                <td> <?php echo $dado["name"];?>
                </td>
                <td> <?php echo $dado["path"];?>
                </td>

            </tr>

        </table>


    </div>
</body>


</html>