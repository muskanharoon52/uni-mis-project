<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $serverDsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $server = new PDO($serverDsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    ensure_lms_columns($pdo);

    return $pdo;
}

function ensure_lms_columns(PDO $pdo): void
{
    ensure_column($pdo, 'users', 'login_id', "VARCHAR(20) NULL");
    ensure_column($pdo, 'users', 'profile_photo', 'VARCHAR(255) NULL');

    ensure_column($pdo, 'courses', 'description', 'TEXT NULL');
    ensure_column($pdo, 'courses', 'semester_name', "VARCHAR(40) NULL");
    ensure_column($pdo, 'courses', 'teacher_id', 'INT NULL');

    ensure_column($pdo, 'attendance', 'teacher_id', 'INT NULL');
}

function ensure_column(PDO $pdo, string $table, string $column, string $definition): void
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([DB_NAME, $table, $column]);

    if ((int) $stmt->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE `$table` ADD `$column` $definition");
    }
}
