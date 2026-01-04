<?php

require_once __DIR__ . '/../repositories/BranchRepository.php';

final class BranchService
{
    private BranchRepository $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function listAllBranches(): array
    {
        return $this->branchRepository->findAll();
    }

    public function assertBranchExists(int $branchId)
    {
        if ($branchId < 1 || !$this->branchRepository->existsById($branchId)) {
            throw new RuntimeException('Wybrany oddział nie istnieje.');
        }
    }
}
