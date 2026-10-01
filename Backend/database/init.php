<?php
// SQL.db elimina tablas. La inicialización solo se ejecuta por CLI con consentimiento explícito.
require_once __DIR__ . '/../../config.php';
if (PHP_SAPI !== 'cli' || !in_array('--reset', $argv ?? [], true)) {
    http_response_code(403);
    exit("Inicialización destructiva: ejecutar por CLI con --reset únicamente en una base de pruebas o nueva.\n");
}
if (!preg_match('/^[a-zA-Z0-9_]+$/D', DB_NAME)) exit("Nombre de base de datos no válido.\n");
$connection = new PDO(sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET), DB_USER, DB_PASS,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$connection->exec(str_replace('delivery_db', DB_NAME, file_get_contents(__DIR__ . '/../../SQL.db')));
echo "Base de datos inicializada.\n";
