<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\Notification;

class NotificationsController extends AppController
{
    public function index(Request $r): void
    {
        $userId = Auth::id();
        $this->view('app/notifications/index', [
            'pageTitle'     => 'Notifications — CaNDI',
            'notifications' => Notification::forUser($userId, 60),
            'unreadCount'   => Notification::unreadCount($userId),
            'success'       => Session::flash('success'),
        ]);
    }

    public function markRead(Request $r): void
    {
        Csrf::check();
        Notification::markRead($r->param('id', ''), Auth::id());
        // AJAX-friendly: if the request wants JSON, return JSON
        if ($this->wantsJson($r)) {
            header('Content-Type: application/json');
            echo json_encode(['ok' => true]);
            exit;
        }
        $this->redirect('/app/notifications');
    }

    public function markAllRead(Request $r): void
    {
        Csrf::check();
        Notification::markAllRead(Auth::id());
        Session::flash('success', 'Toutes les notifications marquées comme lues.');
        $this->redirect('/app/notifications');
    }

    public function deleteRead(Request $r): void
    {
        Csrf::check();
        Notification::deleteRead(Auth::id());
        Session::flash('success', 'Notifications lues supprimées.');
        $this->redirect('/app/notifications');
    }

    // -----------------------------------------------------------------------

    private function wantsJson(Request $r): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
            || str_contains($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '', 'XMLHttpRequest');
    }

    // Stubs
    public function show(Request $r): void      { $this->abort(404); }
    public function create(Request $r): void    { $this->redirect('/app/notifications'); }
    public function store(Request $r): void     { $this->redirect('/app/notifications'); }
    public function edit(Request $r): void      { $this->redirect('/app/notifications'); }
    public function update(Request $r): void    { $this->redirect('/app/notifications'); }
    public function destroy(Request $r): void   { $this->redirect('/app/notifications'); }
    public function initiate(Request $r): void  { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void { $this->redirect('/app/paiements'); }
    public function toggle(Request $r): void    { $this->redirect('/app/utilisateurs'); }
    public function updateRole(Request $r): void     { $this->redirect('/app/utilisateurs'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function loyers(Request $r): void    { $this->redirect('/app/notifications'); }
    public function monLogement(Request $r): void { $this->redirect('/app/notifications'); }
}
