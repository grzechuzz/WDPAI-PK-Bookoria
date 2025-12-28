<?php

require_once __DIR__ . '/../src/core/Env.php';
require_once __DIR__ . '/../src/core/Routing.php';
require_once __DIR__ . '/../src/core/Database.php';


Env::load(__DIR__ . '/../.env');

$router = Routing::getInstance();

$path = $_SERVER['REQUEST_URI']; 
$router->run($_SERVER['REQUEST_METHOD'], $path);