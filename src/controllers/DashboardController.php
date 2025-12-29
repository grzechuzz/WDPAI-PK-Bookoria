<?php

require_once 'AppController.php';



class DashboardController extends AppController {

    public function index() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id'])) {
            $this->redirect('/login');
            return;
        }

        $roleId = $_SESSION['role_id'];

        $this->render('dashboard', ['role_id' => $roleId]);
    }
}