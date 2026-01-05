<?php

require_once __DIR__ . '/Repository.php';


final class BranchStaffRepository extends Repository
{
    public function isUserAssignedToBranch(int $userId, int $branchId): bool
    {
        $stmt = $this->db->prepare("
            SELECT 1 FROM branch_staff 
            WHERE user_id = :user_id AND branch_id = :branch_id 
            LIMIT 1
        ");
        $stmt->execute([
            'user_id' => $userId,
            'branch_id' => $branchId,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function getBranchIdsForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT branch_id FROM branch_staff 
            WHERE user_id = :user_id 
            ORDER BY branch_id ASC
        ");
        $stmt->execute(['user_id' => $userId]);

        return array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    }

    public function getBranchesForUser(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT br.id, br.name, br.city, br.address_line1, br.address_line2, br.postal_code, br.region, br.timezone, br.country_code, br.currency_code,
            (br.city || ', ' || br.name) AS label
            FROM branch_staff bs
            JOIN branches br ON br.id = bs.branch_id
            WHERE bs.user_id = :user_id
            ORDER BY br.city ASC, br.name ASC
        ");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function assignUserToBranch(int $userId, int $branchId): bool
    {
        $stmt = $this->db->prepare("
            INSERT INTO branch_staff (user_id, branch_id)
            VALUES (:user_id, :branch_id)
            ON CONFLICT (user_id, branch_id) DO NOTHING
        ");
        $stmt->execute([
            'user_id' => $userId,
            'branch_id' => $branchId,
        ]);

        return $stmt->rowCount() > 0;
    }

    public function removeUserFromBranch(int $userId, int $branchId): bool
    {
        $stmt = $this->db->prepare("DELETE FROM branch_staff WHERE user_id = :user_id AND branch_id = :branch_id");
        $stmt->execute([
            'user_id' => $userId,
            'branch_id' => $branchId,
        ]);

        return $stmt->rowCount() > 0;
    }

 
    public function countStaffInBranch(int $branchId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM branch_staff WHERE branch_id = :branch_id");
        $stmt->execute(['branch_id' => $branchId]);

        return (int)$stmt->fetchColumn();
    }
}