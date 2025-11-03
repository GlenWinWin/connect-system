<?php
namespace Models;

class User {
    private $pdo;

    public function __construct() {
        // Manually require the Database class
        require_once __DIR__ . '/../core/Database.php';
        $database = new \Core\Database();
        $this->pdo = $database->getConnection();
    }

    public function findByEmail($email) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $data['fullname'],
            $data['email'],
            $data['phone'],
            password_hash($data['password'], PASSWORD_DEFAULT),
            $data['role'] ?? 'member'
        ]);
    }

    public function validatePassword($password, $hashedPassword) {
        return password_verify($password, $hashedPassword);
    }

    public function emailExists($email) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }
}
?>