<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Model;

class CrmClient extends Model
{
    protected static string $table = 'clients';

    public static function allActive(): array
    {
        return static::query(
            "SELECT * FROM clients WHERE archived_at IS NULL AND status = 'actif' ORDER BY full_name",
            []
        );
    }

    public static function allAdmin(): array
    {
        return static::query(
            "SELECT * FROM clients WHERE archived_at IS NULL ORDER BY full_name",
            []
        );
    }

    public static function findById(string $id): ?array
    {
        return static::queryOne("SELECT * FROM clients WHERE id = ? LIMIT 1", [$id]);
    }

    public static function upsert(array $data, ?string $id = null): string
    {
        $now = date('Y-m-d H:i:s');
        if ($id) {
            static::db()->prepare(
                "UPDATE clients SET full_name=?, company_name=?, phone=?, email=?, city=?, client_type=?, updated_at=? WHERE id=?"
            )->execute([
                $data['full_name'], $data['company_name'] ?? null,
                $data['phone'] ?? null, $data['email'] ?? null,
                $data['city']  ?? null, $data['client_type'] ?? 'particulier',
                $now, $id,
            ]);
            return $id;
        }
        $newId = Auth::uuid();
        static::db()->prepare(
            "INSERT INTO clients (id, full_name, company_name, phone, email, city, client_type, status, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $newId,
            $data['full_name'], $data['company_name'] ?? null,
            $data['phone'] ?? null, $data['email'] ?? null,
            $data['city']  ?? null, $data['client_type'] ?? 'particulier',
            'actif',
            $data['created_by'] ?? null,
            $now, $now,
        ]);
        return $newId;
    }

    public static function delete(string|int $id): bool
    {
        static::db()->prepare("UPDATE clients SET archived_at=?, updated_at=? WHERE id=?")
            ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
        return true;
    }
}
