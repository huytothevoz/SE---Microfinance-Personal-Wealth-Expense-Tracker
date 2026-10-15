<?php
declare(strict_types=1);

namespace WealthFlow;

use PDO;

final class Database
{
    public static function connect(Config $config, bool $selectDatabase = true): PDO
    {
        $name = $config->get('DB_NAME');
        if (!preg_match('/^[A-Za-z0-9_]+$/D', $name)) {
            throw new \RuntimeException('Invalid database name.');
        }
        $dsn = 'mysql:host=' . $config->get('DB_HOST') . ';port=' . $config->get('DB_PORT')
            . ($selectDatabase ? ';dbname=' . $name : '') . ';charset=utf8mb4';
        $pdo = new PDO($dsn, $config->get('DB_USER'), $config->get('DB_PASSWORD'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $pdo->exec("SET time_zone = '+00:00'");
        return $pdo;
    }
}
