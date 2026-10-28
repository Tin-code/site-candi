<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Transaction extends Model
{
    protected static string $table = 'transactions';

    public static function listAll(array $filters = [], int $page = 1, int $perPage = 25): array
    {
        $where  = ['t.archived_at IS NULL'];
        $params = [];

        if (!empty($filters['direction'])) {
            $where[]  = 't.direction = ?';
            $params[] = $filters['direction'];
        }
        if (!empty($filters['account_id'])) {
            $where[]  = 't.account_id = ?';
            $params[] = $filters['account_id'];
        }
        if (!empty($filters['category_id'])) {
            $where[]  = 't.category_id = ?';
            $params[] = $filters['category_id'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 't.status = ?';
            $params[] = $filters['status'];
        }
        if (!empty($filters['search'])) {
            $where[]  = '(t.description LIKE ? OR t.third_party LIKE ? OR t.reference LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            array_push($params, $s, $s, $s);
        }
        if (!empty($filters['month'])) {
            $where[]  = 't.tx_date LIKE ?';
            $params[] = $filters['month'] . '%';
        }

        $offset   = ($page - 1) * $perPage;
        $whereStr = implode(' AND ', $where);

        $sql = "SELECT t.*, fa.name AS account_name, fc.name AS category_name
                FROM transactions t
                LEFT JOIN financial_accounts fa ON fa.id = t.account_id
                LEFT JOIN financial_categories fc ON fc.id = t.category_id
                WHERE $whereStr
                ORDER BY t.tx_date DESC, t.created_at DESC
                LIMIT ? OFFSET ?";

        $rows = static::query($sql, array_merge($params, [$perPage, $offset]));

        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM transactions t WHERE $whereStr");
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        return [
            'data'      => $rows,
            'total'     => $total,
            'page'      => $page,
            'per_page'  => $perPage,
            'last_page' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public static function findAdmin(string $id): ?array
    {
        return static::queryOne(
            'SELECT t.*, fa.name AS account_name, fc.name AS category_name
             FROM transactions t
             LEFT JOIN financial_accounts fa ON fa.id = t.account_id
             LEFT JOIN financial_categories fc ON fc.id = t.category_id
             WHERE t.id = ? LIMIT 1',
            [$id]
        );
    }

    public static function createAdmin(array $data): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO transactions
             (id, account_id, direction, amount, currency, tx_date, payment_method,
              category_id, description, third_party, reference, notes, status, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $data['account_id'],
            $data['direction'],
            (float) $data['amount'],
            $data['currency']       ?? 'GNF',
            $data['tx_date'],
            $data['payment_method'] ?? 'especes',
            !empty($data['category_id']) ? $data['category_id'] : null,
            $data['description']    ?? null,
            $data['third_party']    ?? null,
            $data['reference']      ?? null,
            $data['notes']          ?? null,
            $data['status']         ?? 'soumise',
            $data['created_by'],
            $now, $now,
        ]);
        return $id;
    }

    public static function updateAdmin(string $id, array $data): void
    {
        static::db()->prepare(
            'UPDATE transactions SET
             account_id=?, direction=?, amount=?, tx_date=?, payment_method=?,
             category_id=?, description=?, third_party=?, reference=?, notes=?, updated_at=?
             WHERE id=?'
        )->execute([
            $data['account_id'],
            $data['direction'],
            (float) $data['amount'],
            $data['tx_date'],
            $data['payment_method'] ?? 'especes',
            !empty($data['category_id']) ? $data['category_id'] : null,
            $data['description']    ?? null,
            $data['third_party']    ?? null,
            $data['reference']      ?? null,
            $data['notes']          ?? null,
            date('Y-m-d H:i:s'),
            $id,
        ]);
    }

    public static function validate(string $id, string $validatedBy): void
    {
        static::db()->prepare(
            'UPDATE transactions SET status="validee", updated_at=? WHERE id=? AND status != "annulee"'
        )->execute([date('Y-m-d H:i:s'), $id]);
    }

    public static function cancel(string $id): void
    {
        static::db()->prepare(
            'UPDATE transactions SET status="annulee", updated_at=? WHERE id=?'
        )->execute([date('Y-m-d H:i:s'), $id]);
    }

    public static function archive(string $id): void
    {
        static::db()->prepare(
            'UPDATE transactions SET archived_at=?, updated_at=? WHERE id=?'
        )->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }

    public static function createForEnrollment(array $data): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO transactions
             (id, account_id, direction, amount, currency, tx_date, payment_method,
              category_id, enrollment_id, client_id, description, reference, notes,
              status, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $data['account_id'],
            'entree',
            (float) $data['amount'],
            $data['currency']        ?? 'GNF',
            $data['tx_date']         ?? date('Y-m-d'),
            $data['payment_method']  ?? 'especes',
            $data['category_id']     ?? null,
            $data['enrollment_id']   ?? null,
            $data['client_id']       ?? null,
            $data['description']     ?? null,
            $data['reference']       ?? null,
            $data['notes']           ?? null,
            'validee',
            $data['created_by'],
            $now, $now,
        ]);
        return $id;
    }
}
