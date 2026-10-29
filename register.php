<?php include('src/application.php'); ?>
<!DOCTYPE html>
<html>
<head>
    <title>OPEN FTP</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel="shortcut icon" href="thumbnails/openftplogo.ico" type="image/x-icon">
</head>
<body>
    <div class="header">
        <img src="thumbnails/openftplogo.png" alt="OPENFTP logo" width="50" height="50" class="logo">
        <h2>Register</h2>
    </div>
    <form method="post" action="register.php">
        <?php include('src/error_messages.php'); ?>
        <div class="input-group">
            <label>Username</label>
            <input type="text" name="username" required value="<?php echo htmlspecialchars($username); ?>">
        </div>
        <div class="input-group">
            <label>Email</label>
            <input type="email" name="email" required value="<?php echo htmlspecialchars($email); ?>">
        </div>
        <div class="input-group">
            <label>Password</label>
            <input type="password" name="password" required minlength="12" maxlength="128">
        </div>
        <div class="input-group">
            <label>Confirm Password</label>
            <input type="password" name="password_confirmation" required minlength="12" maxlength="128">
        </div>
        <div class="input-group">
            <button type="submit" name="register" class="button">Register</button>
        </div>
        <p>Already a member? <a href="index.php">Login</a></p>
    </form>
</body>
</html>