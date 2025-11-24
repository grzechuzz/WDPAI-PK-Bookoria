<?php

require_once __DIR__."/../../Database.php";

class Repository {
    
    protected $database;

    public function __constructor() {
        $this->database = new Database();
    }
}