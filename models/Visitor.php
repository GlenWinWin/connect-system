<?php
namespace Models;

class Visitor {
    private $pdo;

    public function __construct() {
        // Manually require the Database class
        require_once __DIR__ . '/../core/Database.php';
        $database = new \Core\Database();
        $this->pdo = $database->getConnection();
    }

    public function create($data) {
        $stmt = $this->pdo->prepare(
            "INSERT INTO visitors (iam, fullname, contact, age_group, messenger, service_time, invited_by, lifegroup, connected_with, approached_by) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        
        return $stmt->execute([
            $data['iam'],
            $data['fullname'],
            $data['contact'],
            $data['age_group'],
            $data['messenger'],
            $data['service_time'],
            $data['invited_by'] ?? '',
            $data['lifegroup'],
            $data['connected_with'],
            $data['approached_by']
        ]);
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM visitors ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getFirstTimers() {
        $stmt = $this->pdo->query("SELECT * FROM visitors WHERE iam = 'First Timer' ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getStatistics() {
        $total_visitors = $this->pdo->query("SELECT COUNT(*) FROM visitors")->fetchColumn();
        $total_first_timers = $this->pdo->query("SELECT COUNT(*) FROM visitors WHERE iam = 'First Timer'")->fetchColumn();
        $total_regulars = $this->pdo->query("SELECT COUNT(*) FROM visitors WHERE iam = 'Regular'")->fetchColumn();
        $total_members = $this->pdo->query("SELECT COUNT(*) FROM visitors WHERE iam = 'Member'")->fetchColumn();

        return [
            'total_visitors' => $total_visitors,
            'total_first_timers' => $total_first_timers,
            'total_regulars' => $total_regulars,
            'total_members' => $total_members
        ];
    }
}
?>