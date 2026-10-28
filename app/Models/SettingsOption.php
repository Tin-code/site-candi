<?php

namespace App\Models;

use App\Core\Model;

class SettingsOption extends Model
{
    protected static string $table = 'settings_options';

    public static function allGrouped(): array
    {
        $rows   = static::query('SELECT * FROM settings_options ORDER BY category ASC, sort_order ASC, label ASC');
        $groups = [];
        foreach ($rows as $row) {
            $groups[$row['category']][] = $row;
        }
        return $groups;
    }

    public static function forCategory(string $category): array
    {
        return static::query(
            'SELECT * FROM settings_options WHERE category = ? ORDER BY sort_order ASC, label ASC',
            [$category]
        );
    }

    public static function createOption(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO settings_options (id, category, value, label, sort_order, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,1,?,?)'
        )->execute([
            $id,
            $data['category'],
            $data['value'],
            $data['label'],
            (int) ($data['sort_order'] ?? 99),
            $now,
            $now,
        ]);
        return $id;
    }

    public static function toggleActive(string $id, bool $active): void
    {
        static::db()->prepare(
            'UPDATE settings_options SET is_active=?, updated_at=? WHERE id=?'
        )->execute([(int) $active, date('Y-m-d H:i:s'), $id]);
    }
}
