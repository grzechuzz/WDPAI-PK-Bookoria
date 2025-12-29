<?php

require_once  __DIR__ . '/Repository.php';


final class RoleRepository extends Repository
{
    public function findIdByCode(string $code) 
    {
        $sql = "SELECT id FROM roles WHERE code = :code LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['code' => $code]);

        $id = $stmt->fetchColumn();
        return ($id === false) ? null : (int)$id;
    }
} 
