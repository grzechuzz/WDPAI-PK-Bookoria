<?php


final class Http
{
    public static function redirect(string $to, int $code = 302)
    {
        header("Location: {$to}", true, $code);
        exit;
    }

    public static function html(string $html, int $code = 200)
    {
        http_response_code($code);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    public static function text(string $text, int $code = 200)
    {
        http_response_code($code);
        header('Content-Type: text/plain; charset=utf-8');
        echo $text;
        exit;
    }
}
