<?php
declare(strict_types=1);

$databaseDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database';
$databasePath = $databaseDir . DIRECTORY_SEPARATOR . 'studybuddy.sqlite';

if (!is_dir($databaseDir)) {
    mkdir($databaseDir, 0755, true);
}

try {
    $pdo = openDatabase($databasePath);
    createTables($pdo);
} catch (PDOException $exception) {
    error_log('Database error: ' . $exception->getMessage());

    if (isRecoverableSqliteError($exception) && is_file($databasePath)) {
        $backupPath = $databaseDir . DIRECTORY_SEPARATOR . 'studybuddy.broken-' . date('Ymd-His') . '.sqlite';
        @rename($databasePath, $backupPath);

        try {
            $pdo = openDatabase($databasePath);
            createTables($pdo);
        } catch (PDOException $retryException) {
            error_log('Database retry error: ' . $retryException->getMessage());
            http_response_code(500);
            exit('Terjadi masalah saat menyiapkan database. Silakan coba lagi nanti.');
        }
    } else {
        http_response_code(500);
        exit('Terjadi masalah saat menyiapkan database. Silakan coba lagi nanti.');
    }
}

function openDatabase(string $databasePath): PDO
{
    $pdo = new PDO('sqlite:' . $databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $pdo;
}

function createTables(PDO $pdo): void
{
    // Tabel dibuat otomatis agar aplikasi bisa langsung dijalankan saat demo.
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS todos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            title TEXT NOT NULL,
            course TEXT,
            deadline TEXT,
            status TEXT DEFAULT 'belum',
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS moods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            mood TEXT NOT NULL,
            note TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS schedules (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subject TEXT NOT NULL,
            study_time TEXT,
            target TEXT,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS chat_logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_message TEXT NOT NULL,
            ai_response TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS study_plans (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_input TEXT NOT NULL,
            plan_result TEXT NOT NULL,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

function isRecoverableSqliteError(PDOException $exception): bool
{
    $message = strtolower($exception->getMessage());

    return str_contains($message, 'file is not a database')
        || str_contains($message, 'database disk image is malformed');
}
