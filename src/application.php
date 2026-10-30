<?php

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/router.php';
require_once __DIR__ . '/password_auth.php';

route_project();

$currentFile = basename(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''));

if (setup_project && $currentFile !== 'setup.php') {
    mysqli_report(MYSQLI_REPORT_OFF);
    $setupDatabase = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $schemaComplete = false;
    if ($setupDatabase !== false) {
        mysqli_set_charset($setupDatabase, DB_CHARSET);
        $requiredTablesAndColumns = [
            'users' => ['id', 'username', 'password_hash', 'password_salt', 'email', 'last_login', 'created_at', 'updated_at'],
            'files' => ['id', 'filename', 'path', 'user_id', 'created_at', 'updated_at']
        ];
        $schemaComplete = true;
        foreach ($requiredTablesAndColumns as $table => $columns) {
            $tableEscaped = mysqli_real_escape_string($setupDatabase, $table);
            $tableResult = mysqli_query($setupDatabase, "SHOW TABLES LIKE '$tableEscaped'");
            if (!$tableResult || mysqli_num_rows($tableResult) !== 1) {
                $schemaComplete = false;
                break;
            }
            foreach ($columns as $column) {
                $columnEscaped = mysqli_real_escape_string($setupDatabase, $column);
                $columnResult = mysqli_query($setupDatabase, "SHOW COLUMNS FROM `$tableEscaped` LIKE '$columnEscaped'");
                if (!$columnResult || mysqli_num_rows($columnResult) !== 1) {
                    $schemaComplete = false;
                    break 2;
                }
            }
        }
        mysqli_close($setupDatabase);
    }
    if (!$schemaComplete) {
        header('Location: setup.php');
        exit;
    }
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    $secureCookie = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secureCookie,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

function establish_user_session(int $userId, string $username, mysqli $database): void
{
    session_regenerate_id(true);
    $_SESSION['id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['session_ip'] = $_SERVER['REMOTE_ADDR'] ?? '';
    $_SESSION['session_browser'] = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');
    $_SESSION['last_activity'] = time();

    $statement = mysqli_prepare($database, 'UPDATE users SET last_login = NOW(), updated_at = NOW() WHERE id = ?');
    if ($statement) {
        mysqli_stmt_bind_param($statement, 'i', $userId);
        mysqli_stmt_execute($statement);
        mysqli_stmt_close($statement);
    }
}

function validate_session(): bool
{
    if (empty($_SESSION['id'])) {
        return false;
    }

    $currentIp = $_SERVER['REMOTE_ADDR'] ?? '';
    $currentBrowser = hash('sha256', $_SERVER['HTTP_USER_AGENT'] ?? '');

    if (!hash_equals((string)($_SESSION['session_ip'] ?? ''), $currentIp)
        || !hash_equals((string)($_SESSION['session_browser'] ?? ''), $currentBrowser)) {
        session_unset();
        session_destroy();
        return false;
    }

    $lastActivity = (int)($_SESSION['last_activity'] ?? 0);
    if ($lastActivity === 0 || time() - $lastActivity > session_timeout) {
        session_unset();
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    session_write_close();
    session_start();
    return true;
}

function require_authenticated_user(): void
{
    if (!validate_session()) {
        header('Location: index.php');
        exit;
    }
}

if (!empty($_SESSION['id'])) {
    validate_session();
}

$username = '';
$email = '';
$errors = [];

$database = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($database === false) {
    http_response_code(503);
    die('Database connection failed. Enable setup_project in config.php and complete setup.php, or verify the database configuration.');
}
mysqli_set_charset($database, DB_CHARSET);

if (isset($_GET['logout'])) {
    require_authenticated_user();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

if (isset($_POST['register'])) {
    if (!ALLOW_REGISTRATION) {
        $errors[] = 'New user registration is disabled';
    }

    $username = trim((string)($_POST['username'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $passwordConfirmation = (string)($_POST['password_confirmation'] ?? '');

    if ($username === '' || strlen($username) > 255) {
        $errors[] = $username === '' ? 'Username is required' : 'Username is too long';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 255) {
        $errors[] = 'A valid email is required';
    }
    if (strlen($password) < PASSWORD_MIN_LENGTH || strlen($password) > PASSWORD_MAX_LENGTH) {
        $errors[] = 'Password must contain between ' . PASSWORD_MIN_LENGTH . ' and ' . PASSWORD_MAX_LENGTH . ' characters';
    }
    if (!hash_equals($password, $passwordConfirmation)) {
        $errors[] = 'The passwords do not match';
    }

    if (!$errors) {
        $statement = mysqli_prepare($database, 'SELECT id FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 's', $email);
        mysqli_stmt_execute($statement);
        mysqli_stmt_store_result($statement);
        if (mysqli_stmt_num_rows($statement) > 0) {
            $errors[] = 'An account with this email already exists';
        }
        mysqli_stmt_close($statement);
    }

    if (!$errors) {
        $salt = generate_password_salt();
        $hash = hash_password($password, $salt);
        $statement = mysqli_prepare($database, 'INSERT INTO users (username, password_hash, password_salt, email, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())');
        mysqli_stmt_bind_param($statement, 'ssss', $username, $hash, $salt, $email);
        if (!mysqli_stmt_execute($statement)) {
            $errors[] = 'Unable to create account';
        } else {
            $userId = mysqli_insert_id($database);
            mysqli_stmt_close($statement);
            establish_user_session($userId, $username, $database);
            $_SESSION['success'] = 'You are now logged in';
            header('Location: dashboard.php');
            exit;
        }
        mysqli_stmt_close($statement);
    }
}

if (isset($_POST['login'])) {
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');

    if ($email === '') {
        $errors[] = 'Email is required';
    }
    if ($password === '') {
        $errors[] = 'Password is required';
    }

    if (!$errors) {
        $statement = mysqli_prepare($database, 'SELECT id, username, password_hash, password_salt FROM users WHERE email = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 's', $email);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($statement);

        $authenticated = false;
        if ($user) {
            if (!empty($user['password_salt']) && str_starts_with((string)$user['password_hash'], '$argon2')) {
                try {
                    $authenticated = verify_password($password, $user['password_salt'], $user['password_hash']);
                } catch (RuntimeException $exception) {
                    $authenticated = false;
                }
                if ($authenticated && password_hash_needs_rehash($user['password_hash'])) {
                    $newSalt = generate_password_salt();
                    $newHash = hash_password($password, $newSalt);
                    $update = mysqli_prepare($database, 'UPDATE users SET password_hash = ?, password_salt = ?, updated_at = NOW() WHERE id = ?');
                    mysqli_stmt_bind_param($update, 'ssi', $newHash, $newSalt, $user['id']);
                    mysqli_stmt_execute($update);
                    mysqli_stmt_close($update);
                }
            } elseif (strlen((string)$user['password_hash']) === 32 && hash_equals((string)$user['password_hash'], md5($password))) {
                $authenticated = true;
                $newSalt = generate_password_salt();
                $newHash = hash_password($password, $newSalt);
                $update = mysqli_prepare($database, 'UPDATE users SET password_hash = ?, password_salt = ?, updated_at = NOW() WHERE id = ?');
                mysqli_stmt_bind_param($update, 'ssi', $newHash, $newSalt, $user['id']);
                mysqli_stmt_execute($update);
                mysqli_stmt_close($update);
            }
        }

        if ($authenticated) {
            establish_user_session((int)$user['id'], $user['username'], $database);
            $_SESSION['success'] = 'You are now logged in';
            header('Location: dashboard.php');
            exit;
        }
        $errors[] = 'Email or password is incorrect';
    }
}

if (isset($_POST['upload'])) {
    require_authenticated_user();
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'No file selected or the upload failed';
    } else {
        $originalName = basename($_FILES['file']['name']);
        if ($_FILES['file']['size'] > MAX_FILE_SIZE) {
            $errors[] = 'The file exceeds the maximum size';
        } elseif (!is_dir(UPLOAD_DIR)) {
            $errors[] = 'The upload directory is not available';
        } else {
            $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
            $destination = UPLOAD_DIR . $safeName;
            if (move_uploaded_file($_FILES['file']['tmp_name'], $destination)) {
                $userId = (int)$_SESSION['id'];
                $statement = mysqli_prepare($database, 'INSERT INTO files (filename, path, user_id, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())');
                mysqli_stmt_bind_param($statement, 'ssi', $safeName, $destination, $userId);
                mysqli_stmt_execute($statement);
                mysqli_stmt_close($statement);
            } else {
                $errors[] = 'Unable to upload the file';
            }
        }
    }
}

if (isset($_GET['delete'])) {
    require_authenticated_user();
    $fileId = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
    if ($fileId !== false) {
        $userId = (int)$_SESSION['id'];
        $statement = mysqli_prepare($database, 'SELECT filename FROM files WHERE id = ? AND user_id = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'ii', $fileId, $userId);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $file = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);
        if ($file) {
            @unlink(UPLOAD_DIR . basename($file['filename']));
            $delete = mysqli_prepare($database, 'DELETE FROM files WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($delete, 'ii', $fileId, $userId);
            mysqli_stmt_execute($delete);
            mysqli_stmt_close($delete);
        }
    }
}

if (isset($_POST['confirm'])) {
    require_authenticated_user();
    $fileId = filter_var($_POST['file_id'] ?? null, FILTER_VALIDATE_INT);
    $newFilename = trim((string)($_POST['new_filename'] ?? ''));
    $oldFilename = trim((string)($_POST['old_filename'] ?? ''));

    if (!$fileId || $newFilename === '' || $oldFilename === '') {
        $errors[] = 'Invalid file or filename';
    } else {
        $newFilename = preg_replace('/[^A-Za-z0-9._-]/', '_', $newFilename);
        $userId = (int)$_SESSION['id'];
        $statement = mysqli_prepare($database, 'SELECT filename FROM files WHERE id = ? AND user_id = ? LIMIT 1');
        mysqli_stmt_bind_param($statement, 'ii', $fileId, $userId);
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $file = mysqli_fetch_assoc($result);
        mysqli_stmt_close($statement);
        if (!$file) {
            $errors[] = 'Invalid file';
        } elseif (rename(UPLOAD_DIR . basename($oldFilename), UPLOAD_DIR . $newFilename)) {
            $newPath = UPLOAD_DIR . $newFilename;
            $update = mysqli_prepare($database, 'UPDATE files SET filename = ?, path = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
            mysqli_stmt_bind_param($update, 'ssii', $newFilename, $newPath, $fileId, $userId);
            mysqli_stmt_execute($update);
            mysqli_stmt_close($update);
            header('Location: dashboard.php');
            exit;
        } else {
            $errors[] = 'Unable to rename the file';
        }
    }
}
