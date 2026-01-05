<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';


final class CirculationService
{
    private LoanRepository $loanRepo;
    private ReservationRepository $reservationRepo;
    private CopyRepository $copyRepo;
    private BranchStaffRepository $branchStaffRepo;
    private PDO $db;

    public function __construct(LoanRepository $loanRepo, ReservationRepository $reservationRepo, CopyRepository $copyRepo, BranchStaffRepository $branchStaffRepo) {
        $this->loanRepo = $loanRepo;
        $this->reservationRepo = $reservationRepo;
        $this->copyRepo = $copyRepo;
        $this->branchStaffRepo = $branchStaffRepo;
        $this->db = Database::connect();
    }

    public function getCirculationData(int $librarianId): array
    {
        $branchIds = $this->branchStaffRepo->getBranchIdsForUser($librarianId);
        
        if (empty($branchIds)) {
            return [
                'branches' => [],
                'readyReservations' => [],
                'activeLoans' => [],
                'hasBranches' => false,
            ];
        }

        $branches = $this->branchStaffRepo->getBranchesForUser($librarianId);
        $readyReservations = $this->reservationRepo->findReadyByBranches($branchIds);
        $activeLoans = $this->loanRepo->findActiveByBranches($branchIds);

        return [
            'branches' => $branches,
            'readyReservations' => $readyReservations,
            'activeLoans' => $activeLoans,
            'hasBranches' => true,
        ];
    }

    public function assertLibrarianHasBranchAccess(int $librarianId, int $branchId): void
    {
        if (!$this->branchStaffRepo->isUserAssignedToBranch($librarianId, $branchId)) {
            throw new RuntimeException('Nie masz uprawnień do tego oddziału.', DomainError::BRANCH_ACCESS_DENIED);
        }
    }

    public function issueBook(int $librarianId, int $reservationId): int
    {
        $reservation = $this->reservationRepo->findById($reservationId);
        
        if (!$reservation) {
            throw new RuntimeException('Rezerwacja nie istnieje.', DomainError::RESERVATION_NOT_FOUND);
        }

        if ($reservation['status'] !== Config::RES_READY) {
            throw new RuntimeException('Rezerwacja nie jest gotowa do odbioru.', DomainError::RESERVATION_NOT_READY);
        }

        $branchId = (int)$reservation['branch_id'];
        $this->assertLibrarianHasBranchAccess($librarianId, $branchId);

        $copyId = (int)$reservation['assigned_copy_id'];
        $userId = (int)$reservation['user_id'];

        if (!$copyId) {
            throw new RuntimeException('Rezerwacja nie ma przypisanego egzemplarza.', DomainError::COPY_NOT_FOUND);
        }

        $activeLoansCount = $this->loanRepo->countActiveByUser($userId);
        if ($activeLoansCount >= Config::MAX_ACTIVE_LOANS) {
            throw new RuntimeException('Użytkownik osiągnął limit wypożyczeń.', DomainError::LOAN_LIMIT_REACHED);
        }

        $this->db->beginTransaction();

        try {
            $ok = $this->reservationRepo->fulfillReservation($reservationId);
            if (!$ok) {
                throw new RuntimeException('Nie udało się zrealizować rezerwacji.', DomainError::RESERVATION_ALREADY_FULFILLED);
            }

            $ok = $this->copyRepo->updateStatusIf($copyId, Config::COPY_HELD, Config::COPY_LOANED);
            if (!$ok) {
                throw new RuntimeException('Egzemplarz nie jest dostępny do wydania.', DomainError::COPY_NOT_AVAILABLE);
            }

            $loanId = $this->loanRepo->createLoan($userId, $copyId, Config::LOAN_DAYS);
            if (!$loanId) {
                throw new RuntimeException('Nie udało się utworzyć wypożyczenia.', DomainError::LOAN_CREATE_FAILED);
            }

            $this->db->commit();
            return $loanId;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function returnBook(int $librarianId, int $loanId): void
    {
        $loan = $this->loanRepo->findById($loanId);

        if (!$loan) {
            throw new RuntimeException('Wypożyczenie nie istnieje.', DomainError::LOAN_NOT_FOUND);
        }

        if ($loan['returned_at'] !== null) {
            throw new RuntimeException('Książka została już zwrócona.', DomainError::LOAN_ALREADY_RETURNED);
        }

        $branchId = (int)$loan['branch_id'];
        $this->assertLibrarianHasBranchAccess($librarianId, $branchId);

        $copyId = (int)$loan['copy_id'];

        $this->db->beginTransaction();

        try {
            $ok = $this->loanRepo->returnLoan($loanId);
            if (!$ok) {
                throw new RuntimeException('Nie udało się zarejestrować zwrotu.', DomainError::LOAN_NOT_FOUND);
            }

            $ok = $this->copyRepo->updateStatusIf($copyId, Config::COPY_LOANED, Config::COPY_AVAILABLE);
            if (!$ok) {
                $this->copyRepo->updateStatus($copyId, Config::COPY_AVAILABLE);
            }

            $this->db->commit();

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function getLoanDetails(int $librarianId, int $loanId)
    {
        $loan = $this->loanRepo->findById($loanId);

        if (!$loan) {
            throw new RuntimeException('Wypożyczenie nie istnieje.', DomainError::LOAN_NOT_FOUND);
        }

        $branchId = (int)$loan['branch_id'];
        $this->assertLibrarianHasBranchAccess($librarianId, $branchId);

        return $loan;
    }

    
    public function getReservationDetails(int $librarianId, int $reservationId)
    {
        $reservation = $this->reservationRepo->findById($reservationId);

        if (!$reservation) {
            throw new RuntimeException('Rezerwacja nie istnieje.', DomainError::RESERVATION_NOT_FOUND);
        }

        $branchId = (int)$reservation['branch_id'];
        $this->assertLibrarianHasBranchAccess($librarianId, $branchId);

        return $reservation;
    }
}