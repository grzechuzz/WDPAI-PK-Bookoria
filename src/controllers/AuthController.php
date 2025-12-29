<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../services/AuthService.php';


class AuthController extends AppController {
    private AuthService $authService;

    public function __construct() {
        $db = Database::connect();
        $userRepo = new UserRepository($db);
        $roleRepo = new RoleRepository($db);
        $this->authService = new AuthService($userRepo, $roleRepo);
    }

    public function login() {
        if ($this->isPost()) {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $user = $this->authService->login($email, $password);
                
                if (session_status() === PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];

                $this->redirect('/dashboard');
                return;
                
            } catch (RuntimeException $e) {
                return $this->render('auth/login', ['error' => $e->getMessage()]);
            }
        }

        return $this->render('auth/login');
    }

    public function register() {
        if ($this->isPost()) {
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $this->authService->register($email, $password);
                $this->redirect('/login');
                return;

            } catch (RuntimeException $e) {
                return $this->render('auth/register', ['error' => $e->getMessage()]);
            }
        }

        return $this->render('auth/register');
    }

    public function logout() {
        session_start();
        session_destroy();
        $this->redirect('/login');
    }
}