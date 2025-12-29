<?php

abstract class AppController {

    protected function isGet() {
        return $_SERVER['REQUEST_METHOD'] === 'GET';
    }

    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function render(string $template = null, array $variables = []) {
        $templatePath = __DIR__ . '/../views/' . $template . '.php';
        $output = 'File not found: ' . $templatePath;
                
        if (file_exists($templatePath)) {
            extract($variables);

            ob_start();
            include $templatePath;
            $content = ob_get_clean(); 

  
            ob_start();
            include __DIR__ . '/../views/layout.php';
            $output = ob_get_clean();
        } 
        
        echo $output;
    }

    protected function redirect(string $url) {
        header("Location: {$url}");
        exit;
    }
}