<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;

class UtilisateursController extends AppController
{
    private const ALL_ROLES = [
        'super_admin','admin','comptable','responsable_formation',
        'formateur','commercial','gestionnaire_immobilier','client',
    ];

    public function index(Request $r): void
    {
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/tableau-de-bord');
        }
        $pdo  = Database::getInstance();
        $stmt = $pdo->query(
            'SELECT u.id, u.email, u.is_active, u.created_at,
                    p.first_name, p.last_name, p.phone, p.city, p.job_title,
                    GROUP_CONCAT(ur.role ORDER BY ur.role ASC SEPARATOR ",") AS roles
             FROM users u
             LEFT JOIN profiles p ON p.id = u.id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             GROUP BY u.id
             ORDER BY u.created_at DESC'
        );
        $users = $stmt->fetchAll();
        foreach ($users as &$u) {
            $u['roles'] = $u['roles'] ? explode(',', $u['roles']) : [];
        }
        unset($u);
        $this->view('app/utilisateurs/index', [
            'pageTitle'   => 'Utilisateurs & rôles',
            'users'       => $users,
            'allRoles'    => self::ALL_ROLES,
            'canEdit'     => Auth::hasRole('super_admin'),
            'currentId'   => Auth::id(),
            'old'         => Session::flash('old') ?? [],
            'errors'      => Session::flash('errors') ?? [],
        ]);
    }

    public function store(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole('super_admin')) {
            $this->redirect('/app/utilisateurs');
        }
        $email     = trim($r->input('email', ''));
        $password  = $r->input('password', '');
        $firstName = trim($r->input('first_name', ''));
        $lastName  = trim($r->input('last_name', ''));
        $phone     = trim($r->input('phone', ''));
        $jobTitle  = trim($r->input('job_title', ''));
        $roles     = (array) ($r->input('roles') ?? []);

        $errors = [];
        if (!$email)                          $errors['email']    = 'E-mail obligatoire.';
        elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'E-mail invalide.';
        if (strlen($password) < 8)            $errors['password'] = 'Mot de passe : 8 caractères minimum.';
        if (empty($roles))                    $errors['roles']    = 'Choisissez au moins un rôle.';

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', compact('email','firstName','lastName','phone','jobTitle','roles'));
            $this->redirect('/app/utilisateurs');
        }

        $pdo = Database::getInstance();
        $dup = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $dup->execute([$email]);
        if ($dup->fetch()) {
            Session::flash('errors', ['email' => 'Cet e-mail est déjà utilisé.']);
            Session::flash('old', compact('email','firstName','lastName','phone','jobTitle','roles'));
            $this->redirect('/app/utilisateurs');
        }

        $id   = Auth::uuid();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $now  = date('Y-m-d H:i:s');

        $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, email_verified, is_active, created_at, updated_at) VALUES (?,?,?,1,1,?,?)'
        )->execute([$id, $email, $hash, $now, $now]);

        $pdo->prepare(
            'INSERT INTO profiles (id, first_name, last_name, phone, job_title, created_at, updated_at) VALUES (?,?,?,?,?,?,?)'
        )->execute([$id, $firstName ?: null, $lastName ?: null, $phone ?: null, $jobTitle ?: null, $now, $now]);

        $roles = array_unique(array_intersect($roles, self::ALL_ROLES));
        foreach ($roles as $role) {
            $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?,?)')->execute([$id, $role]);
        }

        Session::flash('success', 'Compte créé.');
        $this->redirect('/app/utilisateurs');
    }

    public function toggle(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/utilisateurs');
        }
        $id = $r->param('id', '');
        if ($id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas vous suspendre vous-même.');
            $this->redirect('/app/utilisateurs');
        }
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT is_active FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if (!$user) $this->abort(404);
        $pdo->prepare('UPDATE users SET is_active=?, updated_at=? WHERE id=?')
            ->execute([(int) !$user['is_active'], date('Y-m-d H:i:s'), $id]);
        Session::flash('success', 'Statut mis à jour.');
        $this->redirect('/app/utilisateurs');
    }

    public function updateRole(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole('super_admin')) {
            $this->redirect('/app/utilisateurs');
        }
        $id    = $r->param('id', '');
        $roles = array_unique(array_intersect((array) ($r->input('roles') ?? []), self::ALL_ROLES));
        if (empty($roles)) {
            Session::flash('error', 'Sélectionnez au moins un rôle.');
            $this->redirect('/app/utilisateurs');
        }
        $pdo = Database::getInstance();
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
        foreach ($roles as $role) {
            $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?,?)')->execute([$id, $role]);
        }

        // Also update profile fields if submitted
        $firstName = trim($r->input('first_name', ''));
        $lastName  = trim($r->input('last_name', ''));
        $phone     = trim($r->input('phone', ''));
        $jobTitle  = trim($r->input('job_title', ''));
        $email     = trim($r->input('email', ''));
        $password  = $r->input('password', '');

        $pdo->prepare('UPDATE profiles SET first_name=?, last_name=?, phone=?, job_title=?, updated_at=? WHERE id=?')
            ->execute([$firstName ?: null, $lastName ?: null, $phone ?: null, $jobTitle ?: null, date('Y-m-d H:i:s'), $id]);

        if ($email) {
            $pdo->prepare('UPDATE users SET email=?, updated_at=? WHERE id=?')
                ->execute([$email, date('Y-m-d H:i:s'), $id]);
        }
        if ($password && strlen($password) >= 8) {
            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash=?, updated_at=? WHERE id=?')
                ->execute([$hash, date('Y-m-d H:i:s'), $id]);
        }

        Session::flash('success', 'Compte mis à jour.');
        $this->redirect('/app/utilisateurs');
    }

    public function destroy(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/utilisateurs');
        }
        $id = $r->param('id', '');
        if ($id === Auth::id()) {
            Session::flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            $this->redirect('/app/utilisateurs');
        }
        $pdo = Database::getInstance();
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM profiles WHERE id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        Session::flash('success', 'Compte supprimé.');
        $this->redirect('/app/utilisateurs');
    }

    // Legacy stubs
    public function show(Request $r): void       { $this->abort(404); }
    public function create(Request $r): void     { $this->redirect('/app/utilisateurs'); }
    public function edit(Request $r): void       { $this->redirect('/app/utilisateurs'); }
    public function update(Request $r): void     { $this->redirect('/app/utilisateurs'); }
    public function initiate(Request $r): void   { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void  { $this->redirect('/app/paiements'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void   { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void     { $this->redirect('/app/utilisateurs'); }
    public function monLogement(Request $r): void { $this->redirect('/app/utilisateurs'); }
}
