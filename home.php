<?php include('src/server.php');
//Conexão com o banco e seleção dos arquivos a serem mostrados na tabela
$db = mysqli_connect('localhost', 'root', '', 'ftp');
mysqli_select_db($db, 'ftp');
$id = $_SESSION['id'];
$sql = "SELECT * FROM `files` WHERE id_user='$id'";
$con = mysqli_query($db, $sql);

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
        <h2>Home<h2>
    </div>

    <div class="content">
        <!--Inicia a seção-->
        <?php if (isset($_SESSION['success'])):?>
        <div class="error success">
            <h3>
                <?php
                        echo $_SESSION['success'];
            unset($_SESSION['success']);

            ?>
            </h3>
        </div>

        <?php endif?>
        <!--Botão para encerrar a seção-->
        <?php if (isset($_SESSION['username'])):?>
        <p>Bem vindo
            <strong><?php echo $_SESSION['username'] ?>
            </strong>
            <a href="home.php?logout='1'" style="color: red; margin-left: 50%; font-weight: bolder;">Sair</a>
        </p>
        <?php endif?>
    </div>
    <!--Formulário de upload-->
    <div class="upload">
        <form action="home.php" method="post" enctype="multipart/form-data">
            <!-- Mostra os erros de validação aqui -->
            <?php include('src/errors.php'); ?>
            <label>Selecione um arquivo para fazer upload</label>
            <input type="file" name="file" class="submit">
            <br>
            <input type="submit" name="submit" value="Upload" class="submit">

        </form>
    </div>

    <div class="footer">
        <h2>Arquivos<h2>
    </div>

    <div class="view">
        <table class="table" border="5">
            <tr>
                <td>Código</td>
                <td>Arquivo</td>
                <td>Caminho</td>
                <td>Ação</td>
            </tr>
            <?php while ($dado = $con->fetch_array()): ?>
            <tr>
                <td> <?php echo $dado["id"];?>
                </td>
                <td><a href="files/<?php echo $dado["name"];?>"
                        download="<?php echo $dado["name"];?>">
                        <?php echo $dado["name"];?>
                    </a></td>
                <td> <?php echo $dado["path"];?>
                </td>
                <td><a
                        href="editar.php?codigo=<?php echo $dado["id"]?>">Editar</a>
                    |
                    <a href="home.php?excluir=<?php echo $dado["id"]?>"
                        onclick="return confirm('Deseja mesmo excluir o arquivo?');">Excluir</a>
                </td>
            </tr>
            <?php endwhile?>
        </table>


    </div>



</body>


</html>