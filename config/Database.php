<?php
namespace Config;

class Database {
    // Database configuration
    const DB_HOST = 'localhost';
    const DB_NAME = 'church_management';
    const DB_USER = 'root';
    const DB_PASS = '';
    const DB_CHARSET = 'utf8mb4';

    public static function getConfig() {
        return [
            'host' => self::DB_HOST,
            'database' => self::DB_NAME,
            'username' => self::DB_USER,
            'password' => self::DB_PASS,
            'charset' => self::DB_CHARSET
        ];
    }
}
?>