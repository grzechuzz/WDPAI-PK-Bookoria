<?php


class Database {
    private static ?PDO $conn = null;

    public static function connect(): PDO 
    {
        if (self::$conn !== null) {
            return self::$conn;
        }

        $host = $_ENV['DB_HOST'] ?? 'db';
        $port = $_ENV['DB_PORT'] ?? '5432';
        $db   = $_ENV['DB_NAME'] ?? 'db';
        $user = $_ENV['DB_USER'] ?? 'docker';
        $pass = $_ENV['DB_PASSWORD'] ?? 'docker';

        $pdo = new PDO("pgsql:host=$host;port=$port;dbname=$db", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        self::$conn = $pdo;
        return $pdo;
    }
}