<?php

require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';


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

    public function getProfileData(int $userId): array
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
}
