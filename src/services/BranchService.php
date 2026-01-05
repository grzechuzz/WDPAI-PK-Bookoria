<?php

require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../repositories/BranchRepository.php';


final class BranchService
{
    private BranchRepository $branchRepository;

    public function __construct(BranchRepository $branchRepository)
    {
        $this->branchRepository = $branchRepository;
    }

    public function listAllBranches()
    {
        return $this->branchRepository->findAll();
    }

  
    public function assertBranchExists(int $branchId)
    {
        if ($branchId < 1 || !$this->branchRepository->existsById($branchId)) {
            throw new RuntimeException('Wybrany oddział nie istnieje.', DomainError::BRANCH_NOT_FOUND);
        }
    }

    public function getBranchById(int $branchId)
    {
        return $this->branchRepository->findById($branchId);
    }
}