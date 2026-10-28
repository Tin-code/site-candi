<?php

namespace App\Middleware;

use App\Core\Auth;
use App\Core\Request;

use function App\Core\env;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        if (!Auth::check()) {
            $base = rtrim(env('APP_URL', ''), '/');
            header('Location: ' . $base . '/auth?redirect=' . urlencode($request->path()), true, 302);
            exit;
        }
    }
}
