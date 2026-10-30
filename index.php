<?php include('src/application.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>OPEN FTP</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="shortcut icon" href="thumbnails/openftplogo.ico" type="image/x-icon">
</head>
<body>
    <div class="header">
        <img src="thumbnails/openftplogo.webp" alt="OPENFTP logo" class="logo">
        <h2>Login</h2>
    </div>
    <form method="post" action="index.php">
        <?php include('src/error_messages.php'); ?>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required>
        </div>
        <div class="input-group">
            <button type="submit" name="login" class="button">Login</button>
        </div>
        <?php if (ALLOW_REGISTRATION): ?>
        <p>Not a member? <a href="register.php">Register</a></p>
        <?php endif; ?>
    </form>
</body>
</html>