<?php

namespace App\Models;

use App\Core\Model;

class Offering extends Model
{
    protected static string $table = 'offerings';

    public static function allPublished(): array
    {
        $rows = static::query(
            'SELECT * FROM offerings WHERE is_published = 1 ORDER BY sort_order ASC, created_at ASC'
        );

        // Décode le champ features (JSON array)
        foreach ($rows as &$row) {
            if (isset($row['features']) && is_string($row['features'])) {
                $row['features'] = json_decode($row['features'], true) ?? [];
            } else {
                $row['features'] = [];
            }
        }

        return $rows;
    }
}
