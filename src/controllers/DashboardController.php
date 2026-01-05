<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Config.php';
require_once __DIR__ . '/../core/Auth.php';


class DashboardController extends AppController
{
    public function index(): void
    {
        Auth::requireLogin();
        
        $roleId = Auth::roleId();
        
        $this->render('dashboard', [
            'role_id' => $roleId,
            'is_admin' => Config::isAdmin($roleId),
            'is_librarian' => Config::isLibrarian($roleId),
            'is_reader' => Config::isReader($roleId),
        ]);
    }
}