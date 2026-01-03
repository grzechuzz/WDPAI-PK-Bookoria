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
}
