<?php

namespace App\Models;

use App\Core\Model;

class AppSetting extends Model
{
    protected static string $table = 'app_settings';
    protected static string $primaryKey = 'key';

    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::first('key', $key);
        if (!$row) return $default;

        $value = $row['value'] ?? null;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }
        return $value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE);
        $pdo  = static::db();
        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (`key`, value, updated_at)
             VALUES (?, ?, NOW())
             ON DUPLICATE KEY UPDATE value = VALUES(value), updated_at = NOW()'
        );
        $stmt->execute([$key, $json]);
    }
}
