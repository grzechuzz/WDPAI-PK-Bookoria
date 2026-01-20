<?php

require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/RoleRepository.php';
require_once __DIR__ . '/../services/AuthService.php';


class AuthController extends AppController
{
    private AuthService $authService;

    public function __construct()
    {
        $db = Database::connect();
        $userRepo = new UserRepository($db);
        $roleRepo = new RoleRepository($db);
        $this->authService = new AuthService($userRepo, $roleRepo);
    }

    public function login() 
    {
        if ($this->isPost()) {

            try {
                Csrf::verifyOrFail();
            } catch (RuntimeException $e) {
                $this->render('auth/login', ['error' => $e->getMessage()]);
                return;
            }

            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';

            try {
                $user = $this->authService->login($email, $password);
                
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role_id'] = $user['role_id'];

                $this->redirect('/dashboard');
                return;
                
            } catch (RuntimeException $e) {
                $this->render('auth/login', ['error' => $e->getMessage()]);
                return;
            }
        }

        $this->render('auth/login');
    }

    public function register(): void
    {
        if ($this->isPost()) {
            try {
                Csrf::verifyOrFail();
            } catch (RuntimeException $e) {
                $this->render('auth/register', ['error' => $e->getMessage()]);
                return;
            }    
        
            $email = $_POST['email'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmedPassword = $_POST['confirmedPassword'] ?? '';
            
            try {
                $this->authService->register($email, $password, $confirmedPassword);
                
                $_SESSION['flash_success'] = 'Konto zostało utworzone. Możesz się zalogować.';
                $this->redirect('/login');
                return;

            } catch (RuntimeException $e) {
                $this->render('auth/register', ['error' => $e->getMessage()]);
                return;
            }
        }

        $this->render('auth/register');
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        $this->redirect('/login');
    }
}