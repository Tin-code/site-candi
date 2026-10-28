<?php

namespace App\Models;

use App\Core\Model;

class TrainingProgram extends Model
{
    protected static string $table = 'training_programs';

    public static function featured(int $limit = 4): array
    {
        return static::query(
            'SELECT p.id, p.title, p.summary, p.level, p.price, p.promo_price,
                    p.duration_label, p.total_hours, p.certificate_available,
                    p.category_id, c.name AS category_name
             FROM training_programs p
             LEFT JOIN training_categories c ON c.id = p.category_id
             WHERE p.is_published = 1
             ORDER BY p.created_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function catalogue(array $filters = []): array
    {
        $where  = ['p.is_published = 1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = '(p.title LIKE ? OR p.summary LIKE ?)';
            $params[] = '%' . $filters['search'] . '%';
            $params[] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['category'])) {
            $where[]  = 'p.category_id = ?';
            $params[] = $filters['category'];
        }

        if (!empty($filters['level'])) {
            $where[]  = 'p.level = ?';
            $params[] = $filters['level'];
        }

        $sql = 'SELECT p.id, p.title, p.summary, p.level, p.price, p.promo_price,
                       p.duration_label, p.total_hours, p.certificate_available,
                       p.description, p.category_id, c.name AS category_name
                FROM training_programs p
                LEFT JOIN training_categories c ON c.id = p.category_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY p.title ASC';

        return static::query($sql, $params);
    }

    public static function levels(): array
    {
        $rows = static::query(
            'SELECT DISTINCT level FROM training_programs WHERE is_published = 1 AND level IS NOT NULL ORDER BY level ASC'
        );
        return array_column($rows, 'level');
    }

    public static function allAdmin(): array
    {
        return static::query(
            'SELECT p.id, p.code, p.title, p.summary, p.level, p.price, p.promo_price,
                    p.duration_label, p.total_hours, p.is_published, p.category_id,
                    p.allow_installments, p.certificate_available, p.archived_at,
                    p.created_at, c.name AS category_name
             FROM training_programs p
             LEFT JOIN training_categories c ON c.id = p.category_id
             WHERE p.archived_at IS NULL
             ORDER BY p.title ASC'
        );
    }

    public static function createAdmin(array $data, string $createdBy): string
    {
        $id   = \App\Core\Auth::uuid();
        $now  = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO training_programs
             (id, code, title, summary, level, price, promo_price, duration_label,
              category_id, is_published, allow_installments, certificate_available,
              created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $data['code'],
            $data['title'],
            $data['summary'] ?? null,
            $data['level'] ?? 'debutant',
            (float) ($data['price'] ?? 0),
            !empty($data['promo_price']) ? (float) $data['promo_price'] : null,
            $data['duration_label'] ?? null,
            $data['category_id'] ?: null,
            (int) ($data['is_published'] ?? 0),
            (int) ($data['allow_installments'] ?? 0),
            (int) ($data['certificate_available'] ?? 0),
            $createdBy,
            $now,
            $now,
        ]);
        return $id;
    }

    public static function updateAdmin(string $id, array $data): void
    {
        static::db()->prepare(
            'UPDATE training_programs SET
             code=?, title=?, summary=?, level=?, price=?, promo_price=?,
             duration_label=?, category_id=?, is_published=?,
             allow_installments=?, certificate_available=?, updated_at=?
             WHERE id=?'
        )->execute([
            $data['code'],
            $data['title'],
            $data['summary'] ?? null,
            $data['level'] ?? 'debutant',
            (float) ($data['price'] ?? 0),
            !empty($data['promo_price']) ? (float) $data['promo_price'] : null,
            $data['duration_label'] ?? null,
            $data['category_id'] ?: null,
            (int) ($data['is_published'] ?? 0),
            (int) ($data['allow_installments'] ?? 0),
            (int) ($data['certificate_available'] ?? 0),
            date('Y-m-d H:i:s'),
            $id,
        ]);
    }

    public static function archiveAdmin(string $id): void
    {
        static::db()->prepare(
            'UPDATE training_programs SET archived_at=?, is_published=0, updated_at=? WHERE id=?'
        )->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }
}
