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
        $sql = "SELECT u.id, u.email, u.role_id, r.code as role_name, u.created_at
                FROM users u
                JOIN roles r ON r.id = u.role_id
                ORDER BY u.id ASC";
        
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

     public function findPaginatedWithRoles(int $limit, int $offset, ?string $emailSearch, ?int $roleFilter)
    {
        $where = "1=1";
        $params = ['limit' => $limit, 'offset' => $offset];

        if ($emailSearch) {
            $where .= " AND LOWER(u.email) LIKE :search";
            $params['search'] = '%' . mb_strtolower($emailSearch) . '%';
        }

        if ($roleFilter) {
            $where .= " AND u.role_id = :role_id";
            $params['role_id'] = $roleFilter;
        }

        $sql = "SELECT u.id, u.email, u.role_id, r.code as role_name, u.created_at
                FROM users u
                JOIN roles r ON r.id = u.role_id
                WHERE {$where}
                ORDER BY u.id ASC
                LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue($key, $value, $type);
        }
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function countFiltered(?string $emailSearch, ?int $roleFilter)
    {
        $where = "1=1";
        $params = [];

        if ($emailSearch) {
            $where .= " AND LOWER(email) LIKE :search";
            $params['search'] = '%' . mb_strtolower($emailSearch) . '%';
        }

        if ($roleFilter) {
            $where .= " AND role_id = :role_id";
            $params['role_id'] = $roleFilter;
        }

        $sql = "SELECT COUNT(*) FROM users WHERE {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return (int)$stmt->fetchColumn();
    }

    public function updateRole(int $userId, int $roleId)
    {
        $stmt = $this->db->prepare("UPDATE users SET role_id = :role_id WHERE id = :id RETURNING id");
        $stmt->execute(['id' => $userId, 'role_id' => $roleId]);
        return $stmt->fetchColumn() !== false;
    }
}