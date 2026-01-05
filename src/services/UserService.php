<?php

require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/DomainError.php';
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/BranchRepository.php';
require_once __DIR__ . '/../repositories/BranchStaffRepository.php';


final class UserService
{
    private UserRepository $userRepo;
    private BranchRepository $branchRepo;
    private BranchStaffRepository $branchStaffRepo;

    public function __construct(UserRepository $userRepo, BranchRepository $branchRepo, BranchStaffRepository $branchStaffRepo) 
    {
        $this->userRepo = $userRepo;
        $this->branchRepo = $branchRepo;
        $this->branchStaffRepo = $branchStaffRepo;
    }

    public function getUsersWithDetails()
    {
        $users = $this->userRepo->findAllWithRoles();
        
        foreach ($users as &$user) {
            $user['branches'] = [];
            if ((int)$user['role_id'] === Config::ROLE_LIBRARIAN) {
                $user['branches'] = $this->branchStaffRepo->getBranchesForUser((int)$user['id']);
            }
        }
        
        return $users;
    }

    public function getUsersPaginated(int $page, ?string $emailSearch, ?int $roleFilter)
    {
        if ($page < 1) $page = 1;
        
        $offset = ($page - 1) * Config::USERS_PER_PAGE;
        $users = $this->userRepo->findPaginatedWithRoles(Config::USERS_PER_PAGE, $offset, $emailSearch, $roleFilter);
        $total = $this->userRepo->countFiltered($emailSearch, $roleFilter);
        $totalPages = (int)ceil($total / Config::USERS_PER_PAGE);

        foreach ($users as &$user) {
            $user['branches'] = [];
            if ((int)$user['role_id'] === Config::ROLE_LIBRARIAN) {
                $user['branches'] = $this->branchStaffRepo->getBranchesForUser((int)$user['id']);
            }
        }

        return [
            'users' => $users,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalUsers' => $total,
            'emailSearch' => $emailSearch,
            'roleFilter' => $roleFilter,
        ];
    }

    public function getAllBranches()
    {
        return $this->branchRepo->findAll();
    }

    public function changeUserRole(int $adminId, int $userId, int $newRoleId)
    {
        if ($adminId === $userId) {
            throw new RuntimeException('Nie możesz zmienić własnej roli.', DomainError::ACCESS_DENIED);
        }

        if (!in_array($newRoleId, [Config::ROLE_ADMIN, Config::ROLE_LIBRARIAN, Config::ROLE_READER], true)) {
            throw new RuntimeException('Nieprawidłowa rola.', DomainError::INVALID_REQUEST);
        }

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new RuntimeException('Użytkownik nie istnieje.', DomainError::USER_NOT_FOUND);
        }

        $oldRoleId = (int)$user['role_id'];

        if ($oldRoleId === Config::ROLE_LIBRARIAN && $newRoleId !== Config::ROLE_LIBRARIAN) {
            $branches = $this->branchStaffRepo->getBranchIdsForUser($userId);
            foreach ($branches as $branchId) {
                $this->branchStaffRepo->removeUserFromBranch($userId, $branchId);
            }
        }

        $this->userRepo->updateRole($userId, $newRoleId);
    }

    public function assignLibrarianToBranch(int $userId, int $branchId)
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw new RuntimeException('Użytkownik nie istnieje.', DomainError::USER_NOT_FOUND);
        }

        if ((int)$user['role_id'] !== Config::ROLE_LIBRARIAN) {
            throw new RuntimeException('Użytkownik nie jest bibliotekarzem.', DomainError::INVALID_REQUEST);
        }

        if (!$this->branchRepo->findById($branchId)) {
            throw new RuntimeException('Oddział nie istnieje.', DomainError::BRANCH_NOT_FOUND);
        }

        $this->branchStaffRepo->assignUserToBranch($userId, $branchId);
    }

    public function removeLibrarianFromBranch(int $userId, int $branchId): void
    {
        $this->branchStaffRepo->removeUserFromBranch($userId, $branchId);
    }
}
