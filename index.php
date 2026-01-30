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
		<h2>Login</h2>
	</div>
	<!--Formulário de login -->
	<form method="post" action="index.php">
		<!-- Mostra os erros de validação aqui -->
		<?php include('src/errors.php'); ?>

		<div class="input-group">
			<label>Email</label>
			<input type="email" name="email" required>
		</div>
		<div class="input-group">
			<label>Senha</label>
			<input type="password" name="password" required>
		</div>
		<div class="input-group">
			<button type="submit" name="login" class="btn">Login</button>
		</div>
		<!-- Link apontando para a página de registro -->
		<p>
			Ainda não é um membro? <a href="registro.php">Registrar</a>
		</p>
	</form>


</body>


</html>