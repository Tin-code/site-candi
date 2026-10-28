<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\PhoneVerification;
use App\Services\NimbaSms;

class AuthController extends Controller
{
    // -----------------------------------------------------------------------
    // Affichage de la page d'authentification
    // -----------------------------------------------------------------------

    public function showLogin(Request $request): void
    {
        $this->view('auth/login', [
            'pageTitle' => 'Connexion — CaNDI',
            'metaDesc'  => 'Connectez-vous à votre espace CaNDI ou créez un compte.',
            'tab'       => $request->get('tab', 'phone'),
            'mode'      => $request->get('mode', 'signin'),
        ]);
    }

    // -----------------------------------------------------------------------
    // Connexion (email ou téléphone + mot de passe)
    // -----------------------------------------------------------------------

    public function login(Request $request): void
    {
        Csrf::check();

        $tab      = $request->post('tab', 'email');
        $password = $request->post('password', '');

        if ($tab === 'phone') {
            $raw    = trim($request->post('phone', ''));
            $digits = preg_replace('/\D/', '', $raw);
            if (strlen($digits) === 9) $digits = '224' . $digits;

            if (strlen($digits) < 9 || $password === '') {
                $this->withError('Numéro de téléphone et mot de passe obligatoires.')->redirect('/auth?tab=phone');
            }

            if (!Auth::attemptByPhone($digits, $password)) {
                $this->withError('Numéro ou mot de passe incorrect.')->redirect('/auth?tab=phone&mode=signin');
            }
        } else {
            $email = strtolower(trim($request->post('email', '')));

            if ($email === '' || $password === '') {
                $this->withError('Identifiants manquants.')->redirect('/auth?tab=email');
            }

            if (!Auth::attempt($email, $password)) {
                $this->withError('Identifiants incorrects.')->redirect('/auth?tab=email&mode=signin');
            }
        }

        $redirect = $request->post('redirect', '') ?: $request->get('redirect', '');
        $safe     = ($redirect !== '' && str_starts_with($redirect, '/')) ? $redirect : '/app/tableau-de-bord';
        $this->redirect($safe);
    }

    // -----------------------------------------------------------------------
    // Inscription e-mail
    // -----------------------------------------------------------------------

