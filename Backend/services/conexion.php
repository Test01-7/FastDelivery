<?php
/**
 * Fast Delivery - Servicio de Conexión a Base de Datos (PDO)
 * Optimizado para MySQL 8.0+ en AWS RDS y Servidores Locales
 */

require_once __DIR__ . '/../../config.php';

class Database {
    private static ?PDO $instance = null;

    /**
     * Obtiene la instancia única de conexión PDO (Patrón Singleton)
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = sprintf(
                    "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                    DB_HOST,
                    DB_PORT,
                    DB_NAME,
                    DB_CHARSET
                );

                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
                ];

                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);

            } catch (PDOException $e) {
                // Si la base de datos no existe en el host, intentamos crearla si es local/inicial
                if ($e->getCode() == 1049) { // Unknown database
                    self::initializeDatabase();
                    return self::getConnection();
                }

                error_log("Error de conexión a la base de datos RDS/MySQL: " . $e->getMessage());
                throw new Exception("Error al conectar con la base de datos: " . $e->getMessage());
            }
        }

        return self::$instance;
    }

    /**
     * Inicializa la base de datos importando el archivo SQL.db si no existe
     */
    public static function initializeDatabase(): bool {
        try {
            $rootDsn = sprintf("mysql:host=%s;port=%s;charset=%s", DB_HOST, DB_PORT, DB_CHARSET);
            $pdoRoot = new PDO($rootDsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]);

            $sqlPath = __DIR__ . '/../../SQL.db';
            if (!file_exists($sqlPath)) {
                return false;
            }

            $sqlContent = file_get_contents($sqlPath);
            
            // Ejecutar sentencias por bloques
            $pdoRoot->exec("CREATE DATABASE IF NOT EXISTS `" . DB_NAME . "` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            $pdoRoot->exec("USE `" . DB_NAME . "`;");
            
            // Ejecutar el script completo
            $pdoRoot->exec($sqlContent);
            return true;
        } catch (PDOException $ex) {
            error_log("Error al inicializar la base de datos con SQL.db: " . $ex->getMessage());
            return false;
        }
    }
}

// Función helper global para conveniencia
function getDB(): PDO {
    return Database::getConnection();
}
