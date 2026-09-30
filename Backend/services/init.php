<?php
/**
 * Fast Delivery - Script de Inicialización de Base de Datos
 * Ejecuta el archivo SQL.db para crear tablas y sembrar datos de prueba
 */

require_once __DIR__ . '/conexion.php';

try {
    echo "--- Iniciando configuración de Base de Datos ---\n";
    $success = Database::initializeDatabase();
    if ($success) {
        echo "Base de datos 'delivery_db' y tablas inicializadas con éxito.\n";
    } else {
        echo "Aviso: No se pudo ejecutar automáticamente initializeDatabase. Verificando tablas...\n";
    }

    $db = getDB();
    $stmt = $db->query("SHOW TABLES;");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas encontradas: " . implode(", ", $tables) . "\n";

    $userCount = $db->query("SELECT COUNT(*) FROM usuarios")->fetchColumn();
    $prodCount = $db->query("SELECT COUNT(*) FROM productos")->fetchColumn();
    echo "Usuarios registrados: {$userCount}\n";
    echo "Productos en catálogo: {$prodCount}\n";
    echo "--- Sistema listo para operar en EC2 / RDS o Local ---\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
