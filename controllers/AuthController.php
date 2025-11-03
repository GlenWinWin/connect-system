<?php
namespace Controllers;

use Core\Controller;
use Models\User;

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        session_start();
        $this->userModel = new User();
    }

    public function login() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            $user = $this->userModel->findByEmail($email);
            
            if ($user && $this->userModel->validatePassword($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                $this->redirect('/dashboard.php');
            } else {
                $this->view('auth/login', ['error' => 'Invalid email or password!']);
            }
        } else {
            $this->view('auth/login');
        }
    }

    public function signup() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'fullname' => $_POST['fullname'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? '',
                'password' => $_POST['password'] ?? '',
                'confirm_password' => $_POST['confirm_password'] ?? ''
            ];

            if ($data['password'] !== $data['confirm_password']) {
                $this->view('auth/signup', ['error' => 'Passwords do not match!']);
                return;
            }

            if ($this->userModel->emailExists($data['email'])) {
                $this->view('auth/signup', ['error' => 'Email already exists!']);
                return;
            }

            if ($this->userModel->create($data)) {
                $this->view('auth/login', ['success' => 'Account created successfully! You can now login.']);
            } else {
                $this->view('auth/signup', ['error' => 'Error creating account. Please try again.']);
            }
        } else {
            $this->view('auth/signup');
        }
    }

    public function logout() {
        session_destroy();
        $this->redirect('/login.php');
    }
}
?>