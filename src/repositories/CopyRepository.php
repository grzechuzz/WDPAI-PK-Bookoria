<?php

require_once __DIR__ . '/Repository.php';
require_once __DIR__ . '/../core/Config.php';


final class CopyRepository extends Repository
{
    public function insertCopy(int $bookId, int $branchId, string $inventoryCode)
    {
        $stmt = $this->db->prepare("
            INSERT INTO copies (book_id, branch_id, inventory_code, status, created_at)
            VALUES (:book_id, :branch_id, :inventory_code, 'AVAILABLE', now())
            RETURNING id
        ");

        $stmt->execute([
            'book_id' => $bookId,
            'branch_id' => $branchId,
            'inventory_code' => $inventoryCode,
            'available' => Config::COPY_AVAILABLE,
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function existsByInventoryCode(string $inventoryCode)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM copies WHERE inventory_code = :code LIMIT 1");
        $stmt->execute(['code' => $inventoryCode]);
        return $stmt->fetchColumn() !== false;
    }

    public function bookExists(int $bookId)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM books WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $bookId]);
        return $stmt->fetchColumn() !== false;
    }

    public function branchExists(int $branchId)
    {
        $stmt = $this->db->prepare("SELECT 1 FROM branches WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $branchId]);
        return $stmt->fetchColumn() !== false;
    }

    public function findById(int $copyId)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.book_id, c.branch_id, c.inventory_code, c.status, c.created_at, b.title, b.isbn13, (br.city || ', ' || br.name) as branch_label
            FROM copies c
            JOIN books b ON b.id = c.book_id
            JOIN branches br ON br.id = c.branch_id
            WHERE c.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $copyId]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

      public function findByInventoryCode(string $inventoryCode)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.book_id, c.branch_id, c.inventory_code, c.status, c.created_at, b.title, b.isbn13, (br.city || ', ' || br.name) as branch_label
            FROM copies c
            JOIN books b ON b.id = c.book_id
            JOIN branches br ON br.id = c.branch_id
            WHERE c.inventory_code = :code
            LIMIT 1
        ");
        $stmt->execute(['code' => $inventoryCode]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateStatus(int $copyId, string $newStatus)
    {
        $stmt = $this->db->prepare("UPDATE copies SET status = :status WHERE id = :id RETURNING id");
        $stmt->execute([
            'id' => $copyId,
            'status' => $newStatus,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function updateStatusIf(int $copyId, string $expectedStatus, string $newStatus)
    {
        $stmt = $this->db->prepare("
            UPDATE copies SET status = :new_status 
            WHERE id = :id AND status = :expected_status
            RETURNING id
        ");
        $stmt->execute([
            'id' => $copyId,
            'expected_status' => $expectedStatus,
            'new_status' => $newStatus,
        ]);

        return $stmt->fetchColumn() !== false;
    }

    public function findByBookId(int $bookId)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.book_id, c.branch_id, c.inventory_code, c.status, c.created_at, (br.city || ', ' || br.name) as branch_label
            FROM copies c
            JOIN branches br ON br.id = c.branch_id
            WHERE c.book_id = :book_id
            ORDER BY br.city ASC, br.name ASC, c.id ASC
        ");
        $stmt->execute(['book_id' => $bookId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByBranchId(int $branchId)
    {
        $stmt = $this->db->prepare("
            SELECT c.id, c.book_id, c.branch_id, c.inventory_code, c.status, c.created_at, b.title, b.isbn13
            FROM copies c
            JOIN books b ON b.id = c.book_id
            WHERE c.branch_id = :branch_id
            ORDER BY b.title ASC, c.id ASC
        ");
        $stmt->execute(['branch_id' => $branchId]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
