<?php
namespace Controllers;

use Core\Controller;

class DashboardController extends Controller {
    private $visitorModel;

    public function __construct() {
        parent::__construct();
        session_start();
        $this->requireAuth();
        
        // Manually require the Visitor model
        require_once __DIR__ . '/../models/Visitor.php';
        $this->visitorModel = new \Models\Visitor();
    }

    public function index() {
        $visitors = $this->visitorModel->getAll();
        $first_timers = $this->visitorModel->getFirstTimers();
        $statistics = $this->visitorModel->getStatistics();

        $data = [
            'visitors' => $visitors,
            'first_timers' => $first_timers,
            'statistics' => $statistics,
            'user' => [
                'fullname' => $_SESSION['fullname'],
                'role' => $_SESSION['role']
            ]
        ];

        $this->render('dashboard/index', $data);
    }
}
?>