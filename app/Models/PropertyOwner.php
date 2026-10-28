<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class PropertyOwner extends Model
{
    protected static string $table = 'property_owners';

    public static function allActive(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT * FROM property_owners WHERE is_active = 1 ORDER BY full_name"
        )->fetchAll();
    }

    public static function forSelect(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT id, full_name, phone FROM property_owners WHERE is_active = 1 ORDER BY full_name"
        )->fetchAll();
    }

    public static function updateOwner(string $id, array $data): void
    {
        Database::getInstance()->prepare(
            'UPDATE property_owners
             SET full_name = ?, owner_type = ?, phone = ?, email = ?,
                 address = ?, city = ?, commission_rate = ?, notes = ?,
                 is_active = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $data['full_name'],
            $data['owner_type']      ?? 'particulier',
            $data['phone']           ?? null,
            $data['email']           ?? null,
            $data['address']         ?? null,
            $data['city']            ?? null,
            (float) ($data['commission_rate'] ?? 0),
            $data['notes']           ?? null,
            isset($data['is_active']) ? (int) $data['is_active'] : 1,
            $id,
        ]);
    }

    public static function deleteOwner(string $id): void
    {
        Database::getInstance()->prepare(
            "UPDATE property_owners SET is_active = 0, updated_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public static function createOwner(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO property_owners
             (id, full_name, owner_type, phone, email, address, city, commission_rate, notes, is_active, created_by, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())'
        )->execute([
            $id,
            $data['full_name'],
            $data['owner_type']      ?? 'particulier',
            $data['phone']           ?? null,
            $data['email']           ?? null,
            $data['address']         ?? null,
            $data['city']            ?? null,
            (float) ($data['commission_rate'] ?? 0),
            $data['notes']           ?? null,
            $data['created_by']      ?? null,
        ]);
        return $id;
    }
}
