<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

class MessagesSmsController extends AppController
{
    private function requireAccess(): void
    {
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/tableau-de-bord');
        }
    }

    public function index(Request $r): void
    {
        $this->requireAccess();

        $pdo      = Database::getInstance();
        $messages = $pdo->query(
            "SELECT id, to_phone, body, event, status, attempts, error, sent_at, created_at
             FROM outbound_messages
             ORDER BY created_at DESC
             LIMIT 200"
        )->fetchAll();

        $this->view('app/messages-sms/index', [
            'pageTitle' => 'SMS automatiques — CaNDI',
            'messages'  => $messages,
            'success'   => Session::flash('success'),
            'info'      => Session::flash('info'),
        ]);
    }

    public function dispatch(Request $r): void
    {
        $this->requireAccess();
        Csrf::check();

        // SMS service not yet connected — graceful fallback
        Session::flash('info', 'Service SMS non configuré : les messages restent en file d\'attente. Configurez les clés d\'API SMS dans les paramètres.');
        $this->redirect('/app/messages-sms');
    }
}
