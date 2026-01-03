<?php

require_once __DIR__ . '/Repository.php';


final class ReservationRepository extends Repository
{
    public function findActiveByUser(int $userId)
    {
        $sql = "
            SELECT r.id, r.user_id, r.book_id, b.title, r.branch_id, (br.city || ', ' || br.name) AS branch_label, r.status, r.ready_until, r.assigned_copy_id, r.created_at
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN branches br ON br.id = r.branch_id
            WHERE r.user_id = :user_id AND r.status = ANY (ARRAY['QUEUED'::text, 'READY_FOR_PICKUP'::text])
            ORDER BY CASE WHEN r.status = 'READY_FOR_PICKUP' THEN 0 ELSE 1 END,
            r.created_at ASC, r.id ASC
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
            SELECT r.id, r.user_id, r.book_id, b.title, r.branch_id, (br.city || ', ' || br.name) AS branch_label, r.status, r.ready_until, r.assigned_copy_id, r.created_at
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN branches br ON br.id = r.branch_id
            WHERE r.user_id = :user_id AND r.status = ANY (ARRAY['CANCELLED'::text, 'EXPIRED'::text, 'FULFILLED'::text])
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function cancelActive(int $reservationId, int $userId)
    {
        $sql = "
            UPDATE reservations
            SET status = 'CANCELLED', ready_until = NULL, assigned_copy_id = NULL
            WHERE id = :res_id AND user_id = :user_id AND status IN ('QUEUED', 'READY_FOR_PICKUP')
            RETURNING id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('res_id', $reservationId, PDO::PARAM_INT);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }

    public function createQueued(int $userId, int $bookId, int $branchId)
    {
        $sql = "
            INSERT INTO reservations (user_id, book_id, branch_id, status, ready_until, assigned_copy_id, created_at)
            SELECT :user_id, :book_id, :branch_id, 'QUEUED', NULL, NULL, now()
            WHERE EXISTS (SELECT 1 FROM books WHERE id = :book_id)
            AND EXISTS (SELECT 1 FROM branches WHERE id = :branch_id)
            AND NOT EXISTS (SELECT 1 FROM reservations r WHERE r.user_id = :user_id AND r.book_id = :book_id AND r.status IN ('QUEUED', 'READY_FOR_PICKUP'))
            RETURNING id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('book_id', $bookId, PDO::PARAM_INT);
        $stmt->bindValue('branch_id', $branchId, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchColumn() !== false;
    }
}
