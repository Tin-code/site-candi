<?php

namespace App\Core;

abstract class Controller
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    // -------------------------------------------------------------------------
    // Rendu de vue
    // -------------------------------------------------------------------------

    protected function view(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        View::make($view, $data, $layout);
    }

    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit;
    }

    // -------------------------------------------------------------------------
    // Redirections
    // -------------------------------------------------------------------------

    protected function redirect(string $path, int $status = 302): void
    {
        $base = rtrim(env('APP_URL', ''), '/');
        $path = '/' . ltrim($path, '/');
        header('Location: ' . $base . $path, true, $status);
        exit;
    }

    protected function redirectBack(): void
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';
        header('Location: ' . $referer, true, 302);
        exit;
    }

    // -------------------------------------------------------------------------
    // Flash messages
    // -------------------------------------------------------------------------

    protected function withSuccess(string $message): static
    {
        Session::flash('success', $message);
        return $this;
    }

    protected function withError(string $message): static
    {
        Session::flash('error', $message);
        return $this;
    }

    protected function withErrors(array $errors): static
    {
        Session::flash('errors', $errors);
        return $this;
    }

    protected function withOld(array $data): static
    {
        Session::flash('old', $data);
        return $this;
    }

    // -------------------------------------------------------------------------
    // Validation
    // -------------------------------------------------------------------------

    protected function validate(array $rules): array
    {
        $v = Validator::make($this->request->all(), $rules);
        if ($v->fails()) {
            $this->withErrors($v->errors())->withOld($this->request->all());
            $this->redirectBack();
        }
        return $this->request->all();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function abort(int $code, string $message = ''): void
    {
        http_response_code($code);
        $messages = [404 => 'Page introuvable', 403 => 'Accès interdit', 500 => 'Erreur serveur'];
        $title = $messages[$code] ?? 'Erreur';
        $this->view('errors/error', ['code' => $code, 'title' => $title, 'message' => $message]);
        exit;
    }
}
