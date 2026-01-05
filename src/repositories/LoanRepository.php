<?php

require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/../core/Config.php';


final class LoanRepository extends Repository
{    
    public function findActiveByUser(int $userId)
    {
        $sql = "
            SELECT loan_id, user_id, copy_id, copy_code, book_id, title, branch_id, branch_label, loaned_at, due_at,
            returned_at, is_active, is_overdue, days_overdue, renewals_count
            FROM v_user_loans
            WHERE user_id = :user_id AND returned_at IS NULL
            ORDER BY is_overdue DESC, due_at ASC, loan_id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findHistoryByUser(int $userId, int $limit = null)
    {
        $limit = $limit ?? Config::HISTORY_LIMIT;
        if ($limit < 1) 
            $limit = 1;
        if ($limit > Config::HISTORY_MAX_LIMIT) 
            $limit = Config::HISTORY_MAX_LIMIT;

        $sql = "
            SELECT loan_id, user_id, copy_id, copy_code, book_id, title, branch_id, branch_label, 
                   loaned_at, due_at, returned_at, is_active, is_overdue, days_overdue, renewals_count 
            FROM v_user_loans
            WHERE user_id = :user_id AND returned_at IS NOT NULL
            ORDER BY returned_at DESC, loan_id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countActiveByUser(int $userId): int
    {
        $sql = "SELECT COUNT(*) FROM loans WHERE user_id = :user_id AND returned_at IS NULL";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return (int)$stmt->fetchColumn();
    }

    public function renewIfAllowed(int $loanId, int $userId, int $days = null)
    {
        $days = $days ?? Config::LOAN_RENEWAL_DAYS;

        $sql = "
            UPDATE loans
            SET due_at = due_at + make_interval(days => :days), renewals_count = renewals_count + 1
            WHERE id = :loan_id AND user_id = :user_id AND returned_at IS NULL AND renewals_count < :max_renewals AND now() <= due_at
            RETURNING id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->bindValue('loan_id', $loanId, PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('max_renewals', Config::MAX_RENEWALS, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function createLoan(int $userId, int $copyId, int $days = null)
    {
        $days = $days ?? Config::LOAN_DAYS;

        $stmt = $this->db->prepare("
            INSERT INTO loans (user_id, copy_id, loaned_at, due_at, returned_at, renewals_count)
            VALUES (:user_id, :copy_id, now(), now() + make_interval(days => :days), NULL, 0)
            RETURNING id
        ");

        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('copy_id', $copyId, PDO::PARAM_INT);
        $stmt->bindValue('days', $days, PDO::PARAM_INT);
        $stmt->execute();

        $id = $stmt->fetchColumn();
        return $id !== false ? (int)$id : null;
    }

    public function findActiveByCopyId(int $copyId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.user_id, l.copy_id, l.loaned_at, l.due_at, l.returned_at, l.renewals_count, u.email as user_email, b.title, c.inventory_code
            FROM loans l
            JOIN users u ON u.id = l.user_id
            JOIN copies c ON c.id = l.copy_id
            JOIN books b ON b.id = c.book_id
            WHERE l.copy_id = :copy_id AND l.returned_at IS NULL
            LIMIT 1
        ");
        $stmt->execute(['copy_id' => $copyId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $loanId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT l.id, l.user_id, l.copy_id, l.loaned_at, l.due_at, l.returned_at, l.renewals_count, u.email as user_email, b.title, b.id as book_id, c.inventory_code, c.branch_id,
            (br.city || ', ' || br.name) as branch_label
            FROM loans l
            JOIN users u ON u.id = l.user_id
            JOIN copies c ON c.id = l.copy_id
            JOIN books b ON b.id = c.book_id
            JOIN branches br ON br.id = c.branch_id
            WHERE l.id = :loan_id
            LIMIT 1
        ");
        $stmt->execute(['loan_id' => $loanId]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function returnLoan(int $loanId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE loans
            SET returned_at = now()
            WHERE id = :loan_id AND returned_at IS NULL
            RETURNING id
        ");
        $stmt->execute(['loan_id' => $loanId]);

        return $stmt->fetchColumn() !== false;
    }

    public function findActiveByBranch(int $branchId): array
    {
        $sql = "
            SELECT l.id as loan_id, l.user_id, l.copy_id, l.loaned_at, l.due_at, l.returned_at, l.renewals_count, u.email as user_email, c.inventory_code as copy_code, c.branch_id,
            b.id as book_id, b.title, (br.city || ', ' || br.name) as branch_label, (l.returned_at IS NULL AND now() > l.due_at) as is_overdue,
            CASE 
                WHEN l.returned_at IS NULL AND now() > l.due_at 
                THEN GREATEST(0, (now()::date - l.due_at::date))
                ELSE 0 
            END as days_overdue
            FROM loans l
            JOIN users u ON u.id = l.user_id
            JOIN copies c ON c.id = l.copy_id
            JOIN books b ON b.id = c.book_id
            JOIN branches br ON br.id = c.branch_id
            WHERE c.branch_id = :branch_id AND l.returned_at IS NULL
            ORDER BY l.due_at ASC, l.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['branch_id' => $branchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findActiveByBranches(array $branchIds): array
    {
        if (empty($branchIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($branchIds), '?'));

        $sql = "
            SELECT l.id as loan_id, l.user_id, l.copy_id, l.loaned_at, l.due_at, l.returned_at, l.renewals_count, u.email as user_email, c.inventory_code as copy_code, c.branch_id,
            b.id as book_id, b.title, (br.city || ', ' || br.name) as branch_label, (l.returned_at IS NULL AND now() > l.due_at) as is_overdue,
            CASE 
                WHEN l.returned_at IS NULL AND now() > l.due_at 
                THEN GREATEST(0, (now()::date - l.due_at::date))
                ELSE 0 
            END as days_overdue
            FROM loans l
            JOIN users u ON u.id = l.user_id
            JOIN copies c ON c.id = l.copy_id
            JOIN books b ON b.id = c.book_id
            JOIN branches br ON br.id = c.branch_id
            WHERE c.branch_id IN ($placeholders) AND l.returned_at IS NULL
            ORDER BY br.city ASC, br.name ASC, l.due_at ASC, l.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($branchIds);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
