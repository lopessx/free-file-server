<?php

function route_path(): string
{
    $scriptName = str_replace('\\', '/', parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    return strtolower(trim($scriptName, '/'));
}

function route_project(): void
{
    $scriptName = str_replace('\\', '/', parse_url($_SERVER['SCRIPT_NAME'] ?? '', PHP_URL_PATH));
    $file = basename($scriptName);

    $allowedRoutes = [
        '', 'index.php', 'register.php', 'dashboard.php', 'edit.php',
        'setup.php', '404.php'
    ];

    if (!in_array($file, $allowedRoutes, true) && $file !== '' && !str_ends_with($file, '.css')) {
        http_response_code(404);
        header('Location: 404.php');
        exit;
    }
}
