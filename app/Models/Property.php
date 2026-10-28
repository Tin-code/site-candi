<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Property extends Model
{
    protected static string $table = 'properties';

    public static function allActive(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT p.*,
                    po.full_name AS owner_name, po.phone AS owner_phone,
                    COUNT(a.id) AS apartment_count
             FROM properties p
             LEFT JOIN property_owners po ON po.id = p.owner_id
             LEFT JOIN apartments a ON a.property_id = p.id
             WHERE p.is_active = 1
             GROUP BY p.id
             ORDER BY p.name"
        )->fetchAll();
    }

    public static function forSelect(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT id, name, city FROM properties WHERE is_active = 1 ORDER BY name"
        )->fetchAll();
    }

    public static function updateProperty(string $id, array $data): void
    {
        Database::getInstance()->prepare(
            'UPDATE properties
             SET name = ?, address = ?, city = ?, notes = ?, owner_id = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $data['name'],
            $data['address']  ?? null,
            $data['city']     ?? null,
            $data['notes']    ?? null,
            $data['owner_id'] ?? null,
            $id,
        ]);
    }

    public static function deleteProperty(string $id): void
    {
        Database::getInstance()->prepare(
            "UPDATE properties SET is_active = 0, updated_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public static function createProperty(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO properties (id, name, address, city, notes, owner_id, is_active, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, 1, NOW())'
        )->execute([
            $id,
            $data['name'],
            $data['address'] ?? null,
            $data['city']    ?? null,
            $data['notes']   ?? null,
            $data['owner_id'] ?? null,
        ]);
        return $id;
    }
}
