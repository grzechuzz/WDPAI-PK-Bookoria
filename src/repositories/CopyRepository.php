<?php

require_once __DIR__ . '/Repository.php';

final class CopyRepository extends Repository
{
    public function insertCopy(int $bookId, int $branchId, string $inventoryCode): int
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
        ]);

        return (int)$stmt->fetchColumn();
    }

    public function existsByInventoryCode(string $inventoryCode): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM copies WHERE inventory_code = :code LIMIT 1");
        $stmt->execute(['code' => $inventoryCode]);
        return $stmt->fetchColumn() !== false;
    }

    public function bookExists(int $bookId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM books WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $bookId]);
        return $stmt->fetchColumn() !== false;
    }

    public function branchExists(int $branchId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM branches WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $branchId]);
        return $stmt->fetchColumn() !== false;
    }
}
