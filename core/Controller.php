<?php
namespace Core;

class Controller {
    protected $db;

    public function __construct() {
        $this->db = new Database();
    }

    protected function view($view, $data = []) {
        extract($data);
        require_once "../views/{$view}.php";
    }

    protected function redirect($url) {
        header("Location: $url");
        exit();
    }

    protected function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login.php');
        }
    }

    protected function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>