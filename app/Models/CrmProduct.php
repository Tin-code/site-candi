<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class CrmProduct extends Model
{
    protected static string $table = 'crm_products';

    public static function allActive(): array
    {
        return static::query("SELECT * FROM crm_products WHERE is_active = 1 ORDER BY name", []);
    }

    public static function allAdmin(): array
    {
        return static::query("SELECT * FROM crm_products ORDER BY name", []);
    }

    public static function findById(string $id): ?array
    {
        return static::queryOne("SELECT * FROM crm_products WHERE id = ? LIMIT 1", [$id]);
    }

    public static function upsert(array $data, ?string $id = null): string
    {
        $now = date('Y-m-d H:i:s');
        if ($id) {
            static::db()->prepare(
                "UPDATE crm_products SET code=?, name=?, description=?, monthly_price=?, is_active=?, updated_at=? WHERE id=?"
            )->execute([$data['code'], $data['name'], $data['description'] ?? null, (float)$data['monthly_price'], (int)$data['is_active'], $now, $id]);
            return $id;
        }
        $newId = Auth::uuid();
        static::db()->prepare(
            "INSERT INTO crm_products (id, code, name, description, monthly_price, is_active, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?)"
        )->execute([$newId, $data['code'], $data['name'], $data['description'] ?? null, (float)$data['monthly_price'], (int)($data['is_active'] ?? 1), $now, $now]);
        return $newId;
    }
}
