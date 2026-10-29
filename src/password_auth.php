<?php

require_once __DIR__ . '/../config.php';

function generate_password_salt(): string
{
    return bin2hex(random_bytes(PASSWORD_SALT_BYTES));
}

function hash_password(string $password, string $authenticationSalt): string
{
    $saltedPassword = $authenticationSalt . ':' . $password;

    return password_hash($saltedPassword, PASSWORD_ALGORITHM, [
        'memory_cost' => PASSWORD_MEMORY_COST,
        'time_cost' => PASSWORD_TIME_COST,
        'threads' => PASSWORD_THREADS,
    ]);
}

function verify_password(string $password, string $authenticationSalt, string $passwordHash): bool
{
    if (!in_array(PASSWORD_ALGORITHM, password_algos(), true)) {
        throw new RuntimeException('The configured password algorithm is unavailable');
    }

    return password_verify($authenticationSalt . ':' . $password, $passwordHash);
}

function password_hash_needs_rehash(string $passwordHash): bool
{
    return password_needs_rehash($passwordHash, PASSWORD_ALGORITHM, [
        'memory_cost' => PASSWORD_MEMORY_COST,
        'time_cost' => PASSWORD_TIME_COST,
        'threads' => PASSWORD_THREADS,
    ]);
}
