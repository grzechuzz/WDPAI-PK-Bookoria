<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';


final class CopyService
{
    private CopyRepository $copyRepository;
    private BranchStaffRepository $branchStaffRepository;

    public function __construct(CopyRepository $copyRepository, BranchStaffRepository $branchStaffRepository)
    {
        $this->copyRepository = $copyRepository;
        $this->branchStaffRepository = $branchStaffRepository;
    }

    public function createCopy(int $userId, int $roleId, int $bookId, int $branchId, string $inventoryCode)
    {
        if (!Config::isLibrarian($roleId)) {
            throw new RuntimeException('Brak uprawnień do dodawania egzemplarzy.', DomainError::ACCESS_DENIED);
        }

        if ($bookId < 1 || $branchId < 1) {
            throw new RuntimeException('Nieprawidłowe dane.', DomainError::INVALID_REQUEST);
        }

        $inventoryCode = trim($inventoryCode);
        if ($inventoryCode === '') {
            throw new RuntimeException('Kod inwentarzowy jest wymagany.');
        }
        if (mb_strlen($inventoryCode) > Config::MAX_INVENTORY_CODE_LENGTH) {
            throw new RuntimeException('Kod inwentarzowy jest za długi (max ' . Config::MAX_INVENTORY_CODE_LENGTH . ' znaków).');
        }

        if (!$this->branchStaffRepository->isUserAssignedToBranch($userId, $branchId)) {
            throw new RuntimeException('Nie masz uprawnień do dodawania egzemplarzy w tym oddziale.', DomainError::BRANCH_ACCESS_DENIED);
        }

        if (!$this->copyRepository->bookExists($bookId)) {
            throw new RuntimeException('Nie znaleziono książki.', DomainError::BOOK_NOT_FOUND);
        }

        if (!$this->copyRepository->branchExists($branchId)) {
            throw new RuntimeException('Nie znaleziono oddziału.', DomainError::BRANCH_NOT_FOUND);
        }

        if ($this->copyRepository->existsByInventoryCode($inventoryCode)) {
            throw new RuntimeException('Egzemplarz z takim kodem inwentarzowym już istnieje.', DomainError::INVENTORY_CODE_EXISTS);
        }

        return $this->copyRepository->insertCopy($bookId, $branchId, $inventoryCode);
    }

    public function getBranchesForLibrarian(int $userId): array
    {
        return $this->branchStaffRepository->getBranchesForUser($userId);
    }

    public function hasAccessToBranch(int $userId, int $branchId): bool
    {
        return $this->branchStaffRepository->isUserAssignedToBranch($userId, $branchId);
    }
}