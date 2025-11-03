<?php
namespace Core;

use PDO;
use PDOException;

class Database {
    private $host = 'localhost';
    private $username = 'root';
    private $password = '';
    private $database = 'church_management';
    private $pdo;

    public function __construct() {
        $this->connect();
        $this->createTables();
    }

    private function connect() {
        try {
            $this->pdo = new PDO(
                "mysql:host={$this->host};dbname={$this->database}", 
                $this->username, 
                $this->password
            );
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }

    private function createTables() {
        // Users table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            fullname VARCHAR(255) NOT NULL,
            email VARCHAR(255) UNIQUE NOT NULL,
            phone VARCHAR(20),
            password VARCHAR(255) NOT NULL,
            role ENUM('admin', 'member') DEFAULT 'member',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Visitors table
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS visitors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            iam VARCHAR(50) NOT NULL,
            fullname VARCHAR(255) NOT NULL,
            contact VARCHAR(20) NOT NULL,
            age_group VARCHAR(50) NOT NULL,
            messenger VARCHAR(255) NOT NULL,
            service_time VARCHAR(10) NOT NULL,
            invited_by VARCHAR(255),
            lifegroup ENUM('YES', 'NO') NOT NULL,
            connected_with VARCHAR(255) NOT NULL,
            approached_by VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");

        // Create default admin user
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'admin@church.com'");
        $stmt->execute();
        if ($stmt->fetchColumn() == 0) {
            $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $this->pdo->prepare("INSERT INTO users (fullname, email, password, role) VALUES (?, ?, ?, ?)")
                ->execute(['Administrator', 'admin@church.com', $hashedPassword, 'admin']);
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}
?>