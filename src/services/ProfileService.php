<?php

require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../core/DomainError.php';


final class ProfileService
{
    private const MAX_ACTIVE_LOANS = 5;

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
        $historyLoans = $this->loanRepository->findHistoryByUser($userId, 50);
        $historyReservations = $this->reservationRepository->findHistoryByUser($userId, 50);

        return [
            'activeLoans' => $activeLoans,
            'historyLoans' => $historyLoans,
            'activeReservations' => $activeReservations,
            'historyReservations' => $historyReservations,
            'activeLoansCount' => $activeLoansCount,
            'maxLoans' => self::MAX_ACTIVE_LOANS,
            'limitReached' => $activeLoansCount >= self::MAX_ACTIVE_LOANS,
        ];
    }

    public function renewLoan(int $userId, int $loanId)
    {
        $ok = $this->loanRepository->renewIfAllowed($loanId, $userId, 14);

        if (!$ok) {
            throw new RuntimeException(
                'Cannot extend loan.',
                DomainError::LOAN_RENEW_NOT_ALLOWED
            );
        }
    }

    public function cancelReservation(int $userId, int $reservationId)
    {
        $ok = $this->reservationRepository->cancelActive($reservationId, $userId);

        if (!$ok) {
            throw new RuntimeException(
                'Cannot cancel reservation',
                DomainError::RESERVATION_CANCEL_NOT_ALLOWED
            );
        }
    }

    public function createReservation(int $userId, int $bookId, int $branchId): void
    {
        $ok = $this->reservationRepository->createQueued($userId, $bookId, $branchId);

        if (!$ok) {
            throw new RuntimeException(
                'Cannot create reservation.',
                DomainError::RESERVATION_CREATE_NOT_ALLOWED
            );
        }
    }
}
