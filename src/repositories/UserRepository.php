<?php

require_once  __DIR__ . '/Repository.php';


final class UserRepository extends Repository {
    public function findByEmail(string $email) 
    {
        $sql = "SELECT id, email, password_hash, role_id, created_at FROM users 
                WHERE email = :email LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findById(int $id) 
    {
        $sql = "SELECT id, email, password_hash, role_id, created_at FROM users
                WHERE id = :id LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function create(string $email, string $passwordHash, int $roleId) 
    {
        $sql = "INSERT INTO users (email, password_hash, role_id)
                VALUES (:email, :password_hash, :role_id) RETURNING id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'email' => $email,
            'password_hash' => $passwordHash,
            'role_id' => $roleId  
        ]);

        return (int)$stmt->fetchColumn();
    }

     public function existsByEmail(string $email)
    {
        $sql = "SELECT 1 FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['email' => $email]);

        return $stmt->fetchColumn() !== false;
    }

    public function findAllWithRoles()
    {
        $sql = "SELECT u.id, u.email, u.role_id, r.name as role_name, u.created_at
                FROM users u
                JOIN roles r ON r.id = u.role_id
                ORDER BY u.id ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateRole(int $userId, int $roleId)
    {
        $stmt = $this->db->prepare("UPDATE users SET role_id = :role_id WHERE id = :id RETURNING id");
        $stmt->execute(['id' => $userId, 'role_id' => $roleId]);
        return $stmt->fetchColumn() !== false;
    }
}