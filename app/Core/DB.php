<?php
namespace App\Core;

use PDO;
use PDOException;

class DB
{
    private static $pdo = null;
    
    public static function init()
    {
        if (self::$pdo === null) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
                self::$pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
    }
    
    public static function q($sql, $params = [])
    {
        try {
            $stmt = self::$pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage() . " SQL: $sql");
            throw $e;
        }
    }
    
    public static function fetch($sql, $params = [])
    {
        $stmt = self::q($sql, $params);
        return $stmt->fetch();
    }
    
    public static function fetchAll($sql, $params = [])
    {
        $stmt = self::q($sql, $params);
        return $stmt->fetchAll();
    }
    
    public static function lastInsertId()
    {
        return self::$pdo->lastInsertId();
    }
    
    public static function beginTransaction()
    {
        return self::$pdo->beginTransaction();
    }
    
    public static function commit()
    {
        return self::$pdo->commit();
    }
    
    public static function rollback()
    {
        return self::$pdo->rollback();
    }
    
    public static function escape($string)
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    public static function columnExists($table, $column)
    {
        $result = self::fetch("
            SELECT COUNT(*) as count
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = ?
        ", [$table, $column]);

        return (int)($result['count'] ?? 0) > 0;
    }
}
