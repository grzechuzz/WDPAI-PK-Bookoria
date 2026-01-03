<?php

require_once __DIR__ . '/AppController.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../repositories/LoanRepository.php';
require_once __DIR__ . '/../repositories/ReservationRepository.php';
require_once __DIR__ . '/../services/ProfileService.php';


final class ProfileController extends AppController
{
    private ProfileService $profileService;

    public function __construct()
    {
        $db = Database::connect();
        $loanRepo = new LoanRepository($db);
        $reservationRepo = new ReservationRepository($db);
        $this->profileService = new ProfileService($loanRepo, $reservationRepo);
    }

    public function index()
    {
        Auth::requireLogin();

        $userId = Auth::userId();
        if ($userId === null) {
            $this->redirect('/login');
            return;
        }

        $data = $this->profileService->getProfileData($userId);
        $this->render('/profile', $data);
    }
}
