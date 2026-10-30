<?php
require_once __DIR__ . '/config.php';
if (!ALLOW_REGISTRATION) {
    http_response_code(403);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registration disabled</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="header"><h2>Registration disabled</h2></div>
    <main class="content">
        <p>New user registration is currently disabled.</p>
        <p><a href="index.php">Return to login</a></p>
    </main>
</body>
</html>
<?php
    exit;
}
include('src/application.php');
?>
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