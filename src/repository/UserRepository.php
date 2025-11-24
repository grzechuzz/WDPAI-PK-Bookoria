<?php

require_once __DIR__."/../../Database.php";
require_once 'Repository.php';

class UserRepository extends Repository {
    
    public function getUsers(): ?array {
        $query = $this->database->connect()->prepare(
            "
            SELECT * FROM public.users;
            "
        );

        $query->execute();

        $users = $query->fetchall(PDO::FETCH_ASSOC);
        return $users;
    }
}