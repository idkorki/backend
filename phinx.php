<?php
$env = @parse_ini_file(__DIR__ . '/.env', false, INI_SCANNER_RAW) ?: [];
$driver = $env['DB_DRIVER'] ?? 'sqlite';

if ($driver === 'sqlite') {
    $path = $env['DB_PATH'] ?? './data/data.db';
    $abs  = __DIR__ . '/' . ltrim($path, './');
    @mkdir(dirname($abs), 0777, true);
    $config = ['adapter' => 'sqlite', 'name' => $abs];
} else {
    $config = [
        'adapter' => 'mysql',
        'host'    => $env['DB_HOST'] ?? 'localhost',
        'name'    => $env['DB_NAME'] ?? 'horki',
        'user'    => $env['DB_USER'] ?? 'root',
        'pass'    => $env['DB_PASS'] ?? '',
        'port'    => $env['DB_PORT'] ?? '3306',
        'charset' => 'utf8mb4',
    ];
}

return [
    'paths' => [
        'migrations' => 'database/migrations',
        'seeds'      => 'database/seeds',
    ],
    'environments' => [
        'default_migration_table' => 'phinxlog',
        'default_environment'     => 'dev',
        'dev' => $config,
    ],
    'version_order' => 'creation',
];
