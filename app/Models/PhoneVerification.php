<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class PhoneVerification
{
    public static function create(string $phone, string $code, string $purpose): string
    {
        $pdo = Database::getInstance();
        $id  = Auth::uuid();
        $pdo->prepare(
            'INSERT INTO phone_verifications
                (id, phone, code, purpose, attempts, expires_at, created_at, updated_at)
             VALUES (?, ?, ?, ?, 0, DATE_ADD(NOW(), INTERVAL 10 MINUTE), NOW(), NOW())'
        )->execute([$id, $phone, $code, $purpose]);
        return $id;
    }

    public static function findValid(string $phone, string $purpose): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT * FROM phone_verifications
             WHERE phone = ? AND purpose = ? AND consumed_at IS NULL AND expires_at > NOW()
             ORDER BY created_at DESC
             LIMIT 1'
        );
        $stmt->execute([$phone, $purpose]);
        return $stmt->fetch() ?: null;
    }

    public static function incrementAttempts(string $id): void
    {
        Database::getInstance()->prepare(
            'UPDATE phone_verifications
             SET attempts = attempts + 1, updated_at = NOW()
             WHERE id = ?'
        )->execute([$id]);
    }

    public static function consume(string $id): void
    {
        Database::getInstance()->prepare(
            'UPDATE phone_verifications
             SET consumed_at = NOW(), updated_at = NOW()
             WHERE id = ?'
        )->execute([$id]);
    }
}
