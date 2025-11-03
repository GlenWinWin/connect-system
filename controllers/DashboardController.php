<?php
namespace Controllers;

use Core\Controller;
use Models\Visitor;

class DashboardController extends Controller {
    private $visitorModel;

    public function __construct() {
        parent::__construct();
        session_start();
        $this->requireAuth();
        $this->visitorModel = new Visitor();
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

        $this->view('dashboard/index', $data);
    }
}
?>