<?php

require_once __DIR__ . '/../src/core/Env.php';
require_once __DIR__ . '/../src/core/Config.php';
require_once __DIR__ . '/../src/core/Routing.php';
require_once __DIR__ . '/../src/core/Database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';
require_once __DIR__ . '/../src/controllers/BookController.php';
require_once __DIR__ . '/../src/controllers/ProfileController.php';
require_once __DIR__ . '/../src/controllers/CopyController.php';
require_once __DIR__ . '/../src/controllers/CirculationController.php';
require_once __DIR__ . '/../src/controllers/UserController.php';

Env::load(__DIR__ . '/../.env');

session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Strict',
]);

session_start();

$router = Routing::getInstance();

$router->get('/register', [new AuthController(), 'register']);
$router->post('/register', [new AuthController(), 'register']);
$router->get('/login', [new AuthController(), 'login']);
$router->post('/login', [new AuthController(), 'login']);
$router->get('/logout', [new AuthController(), 'logout']);
$router->get('/dashboard', [new DashboardController(), 'index']);
$router->get('/repository', [new BookController(), 'index']);
$router->get('/book', [new BookController(), 'show']);
$router->get('/add-book', [new BookController(), 'add']);
$router->post('/add-book', [new BookController(), 'add']);
$router->get('/profile', [new ProfileController(), 'index']);
$router->post('/loan/renew', [new ProfileController(), 'renewLoan']);
$router->post('/reservation/cancel', [new ProfileController(), 'cancelReservation']);
$router->post('/reservation/create', [new ProfileController(), 'createReservation']);
$router->get('/copy/add', [new CopyController(), 'add']);
$router->post('/copy/add', [new CopyController(), 'add']);
$router->get('/circulation', [new CirculationController(), 'index']);
$router->post('/circulation/issue', [new CirculationController(), 'issue']);
$router->post('/circulation/return', [new CirculationController(), 'returnBook']);
$router->get('/users', [new UserController(), 'index']);
$router->post('/users/role', [new UserController(), 'changeRole']);
$router->post('/users/assign-branch', [new UserController(), 'assignBranch']);
$router->post('/users/remove-branch', [new UserController(), 'removeBranch']);

$path = $_SERVER['REQUEST_URI']; 
$router->run($_SERVER['REQUEST_METHOD'], $path);