<?php

require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/../core/Config.php';


final class ReservationRepository extends Repository
{
    public function findActiveByUser(int $userId)
    {
        $sql = "
            SELECT r.id, r.user_id, r.book_id, b.title, r.branch_id, (br.city || ', ' || br.name) AS branch_label, r.status, r.ready_until, r.assigned_copy_id, r.created_at
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN branches br ON br.id = r.branch_id
            WHERE r.user_id = :user_id AND r.status IN (:queued, :ready)
            ORDER BY CASE WHEN r.status = :ready THEN 0 ELSE 1 END,
            r.created_at ASC, r.id ASC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => $userId,
            'queued' => Config::RES_QUEUED,
            'ready' => Config::RES_READY,
        ]);

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
            SELECT r.id, r.user_id, r.book_id, b.title, r.branch_id, (br.city || ', ' || br.name) AS branch_label, r.status, r.ready_until, r.assigned_copy_id, r.created_at
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN branches br ON br.id = r.branch_id
            WHERE r.user_id = :user_id AND r.status IN (:cancelled, :expired, :fulfilled)
            ORDER BY r.created_at DESC, r.id DESC
            LIMIT :limit
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue('cancelled', Config::RES_CANCELLED, PDO::PARAM_STR);
        $stmt->bindValue('expired', Config::RES_EXPIRED, PDO::PARAM_STR);
        $stmt->bindValue('fulfilled', Config::RES_FULFILLED, PDO::PARAM_STR);
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
                WHERE id = :res_id AND user_id = :user_id AND status IN (:queued, :ready)
                FOR UPDATE
            ");
            $stmt->execute([
                'res_id' => $reservationId,
                'user_id' => $userId,
                'queued' => Config::RES_QUEUED,
                'ready' => Config::RES_READY,
            ]);

            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->db->rollBack();
                return false;
            }

            $copyId = isset($row['assigned_copy_id']) ? (int)$row['assigned_copy_id'] : null;

            $stmt = $this->db->prepare("
                UPDATE reservations
                SET status = :cancelled, ready_until = NULL, assigned_copy_id = NULL
                WHERE id = :res_id
                RETURNING id
            ");
            $stmt->execute([
                'res_id' => $reservationId,
                'cancelled' => Config::RES_CANCELLED,
            ]);

            if ($stmt->fetchColumn() === false) {
                $this->db->rollBack();
                return false;
            }

            if ($copyId) {
                $stmt = $this->db->prepare("
                    UPDATE copies SET status = :available
                    WHERE id = :copy_id AND status = :held
                ");
                $stmt->execute([
                    'copy_id' => $copyId,
                    'available' => Config::COPY_AVAILABLE,
                    'held' => Config::COPY_HELD,
                ]);
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

    public function createReadyReservationAndHoldCopy(int $userId, int $bookId, int $branchId): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare("
                SELECT 1 FROM reservations r
                WHERE r.user_id = :user_id AND r.book_id = :book_id AND r.branch_id = :branch_id AND r.status IN (:queued, :ready)
                LIMIT 1
            ");
            $stmt->execute([
                'user_id' => $userId,
                'book_id' => $bookId,
                'branch_id' => $branchId,
                'queued' => Config::RES_QUEUED,
                'ready' => Config::RES_READY,
            ]);

            if ($stmt->fetchColumn() !== false) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare("
                SELECT c.id FROM copies c
                WHERE c.book_id = :book_id AND c.branch_id = :branch_id AND c.status = :available
                ORDER BY c.id ASC
                FOR UPDATE SKIP LOCKED
                LIMIT 1
            ");
            $stmt->execute([
                'book_id' => $bookId,
                'branch_id' => $branchId,
                'available' => Config::COPY_AVAILABLE,
            ]);

            $copyId = $stmt->fetchColumn();
            if ($copyId === false) {
                $this->db->rollBack();
                return false;
            }
            $copyId = (int)$copyId;

            $stmt = $this->db->prepare("
                UPDATE copies SET status = :held
                WHERE id = :copy_id AND status = :available
                RETURNING id
            ");
            $stmt->execute([
                'copy_id' => $copyId,
                'held' => Config::COPY_HELD,
                'available' => Config::COPY_AVAILABLE,
            ]);

            if ($stmt->fetchColumn() === false) {
                $this->db->rollBack();
                return false;
            }

            $holdHours = Config::RESERVATION_HOLD_HOURS;
            $stmt = $this->db->prepare("
                INSERT INTO reservations (user_id, book_id, branch_id, status, ready_until, assigned_copy_id, created_at)
                VALUES (:user_id, :book_id, :branch_id, :ready, now() + interval '{$holdHours} hours', :copy_id, now())
                RETURNING id
            ");
            $stmt->execute([
                'user_id' => $userId,
                'book_id' => $bookId,
                'branch_id' => $branchId,
                'ready' => Config::RES_READY,
                'copy_id' => $copyId,
            ]);

            if ($stmt->fetchColumn() === false) {
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

    public function findById(int $reservationId): ?array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.user_id, r.book_id, r.branch_id, r.status, r.ready_until, r.assigned_copy_id, r.created_at, b.title, u.email as user_email, c.inventory_code as copy_code,
            (br.city || ', ' || br.name) as branch_label
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN users u ON u.id = r.user_id
            JOIN branches br ON br.id = r.branch_id
            LEFT JOIN copies c ON c.id = r.assigned_copy_id
            WHERE r.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $reservationId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findReadyByBranch(int $branchId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.id, r.user_id, r.book_id, r.branch_id, r.status, r.ready_until, r.assigned_copy_id, r.created_at, b.title, u.email as user_email, c.inventory_code as copy_code,
            (br.city || ', ' || br.name) as branch_label
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN users u ON u.id = r.user_id
            JOIN branches br ON br.id = r.branch_id
            LEFT JOIN copies c ON c.id = r.assigned_copy_id
            WHERE r.branch_id = :branch_id AND r.status = :ready
            ORDER BY r.ready_until ASC, r.id ASC
        ");
        $stmt->execute([
            'branch_id' => $branchId,
            'ready' => Config::RES_READY,
        ]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findReadyByBranches(array $branchIds): array
    {
        if (empty($branchIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($branchIds), '?'));

        $sql = "
            SELECT r.id, r.user_id, r.book_id, r.branch_id, r.status, r.ready_until, r.assigned_copy_id, r.created_at, b.title, u.email as user_email, c.inventory_code as copy_code,
            (br.city || ', ' || br.name) as branch_label
            FROM reservations r
            JOIN books b ON b.id = r.book_id
            JOIN users u ON u.id = r.user_id
            JOIN branches br ON br.id = r.branch_id
            LEFT JOIN copies c ON c.id = r.assigned_copy_id
            WHERE r.branch_id IN ($placeholders) AND r.status = ?
            ORDER BY br.city ASC, br.name ASC, r.ready_until ASC, r.id ASC
        ";

        $params = array_merge($branchIds, [Config::RES_READY]);
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function fulfillReservation(int $reservationId): bool
    {
        $stmt = $this->db->prepare("
            UPDATE reservations
            SET status = :fulfilled
            WHERE id = :res_id AND status = :ready
            RETURNING id
        ");
        $stmt->execute([
            'res_id' => $reservationId,
            'fulfilled' => Config::RES_FULFILLED,
            'ready' => Config::RES_READY,
        ]);

        return $stmt->fetchColumn() !== false;
    }
}