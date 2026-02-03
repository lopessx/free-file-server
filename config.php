<?php

// Configurações do Banco de Dados
define('DB_HOST', 'localhost');
define('DB_NAME', 'free_file_server');
define('DB_USER', 'root');
define('DB_PASS', 'sua_senha_segura');
define('DB_CHARSET', 'utf8mb4');

// Configurações de Path (Caminhos) - Já adiantando seu sistema de arquivos
define('UPLOAD_DIR', __DIR__ . '/files/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // Ex: Limite de 5MB