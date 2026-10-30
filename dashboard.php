<?php include('src/application.php'); ?>
<?php
require_authenticated_user();
$user_id = (int)$_SESSION['id'];
$statement = mysqli_prepare($database, 'SELECT id, filename, path, created_at, updated_at FROM files WHERE user_id = ? ORDER BY id DESC');
mysqli_stmt_bind_param($statement, 'i', $user_id);
mysqli_stmt_execute($statement);
$result = mysqli_stmt_get_result($statement);
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
        <h2>Dashboard</h2>
    </div>

    <div class="content">
        <?php if (isset($_SESSION['success'])): ?>
        <div class="error success">
            <h3><?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?></h3>
        </div>
        <?php endif ?>
        <?php if (isset($_SESSION['username'])): ?>
        <p class="welcome-row"><span>Welcome
            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
            </span>
            <a class="logout-link" href="dashboard.php?logout=1">Logout</a>
        </p>
        <?php endif ?>
    </div>

    <div class="upload">
        <form action="dashboard.php" method="post" enctype="multipart/form-data">
            <?php include('src/error_messages.php'); ?>
            <label>Select a file to upload</label>
            <input type="file" name="file" class="upload-button">
            <br>
            <input type="submit" name="upload" value="Upload" class="upload-button">
        </form>
    </div>

    <div class="footer"><h2>Files</h2></div>
    <div class="view">
        <table class="table" border="5">
            <tr>
                <td>Code</td><td>File</td><td>Path</td><td>Action</td>
            </tr>
            <?php while ($file = $result->fetch_array()): ?>
            <tr>
                <td><?php echo htmlspecialchars($file['id']); ?></td>
                <td><a href="<?php echo UPLOAD_URL . '/' . rawurlencode($file['filename']); ?>" download="<?php echo htmlspecialchars($file['filename']); ?>"><?php echo htmlspecialchars($file['filename']); ?></a></td>
                <td><?php echo htmlspecialchars($file['path']); ?></td>
                <td><a href="edit.php?file_id=<?php echo urlencode($file['id']); ?>">Edit</a> |
                    <a href="dashboard.php?delete=<?php echo urlencode($file['id']); ?>" onclick="return confirm('Delete this file?');">Delete</a></td>
            </tr>
            <?php endwhile ?>
        </table>
    </div>
</body>
</html>