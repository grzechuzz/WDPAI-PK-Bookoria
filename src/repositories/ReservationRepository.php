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
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT id, assigned_copy_id FROM reservations
                WHERE id = :res_id AND user_id = :user_id AND status IN ('QUEUED', 'READY_FOR_PICKUP')
                FOR UPDATE
            ");

            $stmt->execute([
                'res_id' => $reservationId,
                'user_id' => $userId,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $copyId = isset($row['assigned_copy_id']) ? (int)$row['assigned_copy_id'] : null;

            $stmt = $this->db->prepare("
                UPDATE reservations
                SET status = 'CANCELLED', ready_until = NULL, assigned_copy_id = NULL
                WHERE id = :res_id
                RETURNING id
            ");
            $stmt->execute(['res_id' => $reservationId]);

            if ($stmt->fetchColumn() === false) {
                $this->db->rollBack();
                return false;
            }

            if ($copyId) {
                $stmt = $this->db->prepare("
                    UPDATE copies
                    SET status = 'AVAILABLE'
                    WHERE id = :copy_id AND status = 'HELD'
                ");
                $stmt->execute(['copy_id' => $copyId]);
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function createReadyReservationAndHoldCopy(int $userId, int $bookId, int $branchId)
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM reservations r
                WHERE r.user_id = :user_id AND r.book_id = :book_id AND r.branch_id = :branch_id AND r.status IN ('QUEUED', 'READY_FOR_PICKUP')
                LIMIT 1
            ");

            $stmt->execute([
                'user_id' => $userId,
                'book_id' => $bookId,
                'branch_id' => $branchId,
            ]);

            if ($stmt->fetchColumn() !== false) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT c.id FROM copies c
                WHERE c.book_id = :book_id AND c.branch_id = :branch_id AND c.status = 'AVAILABLE'
                ORDER BY c.id ASC
                FOR UPDATE SKIP LOCKED
                LIMIT 1
            ");

            $stmt->execute([
                'book_id' => $bookId,
                'branch_id' => $branchId,
            ]);

            $copyId = $stmt->fetchColumn();
            if ($copyId === false) {
                $this->db->rollBack();
                return false; 
            }
            $copyId = (int)$copyId;

       
            $stmt = $this->db->prepare("
                UPDATE copies
                SET status = 'HELD'
                WHERE id = :copy_id AND status = 'AVAILABLE'
                RETURNING id
            ");

            $stmt->execute(['copy_id' => $copyId]);
            if ($stmt->fetchColumn() === false) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                INSERT INTO reservations (user_id, book_id, branch_id, status, ready_until, assigned_copy_id, created_at)
                VALUES (:user_id, :book_id, :branch_id, 'READY_FOR_PICKUP', now() + interval '48 hours', :copy_id, now())
                RETURNING id
            ");

            $stmt->execute([
                'user_id' => $userId,
                'book_id' => $bookId,
                'branch_id' => $branchId,
                'copy_id' => $copyId,
            ]);

            $ok = $stmt->fetchColumn() !== false;
            if (!$ok) {
                $this->db->rollBack();
                return false;
            }

            $this->db->commit();
            return true;

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
