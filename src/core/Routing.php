<?php

require_once 'src/controllers/SecurityController.php';
require_once 'src/controllers/DashboardController.php';

// /dashboard/134234 - regex z id + singletonik

class Routing
{
    private static ?Routing $instance = null;

    public static $routes = [
        "login" => [
            "controller" => "SecurityController",
            "action" => "login",
        ],
        "register" => [
            "controller" => "SecurityController",
            "action" => "register",
        ],
        "dashboard" => [
            "controller" => "DashboardController",
            "action" => "index",
        ],
    ];


    public static function getInstance(): Routing
    {
        if (self::$instance === null) {
            self::$instance = new Routing();
        }
        
        return self::$instance;
    }

    public static function run(string $path)
    {
        $id = null;
        $action = null;

        if (preg_match('/^(\w+)\/(\d+)$/', $path, $matches)) 
        {
            $action = $matches[1];
            $id = (int)$matches[2];
        } 
        else
        {
            $action = $path;
        }

        switch ($path) {
            case "dashboard":
            case "login":
            case "register":
                $controller = Routing::$routes[$path]["controller"];
                $action = Routing::$routes[$path]["action"];

                $controllerObj = new $controller;
                $id = null;
                $controllerObj->$action($id);
                break;

            default:
                include 'public/views/404.html';
                break;
        }
    }
}