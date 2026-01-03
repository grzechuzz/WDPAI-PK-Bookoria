<?php


require_once __DIR__ . '/Repository.php';

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

    public function findHistoryByUser(int $userId, int $limit = 50)
    {
        if ($limit < 1) {
            $limit = 1;
        }
        if ($limit > 200) {
            $limit = 200;
        }

        $sql = "
            SELECT loan_id, user_id, copy_id, copy_code, book_id, title, branch_id, branch_label, loaned_at, due_at,
            returned_at, is_active, is_overdue, days_overdue, renewals_count FROM v_user_loans
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
        $sql = "
            SELECT COUNT(*) FROM loans WHERE user_id = :user_id AND returned_at IS NULL
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['user_id' => $userId]);

        return (int)$stmt->fetchColumn();
    }
}
