<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$root = dirname(__DIR__);
$dotenv = Dotenv::createImmutable($root);
$dotenv->safeLoad();

$dbPath = $_ENV['DB_PATH'] ?? ($root . '/var/app.db');
$sqlFile = $root . '/migrations/001_init.sql';

if (!file_exists($sqlFile)) {
    fwrite(STDERR, "Migration file not found: $sqlFile\n");
    exit(1);
}

$pdo = new PDO('sqlite:' . $dbPath);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// ensure foreign keys
$pdo->exec('PRAGMA foreign_keys = ON;');

$sql = file_get_contents($sqlFile);
$pdo->exec($sql);

echo "Migrations applied OK\n";

