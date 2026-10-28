<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Lease extends Model
{
    protected static string $table = 'leases';

    public static function allActive(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT l.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    p.name AS property_name
             FROM leases l
             JOIN apartments a ON a.id = l.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             WHERE l.status = 'actif'
             ORDER BY p.name, a.label"
        )->fetchAll();
    }

    public static function allLeases(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT l.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    p.name AS property_name
             FROM leases l
             JOIN apartments a ON a.id = l.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             ORDER BY l.status = 'actif' DESC, p.name, a.label"
        )->fetchAll();
    }

    public static function findByAccessCode(string $code): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT l.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    p.name AS property_name, p.city AS property_city
             FROM leases l
             JOIN apartments a ON a.id = l.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             WHERE l.access_code = ? LIMIT 1"
        );
        $stmt->execute([$code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByTenantUser(string $userId): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT l.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    a.monthly_rent AS apartment_rent,
                    p.name AS property_name, p.city AS property_city
             FROM leases l
             JOIN apartments a ON a.id = l.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             WHERE l.tenant_user_id = ? AND l.status = 'actif'
             ORDER BY l.created_at DESC LIMIT 1"
        );
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createLease(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO leases
             (id, apartment_id, tenant_name, tenant_phone, tenant_email,
              tenant_user_id, monthly_rent, currency, deposit,
              start_month, end_date, access_code, status, notes, created_by, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $id,
            $data['apartment_id'],
            $data['tenant_name'],
            $data['tenant_phone']   ?? null,
            $data['tenant_email']   ?? null,
            $data['tenant_user_id'] ?? null,
            (float) ($data['monthly_rent'] ?? 0),
            $data['currency']       ?? 'GNF',
            (float) ($data['deposit'] ?? 0),
            $data['start_month'],
            $data['end_date']       ?? null,
            $data['access_code'],
            $data['status']         ?? 'actif',
            $data['notes']          ?? null,
            $data['created_by']     ?? null,
        ]);
        // Mark apartment as occupied
        Apartment::updateStatus($data['apartment_id'], 'occupe');
        return $id;
    }

    public static function updateLease(string $id, array $data): void
    {
        Database::getInstance()->prepare(
            'UPDATE leases
             SET tenant_name = ?, tenant_phone = ?, tenant_email = ?,
                 monthly_rent = ?, deposit = ?, start_month = ?, end_date = ?,
                 access_code = ?, status = ?, notes = ?, updated_at = NOW()
             WHERE id = ?'
        )->execute([
            $data['tenant_name'],
            $data['tenant_phone']  ?? null,
            $data['tenant_email']  ?? null,
            (float) ($data['monthly_rent'] ?? 0),
            (float) ($data['deposit'] ?? 0),
            $data['start_month'],
            $data['end_date']      ?? null,
            $data['access_code'],
            $data['status']        ?? 'actif',
            $data['notes']         ?? null,
            $id,
        ]);
        if (isset($data['apartment_id'])) {
            $aptStatus = ($data['status'] ?? 'actif') === 'actif' ? 'occupe' : 'libre';
            Apartment::updateStatus($data['apartment_id'], $aptStatus);
        }
    }

    public static function deleteLease(string $id): void
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT apartment_id, status FROM leases WHERE id = ?");
        $stmt->execute([$id]);
        $lease = $stmt->fetch();
        $pdo->prepare("DELETE FROM leases WHERE id = ?")->execute([$id]);
        if ($lease && $lease['status'] === 'actif') {
            Apartment::updateStatus($lease['apartment_id'], 'libre');
        }
    }

    public static function linkTenantUser(string $leaseId, string $userId): void
    {
        Database::getInstance()->prepare(
            "UPDATE leases SET tenant_user_id = ?, updated_at = NOW() WHERE id = ?"
        )->execute([$userId, $leaseId]);
    }
}
