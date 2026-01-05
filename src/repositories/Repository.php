<?php


abstract class Repository {
    protected PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function getDb()
    {
        return $this->db;
    }
}