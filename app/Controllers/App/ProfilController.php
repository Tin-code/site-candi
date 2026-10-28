<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;

class ProfilController extends AppController
{
    public function index(Request $request): void
    {
        $this->view('app/profil', [
            'pageTitle' => 'Mon profil — CaNDI',
            'user'      => Auth::user(),
        ]);
    }

    public function update(Request $request): void
    {
        Csrf::check();

        $userId   = Auth::id();
        $fields   = [
            'first_name' => trim($request->post('first_name', '')) ?: null,
            'last_name'  => trim($request->post('last_name', ''))  ?: null,
            'phone'      => trim($request->post('phone', ''))      ?: null,
            'city'       => trim($request->post('city', ''))       ?: null,
            'job_title'  => trim($request->post('job_title', ''))  ?: null,
        ];

        Database::getInstance()->prepare(
            'UPDATE profiles
             SET first_name = ?, last_name = ?, phone = ?, city = ?, job_title = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $fields['first_name'], $fields['last_name'],
            $fields['phone'], $fields['city'], $fields['job_title'],
            $userId,
        ]);

        Auth::refreshUser();
        $this->withSuccess('Profil mis à jour.')->redirect('/app/profil');
    }

    public function updatePassword(Request $request): void
    {
        Csrf::check();

        $password = $request->post('password', '');
        if (strlen($password) < 8) {
            $this->withError('Le mot de passe doit contenir au moins 8 caractères.')->redirect('/app/profil');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        Database::getInstance()->prepare(
            'UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?'
        )->execute([$hash, Auth::id()]);

        $this->withSuccess('Mot de passe modifié.')->redirect('/app/profil');
    }
}
