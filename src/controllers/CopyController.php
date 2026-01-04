<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../repositories/BranchRepository.php';
require_once __DIR__ . '/../services/CopyService.php';
require_once __DIR__ . '/../services/BranchService.php';


final class CopyController extends AppController
{
    private CopyService $copyService;
    private BranchService $branchService;

    public function __construct()
    {
        $db = Database::connect();
        $repo = new CopyRepository($db);
        $branchRepo = new BranchRepository($db);
        $this->copyService = new CopyService($repo);
        $this->branchService = new BranchService($branchRepo);
    }

    public function add()
    {
        Auth::requireLogin();

        $roleId = (int)($_SESSION['role_id'] ?? 0);
        if ($roleId !== 2) {
            http_response_code(403);
            echo '403 Forbidden';
            return;
        }

        $branches = $this->branchService->listAllBranches();

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
                $this->branchService->assertBranchExists($branchId);
                $copyId = $this->copyService->createCopy($roleId, $bookId, $branchId, $inventoryCode);

                $_SESSION['flash_success'] = 'Dodano egzemplarz (ID: ' . $copyId . ').';
                $this->redirect('/book?id=' . $bookId);
                return;

            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        $this->render('copies/add', [
            'form' => $form,
            'error' => $error,
            'branches' => $branches,
        ]);
    }
}
