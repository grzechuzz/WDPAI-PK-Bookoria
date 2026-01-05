<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../services/ProfileService.php';


final class ProfileController extends AppController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $db = Database::connect();
        $loanRepo = new LoanRepository($db);
        $reservationRepo = new ReservationRepository($db);
        $this->profileService = new ProfileService($loanRepo, $reservationRepo);
    }

    public function index()
    {
        Auth::requireReader();

        $userId = Auth::userId();
        if ($userId === null) {
            $this->redirect('/login');
            return;
        }

        $data = $this->profileService->getProfileData($userId);
        $this->render('profile', $data);
    }

    public function renewLoan()
    {
        Auth::requireReader();

        if (!$this->isPost()) {
            $this->redirect('/profile');
            return;
        }

        $userId = Auth::userId();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);
        if (!$loanId || $loanId < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/profile');
            return;
        }

        try {
            $this->profileService->renewLoan($userId, $loanId);

            $_SESSION['flash_success'] = 'Wypożyczenie przedłużone o ' . Config::LOAN_RENEWAL_DAYS . ' dni.';
            unset($_SESSION['flash_error']);

        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/profile');
    }

    public function cancelReservation()
    {
        Auth::requireReader();

        if (!$this->isPost()) {
            $this->redirect('/profile');
            return;
        }

        $userId = Auth::userId();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
        if (!$reservationId || $reservationId < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/profile');
            return;
        }

        try {
            $this->profileService->cancelReservation($userId, $reservationId);

            $_SESSION['flash_success'] = 'Rezerwacja została anulowana.';
            unset($_SESSION['flash_error']);

        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
        }

        $this->redirect('/profile');
    }

    public function createReservation()
    {
        Auth::requireReader();

        if (!$this->isPost()) {
            $this->redirect('/repository');
            return;
        }

        $userId = Auth::userId();
        if (!$userId) {
            $this->redirect('/login');
            return;
        }

        $bookId = filter_input(INPUT_POST, 'book_id', FILTER_VALIDATE_INT);
        $branchId = filter_input(INPUT_POST, 'branch_id', FILTER_VALIDATE_INT);

        if (!$bookId || $bookId < 1 || !$branchId || $branchId < 1) {
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            unset($_SESSION['flash_success']);
            $this->redirect('/repository');
            return;
        }

        try {
            $this->profileService->createReservation($userId, $bookId, $branchId);

            $_SESSION['flash_success'] = 'Rezerwacja została utworzona. Odbierz książkę w ciągu ' . Config::RESERVATION_HOLD_HOURS . ' godzin.';
            unset($_SESSION['flash_error']);
            $this->redirect('/profile');

        } catch (RuntimeException $e) {
            $_SESSION['flash_error'] = $e->getMessage();
            unset($_SESSION['flash_success']);
            $this->redirect('/book?id=' . $bookId);
        }
    }
}