<?php
/**
 * Fast Delivery - Configuración del Sistema
 * Compatible con Servidor Local (XAMPP / Laragon / CLI) y AWS (EC2 + RDS MySQL)
 */

// Cargar variables de entorno si existen (compatibilidad AWS EC2 / Docker / DotEnv)
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value, " \t\n\r\0\x0B\"'");
        if (!getenv($name)) {
            putenv("{$name}={$value}");
            $_ENV[$name] = $value;
        }
    }
}

// Parámetros de Base de Datos (AWS RDS o Local)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_NAME', getenv('DB_NAME') ?: 'delivery_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Proyecto académico: acceso rápido y formularios simplificados para la exposición.
define('DEMO_MODE', getenv('DEMO_MODE') === false ? true : filter_var(getenv('DEMO_MODE'), FILTER_VALIDATE_BOOLEAN));

// URL Base del Proyecto (Detecta automáticamente protocolo y host)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost:8000';
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$baseDir = preg_replace('/(\/Frontend|\/Backend.*)$/', '', $scriptDir);
define('BASE_URL', rtrim($protocol . $host . $baseDir, '/'));

// Iniciar sesión si no está iniciada
date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Lima');
if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_start();
}
