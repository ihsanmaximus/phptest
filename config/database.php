<?php
declare(strict_types=1);

/**
 * Opens the local SQLite database and applies the schema.
 * The database file is generated automatically on first launch.
 */
function database(): PDO
{
    $databaseDirectory = dirname(__DIR__) . '/database';
    if (!is_dir($databaseDirectory) && !mkdir($databaseDirectory, 0775, true) && !is_dir($databaseDirectory)) {
        throw new RuntimeException('Tidak dapat membuat folder database.');
    }

    $databaseFile = $databaseDirectory . '/inventory.sqlite';
    $pdo = new PDO('sqlite:' . $databaseFile, null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    $pdo->exec('PRAGMA foreign_keys = ON');
    $schema = file_get_contents($databaseDirectory . '/schema.sql');
    if ($schema === false) {
        throw new RuntimeException('File database/schema.sql tidak ditemukan.');
    }
    $pdo->exec($schema);

    return $pdo;
}
