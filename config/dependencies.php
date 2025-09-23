<?php
declare(strict_types=1);

use function DI\create;
use function DI\get;
use PDO;

return [

    'settings' => [
        'displayErrorDetails' => (bool)($_ENV['DISPLAY_ERROR_DETAILS'] ?? true),
        'cors' => [
            'origin' => $_ENV['CORS_ORIGIN'] ?? 'http://localhost:3000',
        ],
        'db_path' => $_ENV['DB_PATH'] ?? (dirname(__DIR__) . '/var/app.db'),
    ],

    PDO::class => function () {
        $dbPath = $_ENV['DB_PATH'] ?? (dirname(__DIR__) . '/var/app.db');

         $dir = dirname($dbPath);
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        if (!is_writable($dir)) {
            throw new RuntimeException("DB dir not writable: $dir");
        }

        $pdo = new PDO('sqlite:' . $dbPath, null, null, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
         $pdo->exec('PRAGMA foreign_keys = ON;');

        return $pdo;
    },

    App\Repository\EventRepository::class  => create()->constructor(get(PDO::class)),
    App\Repository\StageRepository::class  => create()->constructor(get(PDO::class)),
];

