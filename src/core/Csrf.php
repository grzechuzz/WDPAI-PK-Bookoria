<?php


final class Csrf
{
    private const TOKEN_KEY = 'csrf_token';

    public static function token()
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function field()
    {
        return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(self::token()) . '">';
    }

    public static function verify()
    {
        $submitted = $_POST['csrf_token'] ?? '';
        $stored = $_SESSION[self::TOKEN_KEY] ?? '';
        
        if (empty($stored) || empty($submitted)) {
            return false;
        }
        
        return hash_equals($stored, $submitted);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            throw new RuntimeException('Nieprawidłowy token CSRF. Odśwież stronę i spróbuj ponownie.');
        }
    }
}