    public function register(Request $request): void
    {
        Csrf::check();

        $firstName = trim($request->post('first_name', ''));
        $lastName  = trim($request->post('last_name', ''));
        $phone     = trim($request->post('phone', ''));
        $email     = strtolower(trim($request->post('email', '')));
        $password  = $request->post('password', '');

        $errors = [];
        if (strlen($firstName) < 2) $errors[] = 'Prénom requis (2 caractères minimum).';
        if (strlen($lastName) < 2)  $errors[] = 'Nom requis (2 caractères minimum).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Adresse e-mail invalide.';
        if (strlen($password) < 8)  $errors[] = 'Mot de passe : 8 caractères minimum.';

        if ($errors) {
            $this->withErrors($errors)
                 ->withOld(['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName])
                 ->redirect('/auth?tab=email&mode=signup');
        }

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $this->withError('Cet e-mail est déjà utilisé.')
                 ->withOld(['email' => $email, 'first_name' => $firstName, 'last_name' => $lastName])
                 ->redirect('/auth?tab=email&mode=signup');
        }

        Auth::register([
            'first_name'     => $firstName,
            'last_name'      => $lastName,
            'phone'          => $phone ?: null,
            'email'          => $email,
            'password'       => $password,
            'email_verified' => 0,
        ]);

        $this->withSuccess('Compte créé, bienvenue !')->redirect('/app/tableau-de-bord');
    }

    // -----------------------------------------------------------------------
    // Envoi d'un OTP par SMS
    // -----------------------------------------------------------------------

    public function sendOtp(Request $request): void
    {
        Csrf::check();

        $raw     = trim($request->post('phone', ''));
        $purpose = $request->post('purpose', 'signup'); // signup | reset
        $digits  = preg_replace('/\D/', '', $raw);
        if (strlen($digits) === 9) $digits = '224' . $digits;

        if (strlen($digits) < 9) {
            $mode = $purpose === 'reset' ? 'reset' : 'signup';
            $this->withError('Numéro de téléphone invalide.')->redirect('/auth?tab=phone&mode=' . $mode);
        }

        if ($purpose === 'reset') {
            $short = strlen($digits) === 12 ? substr($digits, 3) : $digits;
            $stmt  = Database::getInstance()->prepare(
                'SELECT u.id FROM users u
                 LEFT JOIN profiles p ON p.id = u.id
                 WHERE (u.email = ? OR p.phone = ? OR p.phone = ?)
                   AND u.is_active = 1
                 LIMIT 1'
            );
            $stmt->execute([$digits . '@tel.candi.gn', $digits, $short]);
            if (!$stmt->fetch()) {
                $this->withError('Aucun compte trouvé pour ce numéro.')->redirect('/auth?tab=phone&mode=reset');
            }
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        PhoneVerification::create($digits, $code, $purpose);
        NimbaSms::sendOtp($digits, $code);

        $p = $purpose === 'reset' ? 'r' : 's';
        $this->redirect('/confirmer-telephone?t=' . urlencode($digits) . '&p=' . $p);
    }

    // JSON stub conservé pour compatibilité route
    public function verifyOtp(Request $request): void
    {
        $this->redirect('/confirmer-telephone');
    }

    // -----------------------------------------------------------------------
    // Page de confirmation OTP (GET)
    // -----------------------------------------------------------------------

    public function confirmPhone(Request $request): void
    {
        $this->view('auth/confirm-phone', [
            'pageTitle' => 'Confirmer mon numéro — CaNDI',
            'metaDesc'  => 'Saisissez le code reçu par SMS pour finaliser votre compte CaNDI.',
            'phone'     => $request->get('t', ''),
            'purpose'   => $request->get('p', 's'),
            'code'      => preg_replace('/\D/', '', $request->get('c', '')),
        ]);
    }

    // -----------------------------------------------------------------------
    // Vérification OTP + création compte / reset mot de passe (POST)
    // -----------------------------------------------------------------------

    public function verifyPhone(Request $request): void
    {
        Csrf::check();

        $phone     = preg_replace('/\D/', '', trim($request->post('phone', '')));
        $code      = trim($request->post('code', ''));
        $purpose   = $request->post('purpose', 's'); // 's' | 'r'
        $password  = $request->post('password', '');
        $firstName = trim($request->post('first_name', ''));
        $lastName  = trim($request->post('last_name', ''));

        if (strlen($phone) === 9) $phone = '224' . $phone;

        if (strlen($phone) < 9 || strlen($code) !== 6) {
            $this->withError('Données invalides.')->redirect('/auth?tab=phone');
        }

        $dbPurpose = $purpose === 'r' ? 'reset' : 'signup';
        $retryUrl  = '/auth?tab=phone&mode=' . ($purpose === 'r' ? 'reset' : 'signup');

        $otp = PhoneVerification::findValid($phone, $dbPurpose);
        if (!$otp) {
            $this->withError('Code expiré. Recommencez.')->redirect($retryUrl);
        }

        if ((int) $otp['attempts'] >= 5) {
            $this->withError('Trop de tentatives. Recommencez.')->redirect($retryUrl);
        }

        PhoneVerification::incrementAttempts($otp['id']);

        if (!hash_equals($otp['code'], $code)) {
            $this->withError('Code incorrect.')->redirect(
                '/confirmer-telephone?t=' . urlencode($phone) . '&p=' . $purpose
            );
        }

        if (strlen($password) < 8) {
            $this->withError('Mot de passe : 8 caractères minimum.')->redirect(
                '/confirmer-telephone?t=' . urlencode($phone) . '&p=' . $purpose
            );
        }

        PhoneVerification::consume($otp['id']);

        $email = $phone . '@tel.candi.gn';
        $pdo   = Database::getInstance();

        if ($dbPurpose === 'signup') {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $this->withError('Ce numéro est déjà enregistré. Connectez-vous.')->redirect('/auth?tab=phone&mode=signin');
            }

            if (strlen($firstName) < 2 || strlen($lastName) < 2) {
                $this->withError('Prénom et nom requis.')->redirect(
                    '/confirmer-telephone?t=' . urlencode($phone) . '&p=s'
                );
            }

            Auth::register([
                'first_name'     => $firstName,
                'last_name'      => $lastName,
                'phone'          => $phone,
                'email_verified' => 1,
                'password'       => $password,
            ]);

            $this->withSuccess('Compte créé, bienvenue !')->redirect('/app/tableau-de-bord');
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->withError('Compte introuvable.')->redirect('/auth?tab=phone');
            }

            $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
            $pdo->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?')
                ->execute([$hash, $user['id']]);

            Auth::loginById($user['id']);
            $this->withSuccess('Mot de passe modifié.')->redirect('/app/tableau-de-bord');
        }
    }

    // -----------------------------------------------------------------------
    // Redirections stub pour routes e-mail reset (non exposées en UI)
    // -----------------------------------------------------------------------

    public function showReset(Request $r): void
    {
        $this->redirect('/auth?tab=phone&mode=reset');
    }

    public function sendReset(Request $r): void
    {
        $this->redirect('/auth?tab=phone&mode=reset');
    }

    public function showNewPassword(Request $r): void
    {
        $this->redirect('/auth');
    }

    public function setNewPassword(Request $r): void
    {
        $this->redirect('/auth');
    }

    // -----------------------------------------------------------------------
    // Déconnexion
    // -----------------------------------------------------------------------

    public function logout(Request $request): void
    {
        Csrf::check();
        Auth::logout();
        $this->redirect('/');
    }
}
