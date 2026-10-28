<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class FinancialAccount extends Model
{
    protected static string $table = 'financial_accounts';

    public static function allActive(): array
    {
        return static::query(
            'SELECT fa.*,
                    fa.opening_balance +
                    COALESCE(SUM(CASE WHEN t.direction="entree" AND t.status != "annulee" AND t.archived_at IS NULL THEN t.amount ELSE 0 END),0) -
                    COALESCE(SUM(CASE WHEN t.direction="sortie" AND t.status != "annulee" AND t.archived_at IS NULL THEN t.amount ELSE 0 END),0)
                    AS current_balance
             FROM financial_accounts fa
             LEFT JOIN transactions t ON t.account_id = fa.id
             WHERE fa.is_active = 1
             GROUP BY fa.id
             ORDER BY fa.name ASC'
        );
    }

    public static function allForSelect(): array
    {
        return static::query(
            'SELECT id, name, type, currency FROM financial_accounts WHERE is_active = 1 ORDER BY name ASC'
        );
    }

    public static function createAccount(array $data): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO financial_accounts (id, name, type, currency, opening_balance, notes, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,1,?,?)'
        )->execute([
            $id,
            $data['name'],
            $data['type'] ?? 'caisse',
            $data['currency'] ?? 'GNF',
            (float) ($data['opening_balance'] ?? 0),
            $data['notes'] ?? null,
            $now, $now,
        ]);
        return $id;
    }

    public static function globalStats(): array
    {
        $row = static::queryOne(
            'SELECT
               COALESCE(SUM(CASE WHEN t.direction="entree" AND t.status="validee" THEN t.amount ELSE 0 END),0) AS total_entrees,
               COALESCE(SUM(CASE WHEN t.direction="sortie" AND t.status="validee" THEN t.amount ELSE 0 END),0) AS total_sorties
             FROM transactions t
             LEFT JOIN financial_accounts fa ON fa.id = t.account_id
             WHERE t.archived_at IS NULL AND fa.is_active = 1'
        );
        return $row ?? ['total_entrees' => 0, 'total_sorties' => 0];
    }
}
