<?php

namespace App\Models;

use App\Core\Model;

class Branch extends Model
{
    protected static string $table = 'branches';

    public static function allActive(): array
    {
        return static::query(
            'SELECT id, name, city FROM branches WHERE is_active = 1 ORDER BY name ASC'
        );
    }
}
