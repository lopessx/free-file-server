<?php
require_once __DIR__ . '/config.php';

$message = '';
$error = '';

function database_schema_is_complete(): bool
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($connection === false) {
        return false;
    }
    mysqli_set_charset($connection, DB_CHARSET);

    $requiredColumns = [
        'users' => ['id', 'username', 'password_hash', 'password_salt', 'email', 'last_login', 'created_at', 'updated_at'],
        'files' => ['id', 'filename', 'path', 'user_id', 'created_at', 'updated_at']
    ];

    foreach ($requiredColumns as $table => $columns) {
        $tableEscaped = mysqli_real_escape_string($connection, $table);
        $result = mysqli_query($connection, "SHOW TABLES LIKE '$tableEscaped'");
        if (!$result || mysqli_num_rows($result) !== 1) {
            mysqli_close($connection);
            return false;
        }

        foreach ($columns as $column) {
            $columnEscaped = mysqli_real_escape_string($connection, $column);
            $columnResult = mysqli_query($connection, "SHOW COLUMNS FROM `$tableEscaped` LIKE '$columnEscaped'");
            if (!$columnResult || mysqli_num_rows($columnResult) !== 1) {
                mysqli_close($connection);
                return false;
            }
        }
    }

    mysqli_close($connection);
    return true;
}

if (database_schema_is_complete()) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Not Found';
    exit;
}

function database_connection(bool $includeDatabase = true): mysqli|false
{
    mysqli_report(MYSQLI_REPORT_OFF);
    $connection = $includeDatabase
        ? mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME)
        : mysqli_connect(DB_HOST, DB_USER, DB_PASS);
    if ($connection) {
        mysqli_set_charset($connection, DB_CHARSET);
    }
    return $connection;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['test_connection'])) {
        $connection = database_connection(false);
        if ($connection === false) {
            $error = 'The database connection failed. Verify the credentials in config.php.';
        } else {
            $statement = mysqli_prepare($connection, 'SELECT 1');
            if ($statement && mysqli_stmt_execute($statement)) {
                $message = 'The database connection was successful.';
                mysqli_stmt_close($statement);
            } else {
                $error = 'The database server is reachable, but the connection test failed.';
            }
            mysqli_close($connection);
        }
    }

    if (isset($_POST['install'])) {
        $connection = database_connection(false);
        if ($connection === false) {
            $error = 'The database connection failed. Verify the credentials in config.php.';
        } else {
            $databaseName = str_replace('`', '``', DB_NAME);
            $charset = str_replace("'", "''", DB_CHARSET);
            if (!mysqli_query($connection, "CREATE DATABASE IF NOT EXISTS `$databaseName` CHARACTER SET $charset COLLATE utf8mb4_general_ci")) {
                $error = 'Unable to create the database.';
            } elseif (!mysqli_select_db($connection, DB_NAME)) {
                $error = 'Unable to select the database.';
            } else {
                $queries = [
                    "CREATE TABLE IF NOT EXISTS users (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        username VARCHAR(255) NOT NULL,
                        password_hash VARCHAR(255) DEFAULT NULL,
                        password_salt VARCHAR(64) DEFAULT NULL,
                        email VARCHAR(255) NOT NULL,
                        last_login DATETIME DEFAULT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME NOT NULL,
                        UNIQUE KEY users_email_unique (email)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
                    "CREATE TABLE IF NOT EXISTS files (
                        id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        filename VARCHAR(255) DEFAULT NULL,
                        path VARCHAR(255) DEFAULT NULL,
                        user_id INT DEFAULT NULL,
                        created_at DATETIME NOT NULL,
                        updated_at DATETIME NOT NULL,
                        KEY files_user_id_index (user_id),
                        CONSTRAINT files_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
                ];

                foreach ($queries as $query) {
                    if (!mysqli_query($connection, $query)) {
                        $error = 'Installation failed: ' . htmlspecialchars(mysqli_error($connection));
                        break;
                    }
                }

                if ($error === '') {
                    $columns = [
                        'users' => [
                            'last_login' => 'ADD COLUMN last_login DATETIME DEFAULT NULL AFTER email',
                            'created_at' => 'ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER last_login',
                            'updated_at' => 'ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at'
                        ],
                        'files' => [
                            'created_at' => 'ADD COLUMN created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
                            'updated_at' => 'ADD COLUMN updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'
                        ]
                    ];
                    foreach ($columns as $table => $definitions) {
                        foreach ($definitions as $column => $alter) {
                            $columnEscaped = mysqli_real_escape_string($connection, $column);
                            $existing = mysqli_query($connection, "SHOW COLUMNS FROM `$table` LIKE '$columnEscaped'");
                            if ($existing && mysqli_num_rows($existing) === 0) {
                                if (!mysqli_query($connection, "ALTER TABLE `$table` $alter")) {
                                    $error = 'Installation failed while adding the column: ' . htmlspecialchars(mysqli_error($connection));
                                    break 2;
                                }
                            }
                        }
                    }
                }

                if ($error === '') {
                    $configPath = __DIR__ . '/config.php';
                    $configContent = file_get_contents($configPath);
                    $updatedConfig = preg_replace('/define\([\'\"]setup_project[\'\"],\s*(true|false)\s*\);/', "define('setup_project', false);", $configContent);
                    if ($updatedConfig !== null && is_writable($configPath)) {
                        file_put_contents($configPath, $updatedConfig);
                    }
                    $message = 'Installation completed successfully. The setup mode has been disabled in config.php.';
                }
            }
            mysqli_close($connection);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Project Setup</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="header"><h2>Project Setup</h2></div>
    <main class="content">
        <h3>What will be installed?</h3>
        <p>This setup will create the configured database and install the <strong>users</strong> and <strong>files</strong> tables with timestamps, indexes, relationships, and login tracking.</p>
        <p>Database connection details are read from <strong>config.php</strong>.</p>
        <?php if ($message !== ''): ?><div class="error success"><?php echo $message; ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="error"><?php echo $error; ?></div><?php endif; ?>
        <form method="post" class="setup-actions">
            <button type="submit" name="test_connection" class="button">Test Database Connection</button>
            <button type="submit" name="install" class="button">Install</button>
        </form>
        <?php if ($message !== '' && str_starts_with($message, 'Installation')): ?><p><a href="index.php">Continue to the application</a></p><?php endif; ?>
    </main>
</body>
</html>
