<?php

require_once __DIR__ . '/../src/core/Env.php';
require_once __DIR__ . '/../src/core/Routing.php';
require_once __DIR__ . '/../src/core/Database.php';
require_once __DIR__ . '/../src/core/Auth.php';
require_once __DIR__ . '/../src/controllers/AuthController.php';
require_once __DIR__ . '/../src/controllers/DashboardController.php';
require_once __DIR__ . '/../src/controllers/BookController.php';


Env::load(__DIR__ . '/../.env');

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

$path = $_SERVER['REQUEST_URI']; 
$router->run($_SERVER['REQUEST_METHOD'], $path);