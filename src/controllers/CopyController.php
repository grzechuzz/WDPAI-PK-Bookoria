<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../repositories/BranchRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';
require_once __DIR__ . '/../services/CopyService.php';
require_once __DIR__ . '/../services/BranchService.php';


final class CopyController extends AppController
{
    private CopyService $copyService;
    private BranchService $branchService;

    public function __construct()
    {
        $db = Database::connect();
        $copyRepo = new CopyRepository($db);
        $branchRepo = new BranchRepository($db);
        $branchStaffRepo = new BranchStaffRepository($db);
        
        $this->copyService = new CopyService($copyRepo, $branchStaffRepo);
        $this->branchService = new BranchService($branchRepo);
    }

    public function add()
    {
        Auth::requireLibrarian();

        $userId = Auth::userId();
        $roleId = Auth::roleId();

        $branches = $this->copyService->getBranchesForLibrarian($userId);

        $form = [
            'book_id' => (string)($_GET['book_id'] ?? ''),
            'branch_id' => '',
            'inventory_code' => '',
        ];
        $error = null;

        if ($this->isPost()) {
            $bookId = (int)($_POST['book_id'] ?? 0);
            $branchId = (int)($_POST['branch_id'] ?? 0);
            $inventoryCode = (string)($_POST['inventory_code'] ?? '');

            $form = [
                'book_id' => (string)$bookId,
                'branch_id' => (string)$branchId,
                'inventory_code' => $inventoryCode,
            ];

            try {
                $copyId = $this->copyService->createCopy($userId, $roleId, $bookId, $branchId, $inventoryCode);

                $_SESSION['flash_success'] = 'Egzemplarz został dodany (ID: ' . $copyId . ').';
                unset($_SESSION['flash_error']);
                $this->redirect('/book?id=' . $bookId);
                return;

            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        if (empty($branches)) {
            $error = $error ?? 'Nie jesteś przypisany do żadnego oddziału. Skontaktuj się z administratorem.';
        }

        $this->render('copies/add', [
            'form' => $form,
            'error' => $error,
            'branches' => $branches,
        ]);
    }
}