<?php

require_once __DIR__ . '/AppController.php';


class DashboardController extends AppController {

    public function index() {
        Auth::requireLogin();
        $roleId = $_SESSION['role_id'];
        $this->render('dashboard', ['role_id' => $roleId]);
    }
}