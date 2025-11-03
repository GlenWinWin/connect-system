<?php
namespace Controllers;

use Core\Controller;
use Models\Visitor;

class VisitorController extends Controller {
    private $visitorModel;

    public function __construct() {
        parent::__construct();
        $this->visitorModel = new Visitor();
    }

    public function form() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'iam' => $_POST['iam'] ?? '',
                'fullname' => $_POST['fullname'] ?? '',
                'contact' => $_POST['contact'] ?? '',
                'age_group' => $_POST['age_group'] ?? '',
                'messenger' => $_POST['messenger'] ?? '',
                'service_time' => $_POST['service_time'] ?? '',
                'invited_by' => $_POST['invited_by'] ?? '',
                'lifegroup' => $_POST['lifegroup'] ?? '',
                'connected_with' => $_POST['connected_with'] ?? '',
                'approached_by' => $_POST['approached_by'] ?? ''
            ];

            if ($this->visitorModel->create($data)) {
                $this->view('visitors/form', ['success' => 'Thank you for submitting your information! We will contact you soon.']);
            } else {
                $this->view('visitors/form', ['error' => 'There was an error submitting your form. Please try again.']);
            }
        } else {
            $this->view('visitors/form');
        }
    }
}
?>