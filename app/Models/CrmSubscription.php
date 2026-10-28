<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class CrmSubscription extends Model
{
    protected static string $table = 'crm_subscriptions';

    public static function allWithDetails(): array
    {
        return static::query(
            "SELECT s.*, c.full_name AS client_name, c.company_name, p.name AS product_name, p.monthly_price AS default_price
             FROM crm_subscriptions s
             LEFT JOIN clients c ON c.id = s.client_id
             LEFT JOIN crm_products p ON p.id = s.crm_id
             ORDER BY s.created_at DESC",
            []
        );
    }

    public static function findById(string $id): ?array
    {
        return static::queryOne("SELECT * FROM crm_subscriptions WHERE id = ? LIMIT 1", [$id]);
    }

    public static function createSub(array $data): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            "INSERT INTO crm_subscriptions (id, client_id, crm_id, monthly_price, start_month, status, notes, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $id,
            $data['client_id'],
            $data['crm_id'],
            (float) $data['monthly_price'],
            $data['start_month'],
            $data['status']     ?? 'actif',
            $data['notes']      ?? null,
            $data['created_by'] ?? null,
            $now, $now,
        ]);
        return $id;
    }

    public static function updateStatus(string $id, string $status): void
    {
        static::db()->prepare("UPDATE crm_subscriptions SET status=?, updated_at=? WHERE id=?")
            ->execute([$status, date('Y-m-d H:i:s'), $id]);
    }

    public static function delete(string|int $id): bool
    {
        static::db()->prepare("DELETE FROM crm_subscriptions WHERE id=?")->execute([$id]);
        return true;
    }

    public static function hasPayments(string $id): bool
    {
        $cnt = static::db()->prepare("SELECT COUNT(*) FROM crm_payments WHERE subscription_id=?");
        $cnt->execute([$id]);
        return (int) $cnt->fetchColumn() > 0;
    }
}
