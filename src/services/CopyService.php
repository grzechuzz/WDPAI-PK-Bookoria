<?php

require_once __DIR__ . '/../repositories/CopyRepository.php';
require_once __DIR__ . '/../core/Database.php';

final class CopyService
{
    private CopyRepository $copyRepository;

    public function __construct(CopyRepository $copyRepository)
    {
        $this->copyRepository = $copyRepository;
    }

    public function createCopy(int $roleId, int $bookId, int $branchId, string $inventoryCode): int
    {
        if ($roleId !== 2) {
            throw new RuntimeException('Brak uprawnień.');
        }

        if ($bookId < 1 || $branchId < 1) {
            throw new RuntimeException('Nieprawidłowe dane.');
        }

        $inventoryCode = trim($inventoryCode);
        if ($inventoryCode === '') {
            throw new RuntimeException('Kod inwentarzowy jest wymagany.');
        }
        if (mb_strlen($inventoryCode) > 64) {
            throw new RuntimeException('Kod inwentarzowy jest za długi.');
        }

        if (!$this->copyRepository->bookExists($bookId)) {
            throw new RuntimeException('Nie znaleziono książki.');
        }
        if (!$this->copyRepository->branchExists($branchId)) {
            throw new RuntimeException('Nie znaleziono oddziału.');
        }

        // pre-check (ładny komunikat)
        if ($this->copyRepository->existsByInventoryCode($inventoryCode)) {
            throw new RuntimeException('Taki kod inwentarzowy już istnieje.');
        }

        // finalna ochrona: jeśli masz UNIQUE w DB, to i tak przechwycisz wyjątek
        return $this->copyRepository->insertCopy($bookId, $branchId, $inventoryCode);
    }
}
