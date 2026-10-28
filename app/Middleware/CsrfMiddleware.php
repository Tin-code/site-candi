<?php

namespace App\Middleware;

use App\Core\Csrf;
use App\Core\Request;

class CsrfMiddleware
{
    public function handle(Request $request): void
    {
        if (!in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            Csrf::check();
        }
    }
}
