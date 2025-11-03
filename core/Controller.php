<?php
namespace Core;

class Controller {
    protected $db;

    public function __construct() {
        // Manually require the Database class
        require_once __DIR__ . '/Database.php';
        $this->db = new Database();
    }

    protected function view($view, $data = []) {
        // Extract data to variables
        extract($data);
        
        // Start output buffering
        ob_start();
        
        // Include the view file with correct path
        $viewFile = __DIR__ . "/../views/{$view}.php";
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            die("View not found: {$viewFile}");
        }
        
        // Get buffered content and clean
        return ob_get_clean();
    }

    protected function render($view, $data = []) {
        $content = $this->view($view, $data);
        echo $content;
    }

    protected function redirect($url) {
        header("Location: $url");
        exit();
    }

    protected function isLoggedIn() {
        session_start();
        return isset($_SESSION['user_id']);
    }

    protected function requireAuth() {
        if (!$this->isLoggedIn()) {
            $this->redirect('/login.php');
        }
    }

    protected function isAdmin() {
        session_start();
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
}
?>