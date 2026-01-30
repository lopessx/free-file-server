<?php include('src/server.php'); //Para garantir que haja conexão com o banco?>
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
        <h2>Registro</h2>
    </div>
    <!-- Formulário de cadastro -->
    <form method="post" action="registro.php">
        <!-- Mostra os erros de validação aqui -->
        <?php include('src/errors.php'); ?>

        <!-- Inputs para que o usuário preencha com as informações requeridas no cadastro -->
        <div class="input-group">
            <label>Usuario</label>
            <input type="text" name="username" required
                value="<?php echo $username ?>">
        </div>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required
                value="<?php echo $email ?>">
        </div>
        <div class="input-group">
            <label>Senha</label>
            <input type="password" name="password_1" required>
        </div>
        <div class="input-group">
            <label>Confirmar Senha</label>
            <input type="password" name="password_2" required>
        </div>
        <!-- Botão que faz o registro e manda as informações para o servidor-->
        <div class="input-group">
            <button type="submit" name="register" class="btn">Registrar</button>
        </div>
        <p>
            <!-- link para a página de login -->
            Já é um membro? <a href="index.php">Entrar</a>
        </p>
    </form>


</body>


</html>