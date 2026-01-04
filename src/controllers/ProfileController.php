<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/DomainError.php';
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
        Auth::requireLogin();

        $userId = Auth::userId();
        if ($userId === null) {
            $this->redirect('/login');
            return;
        }

        $data = $this->profileService->getProfileData($userId);
        $this->render('/profile', $data);
    }

    public function renewLoan()
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            http_response_code(405);
            $this->redirect('/profile');
        }

        $userId = Auth::userId();
        if (!$userId) {
            $this->redirect('/login');
        }

        $loanId = filter_input(INPUT_POST, 'loan_id', FILTER_VALIDATE_INT);
        if (!$loanId || $loanId < 1) {
            http_response_code(400);
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/profile');
        }

        try {
            $this->profileService->renewLoan($userId, (int)$loanId);

            $_SESSION['flash_success'] = 'Wypożyczenie przedłużone o 14 dni.';
            $this->redirect('/profile');

        } catch (RuntimeException $e) {
            if ($e->getCode() === DomainError::LOAN_RENEW_NOT_ALLOWED) {
                http_response_code(409);
                $_SESSION['flash_error'] = 'Nie można przedłużyć wypożyczenia (po terminie lub już przedłużone).';
                $this->redirect('/profile');
            }

            http_response_code(500);
            throw $e;
        }
    }

    public function cancelReservation()
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            http_response_code(405);
            $this->redirect('/profile');
        }

        $userId = Auth::userId();
        if (!$userId) {
            $this->redirect('/login');
        }

        $reservationId = filter_input(INPUT_POST, 'reservation_id', FILTER_VALIDATE_INT);
        if (!$reservationId || $reservationId < 1) {
            http_response_code(400);
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            $this->redirect('/profile');
        }

        try {
            $this->profileService->cancelReservation($userId, (int)$reservationId);

            $_SESSION['flash_success'] = 'Rezerwacja została anulowana.';
            $this->redirect('/profile');

        } catch (RuntimeException $e) {
            if ($e->getCode() === DomainError::RESERVATION_CANCEL_NOT_ALLOWED) {
                http_response_code(409);
                $_SESSION['flash_error'] = 'Nie można anulować tej rezerwacji.';
                $this->redirect('/profile');
            }

            http_response_code(500);
            throw $e;
        }
    }

    public function createReservation()
    {
        Auth::requireLogin();

        if (!$this->isPost()) {
            http_response_code(405);
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
            http_response_code(400);
            $_SESSION['flash_error'] = 'Nieprawidłowe żądanie.';
            unset($_SESSION['flash_success']);
            $this->redirect('/repository');
            return;
        }

        try {
            $this->profileService->createReservation($userId, (int)$bookId, (int)$branchId);

            $_SESSION['flash_success'] = 'Rezerwacja została utworzona.';
            unset($_SESSION['flash_error']);
            $this->redirect('/profile');
            return;

        } catch (RuntimeException $e) {
            if ($e->getCode() === DomainError::RESERVATION_CREATE_NOT_ALLOWED) {
                http_response_code(409);
                $_SESSION['flash_error'] = 'Nie można utworzyć rezerwacji.';
                unset($_SESSION['flash_success']);
                $this->redirect('/book?id=' . (int)$bookId);
                return;
            }
            throw $e;
        }
    }
}
