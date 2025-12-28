<?php 

class Env {
    public static function load(string $path) {
        if (!file_exists($path)) {
            return;
        }

        foreach (file($path) as $line) {
            $line = trim($line);
            if ($line == '' || $line[0] === '#') {
                continue;
            }
            
            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                 continue;
            }
    
            $_ENV[$parts[0]] = $parts[1];
        }
    }
}