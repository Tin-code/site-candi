<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class CrmPayment extends Model
{
    protected static string $table = 'crm_payments';

    public static function allWithDetails(): array
    {
        return static::query(
            "SELECT cp.*, c.full_name AS client_name, p.name AS product_name
             FROM crm_payments cp
             LEFT JOIN clients c ON c.id = cp.client_id
             LEFT JOIN crm_products p ON p.id = cp.crm_id
             ORDER BY cp.paid_at DESC",
            []
        );
    }

    public static function forSubscription(string $subId): array
    {
        return static::query(
            "SELECT * FROM crm_payments WHERE subscription_id=? ORDER BY period_month",
            [$subId]
        );
    }

    /**
     * Create one or more monthly payments + a single accounting transaction.
     * $months = array of period_month strings (YYYY-MM-01).
     */
    public static function createBatch(array $data, array $months): void
    {
        $pdo     = Database::getInstance();
        $now     = date('Y-m-d H:i:s');
        $paidAt  = $data['paid_at'] ?? date('Y-m-d');
        $total   = (float) $data['amount'];
        $perMonth = count($months) > 0 ? $total / count($months) : $total;

        $pdo->beginTransaction();
        try {
            foreach ($months as $month) {
                $id = Auth::uuid();
                $pdo->prepare(
                    "INSERT INTO crm_payments
                     (id, client_id, crm_id, subscription_id, amount, currency, payment_method, period_month, paid_at, notes, account_id, created_by, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([
                    $id,
                    $data['client_id'],
                    $data['crm_id'],
                    $data['subscription_id'],
                    $perMonth,
                    $data['currency']       ?? 'GNF',
                    $data['payment_method'] ?? 'especes',
                    $month,
                    $paidAt,
                    $data['notes']          ?? null,
                    $data['account_id']     ?? null,
                    $data['created_by']     ?? null,
                    $now, $now,
                ]);
            }

            // Accounting transaction
            if (!empty($data['account_id'])) {
                $txId = Auth::uuid();
                $pdo->prepare(
                    "INSERT INTO transactions
                     (id, account_id, direction, amount, currency, tx_date, payment_method, description, reference, status, created_by, created_at, updated_at)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)"
                )->execute([
                    $txId,
                    $data['account_id'],
                    'entree',
                    $total,
                    $data['currency']       ?? 'GNF',
                    $paidAt,
                    $data['payment_method'] ?? 'especes',
                    'Abonnement CRM : ' . ($data['product_name'] ?? '') . ' — ' . ($data['client_name'] ?? ''),
                    'CRM-' . strtoupper(substr(Auth::uuid(), 0, 8)),
                    'validee',
                    $data['created_by']     ?? null,
                    $now, $now,
                ]);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
