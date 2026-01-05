<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/BranchRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';
require_once __DIR__ . '/../services/UserService.php';


final class UserController extends AppController
{
    private UserService $userService;

    public function __construct()
    {
        $db = Database::connect();
        $userRepo = new UserRepository($db);
        $branchRepo = new BranchRepository($db);
        $branchStaffRepo = new BranchStaffRepository($db);
        
        $this->userService = new UserService($userRepo, $branchRepo, $branchStaffRepo);
    }

    public function index()
    {
        Auth::requireAdmin();

        $users = $this->userService->getUsersWithDetails();
        $branches = $this->userService->getAllBranches();

        $this->render('users/index', [
            'users' => $users,
            'branches' => $branches,
            'roles' => [
                Config::ROLE_ADMIN => 'Administrator',
                Config::ROLE_LIBRARIAN => 'Bibliotekarz',
                Config::ROLE_READER => 'Czytelnik',
            ],
        ]);
    }

    public function changeRole()
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/users');
            return;
        }

        try {
            Csrf::verifyOrFail();
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/users');
            return;
        }

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $roleId = filter_input(INPUT_POST, 'role_id', FILTER_VALIDATE_INT);

        if (!$userId || !$roleId) {
            $_SESSION['flash_error'] = 'Nieprawidłowe dane.';
            $this->redirect('/users');
            return;
        }

        try {
            $adminId = Auth::userId();
            $this->userService->changeUserRole($adminId, $userId, $roleId);
            
            $_SESSION['flash_success'] = 'Rola została zmieniona.';
            unset($_SESSION['flash_error']);
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/users');
    }

    public function assignBranch()
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/users');
            return;
        }

        try {
            Csrf::verifyOrFail();
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/users');
            return;
        }

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $branchId = filter_input(INPUT_POST, 'branch_id', FILTER_VALIDATE_INT);

        if (!$userId || !$branchId) {
            $_SESSION['flash_error'] = 'Nieprawidłowe dane.';
            $this->redirect('/users');
            return;
        }

        try {
            $this->userService->assignLibrarianToBranch($userId, $branchId);
            
            $_SESSION['flash_success'] = 'Bibliotekarz został przypisany do oddziału.';
            unset($_SESSION['flash_error']);
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/users');
    }

    public function removeBranch()
    {
        Auth::requireAdmin();

        if (!$this->isPost()) {
            $this->redirect('/users');
            return;
        }

        try {
            Csrf::verifyOrFail();
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            $this->redirect('/users');
            return;
        }

        $userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
        $branchId = filter_input(INPUT_POST, 'branch_id', FILTER_VALIDATE_INT);

        if (!$userId || !$branchId) {
            $_SESSION['flash_error'] = 'Nieprawidłowe dane.';
            $this->redirect('/users');
            return;
        }

        try {
            $this->userService->removeLibrarianFromBranch($userId, $branchId);
            
            $_SESSION['flash_success'] = 'Bibliotekarz został usunięty z oddziału.';
            unset($_SESSION['flash_error']);
        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/users');
    }
}
