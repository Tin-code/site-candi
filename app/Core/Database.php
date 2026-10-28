<?php

namespace App\Core;

use PDO;
use PDOException;

use function App\Core\env;

/**
 * Singleton PDO — une seule connexion par cycle de vie.
 */
class Database
{
    private static ?PDO $instance = null;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = env('DB_HOST', '127.0.0.1');
            $port    = env('DB_PORT', '3306');
            $name    = env('DB_NAME', 'site_candi');
            $user    = env('DB_USER', 'root');
            $pass    = env('DB_PASS', '');
            $charset = env('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE utf8mb4_unicode_ci",
                ]);
            } catch (PDOException $e) {
                if (env('APP_DEBUG', false)) {
                    throw $e;
                }
                error_log('DB connection failed: ' . $e->getMessage());
                http_response_code(500);
                die('Erreur de connexion à la base de données.');
            }
        }

        return self::$instance;
    }

    /** Empêche l'instanciation directe. */
    private function __construct() {}
}
