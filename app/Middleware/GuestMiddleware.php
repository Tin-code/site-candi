<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

use function App\Core\env;

class GuestMiddleware
{
    public function handle(Request $request): void
    {
        if (Auth::check()) {
            $base = rtrim(env('APP_URL', ''), '/');
            header('Location: ' . $base . '/app/tableau-de-bord', true, 302);
            exit;
        }
    }
}
