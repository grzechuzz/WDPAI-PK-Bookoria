<?php

require_once  __DIR__ . '/Repository.php';


final class RoleRepository extends Repository
{
    public function findIdByName(string $name) 
    {
        $sql = "SELECT id FROM roles WHERE name = :name LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['name' => $name]);

        $id = $stmt->fetchColumn();
        return ($id === false) ? null : (int)$id;
    }
} 
