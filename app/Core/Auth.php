<?php

namespace App\Core;

use PDO;

class Auth
{
    private const SESSION_KEY = '_auth_user';

    // -------------------------------------------------------------------------
    // Lecture de l'utilisateur courant
    // -------------------------------------------------------------------------

    public static function user(): ?array
    {
        Session::start();
        return Session::get(self::SESSION_KEY);
    }

    public static function id(): ?string
    {
        return self::user()['id'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function hasRole(string|array $roles): bool
    {
        $role = self::role();
        if ($role === null) return false;
        return in_array($role, (array) $roles, true);
    }

    // -------------------------------------------------------------------------
    // Connexion e-mail / téléphone + mot de passe
    // -------------------------------------------------------------------------

    public static function attempt(string $email, string $password): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT id, password_hash, is_active FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !$user['is_active']) return false;
        if (!password_verify($password, $user['password_hash'])) return false;

        self::loginById($user['id']);
        return true;
    }

    /**
     * Phone login — fetches all candidates matching by email alias or profiles.phone,
     * ordered so real-email accounts come before @tel.candi.gn duplicates,
     * then verifies the password against each in order.
     * This correctly handles staff users who have a real email + phone in profiles.
     */
    public static function attemptByPhone(string $normalizedDigits, string $password): bool
    {
        // $normalizedDigits must already be 12-digit (224XXXXXXXXX)
        $short = strlen($normalizedDigits) === 12 ? substr($normalizedDigits, 3) : $normalizedDigits;
        $pdo   = Database::getInstance();

        $stmt = $pdo->prepare(
            "SELECT u.id, u.password_hash FROM users u
             LEFT JOIN profiles p ON p.id = u.id
             WHERE (u.email = ? OR p.phone = ? OR p.phone = ?) AND u.is_active = 1
             ORDER BY (u.email LIKE '%@tel.candi.gn') ASC, u.created_at ASC"
        );
        $stmt->execute([$normalizedDigits . '@tel.candi.gn', $normalizedDigits, $short]);
        $candidates = $stmt->fetchAll();

        foreach ($candidates as $candidate) {
            if (password_verify($password, $candidate['password_hash'])) {
                self::loginById($candidate['id']);
                return true;
            }
        }
        return false;
    }

    // -------------------------------------------------------------------------
    // Inscription
    // -------------------------------------------------------------------------

    public static function register(array $data): string
    {
        $pdo  = Database::getInstance();
        $id   = self::uuid();
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

        $phone = $data['phone'] ?? null;
        if ($phone) {
            $phone = preg_replace('/\D/', '', $phone);
            if (strlen($phone) === 9) $phone = '224' . $phone;
        }

        $email = $data['email'] ?? self::phoneToEmail($phone ?? '');
        $emailVerified = (int) ($data['email_verified'] ?? 0);

        $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, email_verified, is_active, created_at, updated_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())'
        )->execute([$id, $email, $hash, $emailVerified]);

        $pdo->prepare(
            'INSERT INTO profiles (id, first_name, last_name, phone, email, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, NOW(), NOW())'
        )->execute([
            $id,
            $data['first_name'] ?? null,
            $data['last_name']  ?? null,
            $phone,
            $data['email'] ?? null,
        ]);

        $pdo->prepare(
            'INSERT INTO user_roles (id, user_id, role, created_at) VALUES (?, ?, "client", NOW())'
        )->execute([self::uuid(), $id]);

        self::loginById($id);
        return $id;
    }

    /**
     * Find an existing user by phone number or create one with default password "123456".
     * Does NOT log in the created/found user.
     * Sets $wasCreated = true when a new account is created.
     *
     * @return string  The user ID (existing or newly created)
     */
    public static function findOrCreateByPhone(
        string $rawPhone,
        string $displayName = '',
        bool  &$wasCreated  = false
    ): string {
        $phone = preg_replace('/\D/', '', $rawPhone);
        if (strlen($phone) === 9) $phone = '224' . $phone;
        if (strlen($phone) < 8) return '';

        $pdo   = Database::getInstance();
        $email = self::phoneToEmail($phone);
        $short = strlen($phone) === 12 ? substr($phone, 3) : $phone;

        // Look for existing account (by email alias or stored phone)
        $stmt = $pdo->prepare(
            "SELECT u.id FROM users u
             LEFT JOIN profiles p ON p.id = u.id
             WHERE u.email = ? OR p.phone = ? OR p.phone = ?
             LIMIT 1"
        );
        $stmt->execute([$email, $phone, $short]);
        $existing = $stmt->fetch();
        if ($existing) {
            $wasCreated = false;
            return $existing['id'];
        }

        // Create new account with default password 123456
        $id   = self::uuid();
        $hash = password_hash('12345678', PASSWORD_BCRYPT, ['cost' => 12]);

        $parts     = explode(' ', trim($displayName), 2);
        $firstName = trim($parts[0] ?? '') ?: null;
        $lastName  = isset($parts[1]) ? trim($parts[1]) : null;

        $pdo->prepare(
            'INSERT INTO users (id, email, password_hash, email_verified, is_active, created_at, updated_at)
             VALUES (?, ?, ?, 0, 1, NOW(), NOW())'
        )->execute([$id, $email, $hash]);

        $pdo->prepare(
            'INSERT INTO profiles (id, first_name, last_name, phone, created_at, updated_at)
             VALUES (?, ?, ?, ?, NOW(), NOW())'
        )->execute([$id, $firstName, $lastName, $phone]);

        $pdo->prepare(
            'INSERT INTO user_roles (id, user_id, role, created_at) VALUES (?, ?, "client", NOW())'
        )->execute([self::uuid(), $id]);

        $wasCreated = true;
        return $id;
    }

    // -------------------------------------------------------------------------
    // Déconnexion
    // -------------------------------------------------------------------------

    public static function logout(): void
    {
        Session::destroy();
    }

    // -------------------------------------------------------------------------
    // Charger un utilisateur en session depuis son ID
    // -------------------------------------------------------------------------

    public static function loginById(string $userId): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT u.id, u.email, u.email_verified, u.is_active,
                    p.first_name, p.last_name, p.phone, p.avatar_url,
                    COALESCE(ur.role, \'client\') AS role
             FROM users u
             LEFT JOIN profiles p ON p.id = u.id
             LEFT JOIN user_roles ur ON ur.user_id = u.id
             WHERE u.id = ?
             LIMIT 1'
        );
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            session_regenerate_id(true);
            Session::set(self::SESSION_KEY, $user);
        }
    }

    public static function refreshUser(): void
    {
        $id = self::id();
        if ($id) self::loginById($id);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    public static function phoneToEmail(string $digits): string
    {
        $normalized = preg_replace('/\D/', '', $digits);
        if (strlen($normalized) === 9) $normalized = '224' . $normalized;
        return $normalized . '@tel.candi.gn';
    }

    public static function uuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
