<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class FinancialCategory extends Model
{
    protected static string $table = 'financial_categories';

    public static function allActive(): array
    {
        return static::query(
            'SELECT id, name, direction FROM financial_categories WHERE is_active = 1 ORDER BY direction, name ASC'
        );
    }

    public static function createCategory(string $name, string $direction): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO financial_categories (id, name, direction, is_active, created_at) VALUES (?,?,?,1,?)'
        )->execute([$id, $name, $direction, $now]);
        return $id;
    }
}
