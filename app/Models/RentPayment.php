<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class RentPayment extends Model
{
    protected static string $table = 'rent_payments';

    public static function listAll(array $filters = [], int $page = 1, int $perPage = 30): array
    {
        $pdo    = Database::getInstance();
        $where  = ['1=1'];
        $params = [];

        if (!empty($filters['status'])) {
            $where[]  = 'rp.status = ?';
            $params[] = $filters['status'];
        } else {
            $where[] = "rp.status != 'refusee'";
        }
        if (!empty($filters['month'])) {
            $where[]  = 'rp.period_month LIKE ?';
            $params[] = $filters['month'] . '%';
        }
        if (!empty($filters['date_from'])) {
            $where[]  = 'DATE(rp.paid_at) >= ?';
            $params[] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $where[]  = 'DATE(rp.paid_at) <= ?';
            $params[] = $filters['date_to'];
        }
        if (!empty($filters['q'])) {
            $like     = '%' . $filters['q'] . '%';
            $where[]  = '(l.tenant_name LIKE ? OR a.label LIKE ? OR rp.provider_reference LIKE ?)';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }
        if (!empty($filters['apartment_id'])) {
            $where[]  = 'rp.apartment_id = ?';
            $params[] = $filters['apartment_id'];
        }
        if (!empty($filters['owner_id'])) {
            $where[]  = '(a.owner_id = ? OR p.owner_id = ?)';
            $params[] = $filters['owner_id'];
            $params[] = $filters['owner_id'];
        }

        $cond = implode(' AND ', $where);

        $countStmt = $pdo->prepare(
            "SELECT COUNT(*) FROM rent_payments rp
             JOIN apartments a ON a.id = rp.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             LEFT JOIN leases l ON l.id = rp.lease_id
             WHERE $cond"
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $listParams = array_merge($params, [$perPage, ($page - 1) * $perPage]);
        $stmt = $pdo->prepare(
            "SELECT rp.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    p.name AS property_name,
                    l.tenant_name, l.tenant_phone, l.monthly_rent AS lease_monthly_rent
             FROM rent_payments rp
             JOIN apartments a ON a.id = rp.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             LEFT JOIN leases l ON l.id = rp.lease_id
             WHERE $cond
             ORDER BY rp.paid_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute($listParams);
        $rows = $stmt->fetchAll();

        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage];
    }

    public static function forLease(string $leaseId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT * FROM rent_payments WHERE lease_id = ? ORDER BY period_month DESC"
        );
        $stmt->execute([$leaseId]);
        return $stmt->fetchAll();
    }

    public static function pendingList(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT rp.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    p.name AS property_name,
                    l.tenant_name, l.tenant_phone
             FROM rent_payments rp
             JOIN apartments a ON a.id = rp.apartment_id
             LEFT JOIN properties p ON p.id = a.property_id
             LEFT JOIN leases l ON l.id = rp.lease_id
             WHERE rp.status = 'en_attente'
             ORDER BY rp.paid_at"
        )->fetchAll();
    }

    public static function findById(string $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT rp.*, a.label AS apartment_label, l.tenant_name
             FROM rent_payments rp
             JOIN apartments a ON a.id = rp.apartment_id
             LEFT JOIN leases l ON l.id = rp.lease_id
             WHERE rp.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function createPayment(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO rent_payments
             (id, lease_id, apartment_id, period_month, amount, currency,
              payment_method, payer_phone, provider_reference, paid_at,
              status, notes, account_id, declared_by, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $id,
            $data['lease_id'],
            $data['apartment_id'],
            $data['period_month'],
            (float) $data['amount'],
            $data['currency']           ?? 'GNF',
            $data['payment_method']     ?? 'especes',
            $data['payer_phone']        ?? null,
            $data['provider_reference'] ?? null,
            $data['paid_at']            ?? date('Y-m-d H:i:s'),
            $data['status']             ?? 'en_attente',
            $data['notes']              ?? null,
            $data['account_id']         ?? null,
            $data['declared_by']        ?? null,
        ]);
        return $id;
    }

    public static function approuver(string $id, string $validatedBy): void
    {
        Database::getInstance()->prepare(
            "UPDATE rent_payments SET status = 'validee', validated_by = ?, validated_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([$validatedBy, $id]);
    }

    public static function refuser(string $id): void
    {
        Database::getInstance()->prepare(
            "UPDATE rent_payments SET status = 'refusee', updated_at = NOW() WHERE id = ?"
        )->execute([$id]);
    }

    public static function validatedByLease(): array
    {
        $rows = Database::getInstance()->query(
            "SELECT lease_id,
                    DATE_FORMAT(period_month, '%Y-%m') AS month_key,
                    SUM(amount) AS total
             FROM rent_payments
             WHERE status = 'validee'
             GROUP BY lease_id, month_key"
        )->fetchAll();
        $map = [];
        foreach ($rows as $row) {
            $map[$row['lease_id']][$row['month_key']] = (float) $row['total'];
        }
        return $map;
    }

    public static function encaisseThisMonth(): float
    {
        $row = Database::getInstance()->query(
            "SELECT COALESCE(SUM(amount), 0) AS total
             FROM rent_payments
             WHERE status = 'validee'
               AND DATE_FORMAT(period_month, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m')"
        )->fetch();
        return (float) ($row['total'] ?? 0);
    }

    public static function deleteById(string $id): void
    {
        Database::getInstance()->prepare(
            "DELETE FROM rent_payments WHERE id = ? AND status != 'validee'"
        )->execute([$id]);
    }

    public static function updatePayment(string $id, array $data): void
    {
        $sets   = [];
        $params = [];
        if (isset($data['paid_at']))            { $sets[] = 'paid_at = ?';            $params[] = $data['paid_at']; }
        if (array_key_exists('provider_reference', $data)) { $sets[] = 'provider_reference = ?'; $params[] = $data['provider_reference']; }
        if (array_key_exists('notes', $data))   { $sets[] = 'notes = ?';              $params[] = $data['notes']; }
        if (empty($sets)) return;
        $sets[]   = 'updated_at = NOW()';
        $params[] = $id;
        Database::getInstance()->prepare(
            'UPDATE rent_payments SET ' . implode(', ', $sets) . ' WHERE id = ?'
        )->execute($params);
    }

    public static function stats(): array
    {
        $pdo = Database::getInstance();
        $row = $pdo->query(
            "SELECT
               SUM(CASE WHEN status = 'validee' THEN amount ELSE 0 END) AS total_valide,
               SUM(CASE WHEN status = 'en_attente' THEN amount ELSE 0 END) AS total_attente,
               COUNT(CASE WHEN status = 'en_attente' THEN 1 END) AS nb_attente
             FROM rent_payments
             WHERE period_month >= DATE_FORMAT(NOW(), '%Y-%m-01')"
        )->fetch();
        return $row ?: ['total_valide' => 0, 'total_attente' => 0, 'nb_attente' => 0];
    }
}
