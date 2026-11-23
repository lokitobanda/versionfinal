<?php
$config = require __DIR__ . '/env.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
    $config['db_host'] ?? 'localhost',
    (int) ($config['db_port'] ?? 3306),
    $config['db_name'] ?? 'skygames_db'
);

try {
    $pdo = new PDO($dsn, $config['db_user'] ?? 'root', $config['db_pass'] ?? '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    if (!empty($config['app_debug'])) {
        die('Error crítico de conexión: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
    }
    die('Error crítico de conexión con la base de datos.');
}
