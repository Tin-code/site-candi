<?php

namespace App\Core;

use function App\Core\env;

class Request
{
    private array $params = [];

    public function method(): string
    {
        $override = $_POST['_method'] ?? $_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? '';
        if ($override && in_array(strtoupper($override), ['PUT', 'PATCH', 'DELETE'], true)) {
            return strtoupper($override);
        }
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public function uri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        $pos = strpos($uri, '?');
        return $pos !== false ? substr($uri, 0, $pos) : $uri;
    }

    public function path(): string
    {
        $uri     = $this->uri();
        // Base dérivée de APP_URL (ex: /site-candi) pour ignorer le sous-dossier /public/
        $appPath = parse_url(env('APP_URL', '/'), PHP_URL_PATH) ?? '';
        $base    = rtrim($appPath, '/');
        if ($base !== '' && str_starts_with($uri, $base)) {
            $uri = substr($uri, strlen($base));
        }
        return $uri === '' ? '/' : $uri;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($_GET[$key] ?? $default);
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($_POST[$key] ?? $default);
    }

    public function input(string $key, mixed $default = null): mixed
    {
        return $this->sanitize($_POST[$key] ?? $_GET[$key] ?? $default);
    }

    public function all(): array
    {
        return array_map([$this, 'sanitize'], $_POST + $_GET);
    }

    public function only(array $keys): array
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->sanitize($_POST[$key] ?? $_GET[$key] ?? null);
        }
        return $result;
    }

    public function json(): array
    {
        $raw = file_get_contents('php://input');
        return json_decode($raw, true) ?? [];
    }

    public function isPost(): bool   { return $this->method() === 'POST'; }
    public function isGet(): bool    { return $this->method() === 'GET'; }
    public function isAjax(): bool   { return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'; }
    public function wantsJson(): bool { return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json'); }

    public function setParams(array $params): void { $this->params = $params; }
    public function param(string $key, mixed $default = null): mixed { return $this->params[$key] ?? $default; }

    public function file(string $key): ?array
    {
        return isset($_FILES[$key]['error']) && $_FILES[$key]['error'] !== UPLOAD_ERR_NO_FILE
            ? $_FILES[$key]
            : null;
    }

    public function ip(): string
    {
        return $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? '';
    }

    private function sanitize(mixed $value): mixed
    {
        if (is_string($value)) {
            return trim($value);
        }
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        return $value;
    }
}
