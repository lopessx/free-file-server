<?php

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'free_file_server');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: 'your_secure_password');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');
define('setup_project', filter_var(getenv('SETUP_PROJECT') ?: 'false', FILTER_VALIDATE_BOOLEAN));
define('ALLOW_REGISTRATION', filter_var(getenv('ALLOW_REGISTRATION') ?: 'true', FILTER_VALIDATE_BOOLEAN));

// Session configuration (7200 seconds = 2 hours)
define('session_timeout', 7200);

// File storage configuration
define('UPLOAD_DIR', __DIR__ . '/files/');
define('UPLOAD_URL', 'files');
define('MAX_FILE_SIZE', 5 * 1024 * 1024);

// Password authentication configuration
// Argon2id is a memory-hard password KDF designed to resist GPU attacks
// and remain comparatively strong against quantum search attacks.
define('PASSWORD_ALGORITHM', PASSWORD_ARGON2ID);
define('PASSWORD_MEMORY_COST', 65536); // 64 MiB
define('PASSWORD_TIME_COST', 4);
define('PASSWORD_THREADS', 3);
define('PASSWORD_SALT_BYTES', 32);
define('PASSWORD_MIN_LENGTH', 12);
define('PASSWORD_MAX_LENGTH', 128);