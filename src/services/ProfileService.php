<?php

require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Config.php';


final class ProfileService
{
    private LoanRepository $loanRepository;
    private ReservationRepository $reservationRepository;

    public function __construct(LoanRepository $loanRepository, ReservationRepository $reservationRepository)
    {
        $this->loanRepository = $loanRepository;
        $this->reservationRepository = $reservationRepository;
    }

    public function getProfileData(int $userId)
    {
        $activeLoans = $this->loanRepository->findActiveByUser($userId);
        $activeLoansCount = $this->loanRepository->countActiveByUser($userId);
        $activeReservations = $this->reservationRepository->findActiveByUser($userId);
        $historyLoans = $this->loanRepository->findHistoryByUser($userId, Config::HISTORY_LIMIT);
        $historyReservations = $this->reservationRepository->findHistoryByUser($userId, Config::HISTORY_LIMIT);

        return [
            'activeLoans' => $activeLoans,
            'historyLoans' => $historyLoans,
            'activeReservations' => $activeReservations,
            'historyReservations' => $historyReservations,
            'activeLoansCount' => $activeLoansCount,
            'maxLoans' => Config::MAX_ACTIVE_LOANS,
            'limitReached' => $activeLoansCount >= Config::MAX_ACTIVE_LOANS,
        ];
    }

    public function renewLoan(int $userId, int $loanId)
    {
        $ok = $this->loanRepository->renewIfAllowed($loanId, $userId, Config::LOAN_RENEWAL_DAYS);

        if (!$ok) {
            throw new RuntimeException( 'Nie można przedłużyć wypożyczenia.', DomainError::LOAN_RENEW_NOT_ALLOWED);
        }
    }

    public function cancelReservation(int $userId, int $reservationId)
    {
        $ok = $this->reservationRepository->cancelActive($reservationId, $userId);

        if (!$ok) {
            throw new RuntimeException('Nie można anulować tej rezerwacji.', DomainError::RESERVATION_CANCEL_NOT_ALLOWED);
        }
    }

    public function createReservation(int $userId, int $bookId, int $branchId)
    {
        $ok = $this->reservationRepository->createReadyReservationAndHoldCopy($userId, $bookId, $branchId);

        if (!$ok) {
            throw new RuntimeException('Nie można utworzyć rezerwacji.', DomainError::RESERVATION_CREATE_NOT_ALLOWED);
        }
    }
}
