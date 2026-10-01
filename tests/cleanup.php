<?php
ini_set('session.save_path', __DIR__ . '/../.runtime');
require_once __DIR__ . '/../config.php';
$path = __DIR__ . '/../.runtime/test-db.json';
$name = json_decode(file_get_contents($path), true)['database'] ?? '';
if (!preg_match('/^fastdelivery_test_[a-f0-9]{12}$/D', $name)) throw new RuntimeException('Esquema de pruebas no válido.');
$root = new PDO(sprintf('mysql:host=%s;port=%s;charset=%s', DB_HOST, DB_PORT, DB_CHARSET), DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$root->exec("DROP DATABASE `{$name}`");
unlink($path);
foreach (glob(__DIR__ . '/../.runtime/sess_*') as $file) unlink($file);
echo "Esquema de pruebas eliminado.\n";
