<?php
namespace Controllers;

use Core\Controller;

class AuthController extends Controller {
    private $userModel;

    public function __construct() {
        parent::__construct();
        session_start();
        
        // Manually require the User model
        require_once __DIR__ . '/../models/User.php';
        $this->userModel = new \Models\User();
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
                return;
            } else {
                $error = 'Invalid email or password!';
            }
        }
        
        $this->render('auth/login', ['error' => $error ?? null]);
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
                $error = 'Passwords do not match!';
            } elseif ($this->userModel->emailExists($data['email'])) {
                $error = 'Email already exists!';
            } elseif ($this->userModel->create($data)) {
                $success = 'Account created successfully! You can now login.';
                $this->render('auth/login', ['success' => $success]);
                return;
            } else {
                $error = 'Error creating account. Please try again.';
            }
        }
        
        $this->render('auth/signup', ['error' => $error ?? null]);
    }

    public function logout() {
        session_destroy();
        $this->redirect('/login.php');
    }
}
?>