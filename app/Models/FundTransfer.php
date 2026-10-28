<?php
namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class FundTransfer extends Model
{
    protected static string $table = 'fund_transfers';

    public static function listRecent(int $limit = 50): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT ft.*,
                    fa.name AS from_account_name,
                    ta.name AS to_account_name,
                    CONCAT(COALESCE(pr.first_name,''), ' ', COALESCE(pr.last_name,'')) AS requested_by_name
             FROM fund_transfers ft
             LEFT JOIN financial_accounts fa ON fa.id = ft.from_account_id
             LEFT JOIN financial_accounts ta ON ta.id = ft.to_account_id
             LEFT JOIN profiles pr ON pr.id = ft.requested_by
             ORDER BY ft.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    public static function findById(string $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT ft.*,
                    fa.name AS from_account_name,
                    ta.name AS to_account_name
             FROM fund_transfers ft
             LEFT JOIN financial_accounts fa ON fa.id = ft.from_account_id
             LEFT JOIN financial_accounts ta ON ta.id = ft.to_account_id
             WHERE ft.id = ?
             LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function createTransfer(array $data): string
    {
        $id  = Auth::uuid();
        $ref = 'TRF-' . strtoupper(substr(str_replace('-', '', $id), 0, 8));
        $pdo = Database::getInstance();
        $pdo->prepare(
            'INSERT INTO fund_transfers
             (id, from_account_id, to_account_id, amount, currency, reference, note, status, requested_by, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
        )->execute([
            $id,
            $data['from_account_id'],
            $data['to_account_id'],
            (float) $data['amount'],
            $data['currency']  ?? 'GNF',
            $data['reference'] ?? $ref,
            $data['note']      ?? null,
            'en_attente',
            $data['requested_by'] ?? null,
        ]);
        return $id;
    }

    /**
     * Validates a transfer and creates the matching accounting entries (sortie + entrée).
     * Runs inside a DB transaction to keep fund_transfers and transactions in sync.
     */
    public static function approve(string $id, string $validatedBy): void
    {
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare(
            "SELECT ft.*, fa.name AS from_account_name, ta.name AS to_account_name
             FROM fund_transfers ft
             LEFT JOIN financial_accounts fa ON fa.id = ft.from_account_id
             LEFT JOIN financial_accounts ta ON ta.id = ft.to_account_id
             WHERE ft.id = ? AND ft.status = 'en_attente'
             LIMIT 1"
        );
        $stmt->execute([$id]);
        $t = $stmt->fetch();
        if (!$t) return;

        $pdo->beginTransaction();
        try {
            // 1. Mark transfer validated
            $pdo->prepare(
                "UPDATE fund_transfers
                 SET status = 'valide', validated_by = ?, validated_at = NOW(), updated_at = NOW()
                 WHERE id = ?"
            )->execute([$validatedBy, $id]);

            $now   = date('Y-m-d H:i:s');
            $today = date('Y-m-d');
            $ref   = $t['reference'] ?? ('TRF-' . strtoupper(substr($id, 0, 8)));
            $cur   = $t['currency'] ?? 'GNF';
            $amt   = (float) $t['amount'];

            // 2. Sortie from source account
            $pdo->prepare(
                'INSERT INTO transactions
                 (id, account_id, direction, amount, currency, tx_date, payment_method,
                  description, reference, status, created_by, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                Auth::uuid(),
                $t['from_account_id'],
                'sortie',
                $amt, $cur, $today, 'virement',
                'Transfert vers ' . ($t['to_account_name'] ?? 'compte destinataire'),
                $ref, 'validee', $validatedBy, $now, $now,
            ]);

            // 3. Entrée on destination account
            $pdo->prepare(
                'INSERT INTO transactions
                 (id, account_id, direction, amount, currency, tx_date, payment_method,
                  description, reference, status, created_by, created_at, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                Auth::uuid(),
                $t['to_account_id'],
                'entree',
                $amt, $cur, $today, 'virement',
                'Transfert depuis ' . ($t['from_account_name'] ?? 'compte source'),
                $ref, 'validee', $validatedBy, $now, $now,
            ]);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function refuse(string $id, string $validatedBy): void
    {
        Database::getInstance()->prepare(
            "UPDATE fund_transfers
             SET status = 'refuse', validated_by = ?, validated_at = NOW(), updated_at = NOW()
             WHERE id = ? AND status = 'en_attente'"
        )->execute([$validatedBy, $id]);
    }
}
