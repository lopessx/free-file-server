<?php include('src/application.php'); ?>
<?php
require_authenticated_user();
$file_id = filter_var($_GET['file_id'] ?? null, FILTER_VALIDATE_INT);
$file = null;
if ($file_id !== false) {
    $user_id = (int)$_SESSION['id'];
    $statement = mysqli_prepare($database, 'SELECT id, filename, path FROM files WHERE id = ? AND user_id = ? LIMIT 1');
    mysqli_stmt_bind_param($statement, 'ii', $file_id, $user_id);
    mysqli_stmt_execute($statement);
    $result = mysqli_stmt_get_result($statement);
    $file = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($statement);
}
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
        <h2>Edit File</h2>
    </div>
    <form method="post" action="dashboard.php">
        <?php include('src/error_messages.php'); ?>
        <div class="input-group">
            <label>Enter the new filename</label>
            <input type="hidden" name="old_filename" value="<?php echo htmlspecialchars($file['filename'] ?? ''); ?>">
            <input type="text" name="new_filename">
            <input type="hidden" name="file_id" value="<?php echo htmlspecialchars($file_id); ?>">
        </div>
        <div class="input-group">
            <button type="submit" name="confirm" class="button">Confirm</button>
        </div>
    </form>
    <?php if ($file): ?>
    <div class="view">
        <table class="table" border="5">
            <tr><td>Code</td><td>File</td><td>Path</td></tr>
            <tr>
                <td><?php echo htmlspecialchars($file['id']); ?></td>
                <td><?php echo htmlspecialchars($file['filename']); ?></td>
                <td><?php echo htmlspecialchars($file['path']); ?></td>
            </tr>
        </table>
    </div>
    <?php endif ?>
</body>
</html>