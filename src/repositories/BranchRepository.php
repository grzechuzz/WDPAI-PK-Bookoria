<?php

require_once __DIR__ . '/Repository.php';


final class BranchRepository extends Repository
{
    public function findAll(): array
    {
        $sql = "
            SELECT br.id, br.name, br.city, br.address_line1, br.address_line2, br.postal_code, br.region, br.timezone, br.country_code, br.currency_code,
                (
                    br.city || ', ' || br.name
                    || CASE WHEN br.address_line1 IS NOT NULL AND br.address_line1 <> '' 
                        THEN ' — ' || br.address_line1 ELSE '' END
                    || CASE WHEN br.postal_code IS NOT NULL AND br.postal_code <> '' 
                        THEN ', ' || br.postal_code ELSE '' END
                ) AS label
            FROM branches br
            ORDER BY br.city ASC, br.name ASC, br.id ASC
        ";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $branchId): ?array
    {
        $sql = "
            SELECT br.id, br.name, br.city, br.address_line1, br.address_line2, br.postal_code, br.region, br.timezone, br.country_code, br.currency_code,
                (
                    br.city || ', ' || br.name
                    || CASE WHEN br.address_line1 IS NOT NULL AND br.address_line1 <> '' 
                        THEN ' — ' || br.address_line1 ELSE '' END
                    || CASE WHEN br.postal_code IS NOT NULL AND br.postal_code <> '' 
                        THEN ', ' || br.postal_code ELSE '' END
                ) AS label
            FROM branches br
            WHERE br.id = :id
            LIMIT 1
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $branchId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function existsById(int $branchId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM branches WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $branchId]);
        return $stmt->fetchColumn() !== false;
    }
}