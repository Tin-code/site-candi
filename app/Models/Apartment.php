<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Apartment extends Model
{
    protected static string $table = 'apartments';

    public static function allWithLease(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT a.*,
                    p.name AS property_name, p.city AS property_city,
                    l.id AS lease_id, l.tenant_name, l.tenant_phone,
                    l.monthly_rent AS lease_rent, l.start_month,
                    l.status AS lease_status, l.access_code
             FROM apartments a
             LEFT JOIN properties p ON p.id = a.property_id
             LEFT JOIN leases l ON l.apartment_id = a.id AND l.status = 'actif'
             ORDER BY p.name, a.label"
        )->fetchAll();
    }

    public static function forProperty(string $propertyId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT id, label, code, status, monthly_rent, rooms FROM apartments WHERE property_id = ? ORDER BY label"
        );
        $stmt->execute([$propertyId]);
        return $stmt->fetchAll();
    }

    public static function forSelect(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT a.id, a.label, a.code, a.status, a.monthly_rent, p.name AS property_name
             FROM apartments a
             LEFT JOIN properties p ON p.id = a.property_id
             ORDER BY p.name, a.label"
        )->fetchAll();
    }

    /** Retourne uniquement les logements sans bail actif, avec leur loyer mensuel. */
    public static function freeForSelect(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT a.id, a.label, a.code, a.monthly_rent, p.name AS property_name
             FROM apartments a
             LEFT JOIN properties p ON p.id = a.property_id
             WHERE a.id NOT IN (
                 SELECT apartment_id FROM leases WHERE status = 'actif'
             )
             ORDER BY p.name, a.label"
        )->fetchAll();
    }

    public static function createApartment(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO apartments (id, label, code, property_id, owner_id, monthly_rent, currency, rooms, status, notes, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $id,
            $data['label'],
            $data['code']        ?? null,
            $data['property_id'] ?? null,
            $data['owner_id']    ?? null,
            (float) ($data['monthly_rent'] ?? 0),
            $data['currency']    ?? 'GNF',
            $data['rooms']       ?? null,
            $data['status']      ?? 'libre',
            $data['notes']       ?? null,
        ]);
        return $id;
    }

    public static function updateApartment(string $id, array $data): void
    {
        Database::getInstance()->prepare(
            'UPDATE apartments
             SET label = ?, code = ?, property_id = ?, owner_id = ?,
                 monthly_rent = ?, rooms = ?, status = ?, notes = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $data['label'],
            $data['code']        ?? null,
            $data['property_id'] ?? null,
            $data['owner_id']    ?? null,
            (float) ($data['monthly_rent'] ?? 0),
            $data['rooms']       ?? null,
            $data['status']      ?? 'libre',
            $data['notes']       ?? null,
            $id,
        ]);
    }

    public static function deleteApartment(string $id): void
    {
        Database::getInstance()->prepare(
            "DELETE FROM apartments WHERE id = ?
             AND id NOT IN (SELECT apartment_id FROM leases WHERE status = 'actif')"
        )->execute([$id]);
    }

    public static function updateStatus(string $id, string $status): void
    {
        Database::getInstance()->prepare(
            "UPDATE apartments SET status = ?, updated_at = NOW() WHERE id = ?"
        )->execute([$status, $id]);
    }
}
