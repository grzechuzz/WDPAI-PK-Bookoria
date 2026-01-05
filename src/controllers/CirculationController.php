<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';
require_once __DIR__ . '/../services/CirculationService.php';


final class CirculationController extends AppController
{
    private CirculationService $circulationService;

    public function __construct()
    {
        $db = Database::connect();
        $loanRepo = new LoanRepository($db);
        $reservationRepo = new ReservationRepository($db);
        $copyRepo = new CopyRepository($db);
        $branchStaffRepo = new BranchStaffRepository($db);
        $this->circulationService = new CirculationService($loanRepo, $reservationRepo, $copyRepo, $branchStaffRepo);
    }

    public function index()
    {
        Auth::requireLibrarian();

        $userId = Auth::userId();
        $data = $this->circulationService->getCirculationData($userId);

        $this->render('circulation/index', $data);
    }

    public function issue()
    {
        Auth::requireLibrarian();

        if (!$this->isPost()) {
            $this->redirect('/circulation');
            return;
        }

        $userId = Auth::userId();
        $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);

        if (!$reservationId || $reservationId < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/circulation');
            return;
        }

        try {
            $loanId = $this->circulationService->issueBook($userId, $reservationId);

            $_SESSION['flash_success'] = 'Książka została wydana. Wypożyczenie #' . $loanId;
            unset($_SESSION['flash_error']);

        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/circulation');
    }

    public function returnBook()
    {
        Auth::requireLibrarian();

        if (!$this->isPost()) {
            $this->redirect('/circulation');
            return;
        }

        $userId = Auth::userId();
        $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);

        if (!$loanId || $loanId < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/circulation');
            return;
        }

        try {
            $this->circulationService->returnBook($userId, $loanId);

            $_SESSION['flash_success'] = 'Zwrot został zarejestrowany.';
            unset($_SESSION['flash_error']);

        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/circulation');
    }
}