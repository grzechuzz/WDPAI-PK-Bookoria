<?php


class Routing
{
    private static ?Routing $instance = null;

    private array $routes = [
        'GET' => [],
        'POST' => []
    ];

    private function __construct() {}


    public static function getInstance(): Routing
    {
        if (self::$instance === null) {
            self::$instance = new Routing();
        }
        return self::$instance;
    }

    public function get(string $path, callable $handler) {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler) {
        $this->routes['POST'][$path] = $handler;
    }

    public function run(string $method, string $path)
    {
        $method = strtoupper($method);
        $path = parse_url($path, PHP_URL_PATH) ?? '/';
        $path = rtrim($path, '/') ?: '/';

        $handler = $this->routes[$method][$path] ?? null;
        if ($handler === null) {
            http_response_code(404);
            $this->render404();
            return;
        }

        $handler();
    }

    private function render404(): 
    {
        $templatePath = __DIR__ . '/../views/errors/404.php';
        
        if (file_exists($templatePath)) {
            ob_start();
            include $templatePath;
            $content = ob_get_clean();

            include __DIR__ . '/../views/layout.php';
        } else {
            echo '404 Not Found';
        }
    }
}