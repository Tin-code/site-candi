<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';
    private const TOKEN_BYTES = 32;

    public static function token(): string
    {
        Session::start();
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(self::TOKEN_BYTES)));
        }
        return Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        $token = self::token();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    public static function verify(string $token): bool
    {
        Session::start();
        $stored = Session::get(self::TOKEN_KEY, '');
        return hash_equals($stored, $token);
    }

    /** À appeler dans le middleware des routes POST/PUT/DELETE/PATCH. */
    public static function check(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!self::verify($token)) {
            http_response_code(419);
            die('Token CSRF invalide. Veuillez rafraîchir la page et réessayer.');
        }
    }
}
