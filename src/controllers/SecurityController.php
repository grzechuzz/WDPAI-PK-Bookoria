<?php

require_once 'AppController.php';

class SecurityController extends AppController{

    public function login() {
        // zwroc html logowania, przetworz dane itd.
        return $this->render("login", ["message" => "Błędne hasło!"]);
    }
}