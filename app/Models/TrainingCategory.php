<?php

namespace App\Models;

use App\Core\Model;

class TrainingCategory extends Model
{
    protected static string $table = 'training_categories';

    public static function allActive(): array
    {
        return static::query(
            'SELECT id, name, slug FROM training_categories ORDER BY name ASC'
        );
    }
}
