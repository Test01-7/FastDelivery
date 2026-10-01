<?php
require_once __DIR__ . '/../../config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            self::$instance = new PDO(
                sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', DB_HOST, DB_PORT, DB_NAME, DB_CHARSET),
                DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                 PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                 PDO::ATTR_EMULATE_PREPARES => false]
            );
            $timezone = self::$instance->prepare('SET time_zone = ?');
            $timezone->execute([(new DateTimeImmutable())->format('P')]);
        }
        return self::$instance;
    }
}

function getDB(): PDO { return Database::getConnection(); }
