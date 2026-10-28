<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Task extends Model
{
    protected static string $table = 'tasks';

    public static function listAll(array $filters = []): array
    {
        $where  = ['t.archived_at IS NULL'];
        $params = [];

        if (!empty($filters['assigned_to'])) {
            $where[]  = 't.assigned_to = ?';
            $params[] = $filters['assigned_to'];
        }
        if (!empty($filters['status'])) {
            $where[]  = 't.status = ?';
            $params[] = $filters['status'];
        }

        $whereStr = implode(' AND ', $where);

        return static::query(
            "SELECT t.*,
                    CONCAT(COALESCE(p.first_name,''), ' ', COALESCE(p.last_name,'')) AS assigned_name
             FROM tasks t
             LEFT JOIN profiles p ON p.id = t.assigned_to
             WHERE $whereStr
             ORDER BY
               FIELD(t.priority,'urgente','haute','normale','basse'),
               t.due_date IS NULL,
               t.due_date ASC,
               t.created_at DESC",
            $params
        );
    }

    public static function findById(string $id): ?array
    {
        return static::queryOne(
            "SELECT t.*,
                    CONCAT(COALESCE(p.first_name,''), ' ', COALESCE(p.last_name,'')) AS assigned_name
             FROM tasks t
             LEFT JOIN profiles p ON p.id = t.assigned_to
             WHERE t.id = ? LIMIT 1",
            [$id]
        );
    }

    public static function createTask(array $data): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            "INSERT INTO tasks
             (id, title, description, module, priority, status, assigned_to, due_date, completed_at, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?)"
        )->execute([
            $id,
            $data['title'],
            $data['description'] ?? null,
            $data['module']      ?? null,
            $data['priority']    ?? 'normale',
            $data['status']      ?? 'a_faire',
            $data['assigned_to'] ?? null,
            $data['due_date']    ?? null,
            $data['status'] === 'terminee' ? $now : ($data['completed_at'] ?? null),
            $data['created_by']  ?? null,
            $now, $now,
        ]);
        return $id;
    }

    public static function updateTask(string $id, array $data): void
    {
        $now = date('Y-m-d H:i:s');

        $sets   = ['updated_at = ?'];
        $params = [$now];

        $allowed = ['title', 'description', 'module', 'priority', 'status', 'assigned_to', 'due_date', 'completed_at'];
        foreach ($allowed as $col) {
            if (array_key_exists($col, $data)) {
                $sets[]   = "$col = ?";
                $params[] = $data[$col];
            }
        }

        if (array_key_exists('status', $data) && $data['status'] === 'terminee' && !array_key_exists('completed_at', $data)) {
            $sets[]   = 'completed_at = ?';
            $params[] = $now;
        }

        $params[] = $id;
        static::db()->prepare('UPDATE tasks SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    }

    public static function deleteTask(string $id): void
    {
        static::db()->prepare('UPDATE tasks SET archived_at = ?, updated_at = ? WHERE id = ?')
            ->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }

    public static function staffUsers(): array
    {
        return static::query(
            "SELECT DISTINCT u.id,
                    TRIM(CONCAT(COALESCE(p.first_name,''), ' ', COALESCE(p.last_name,''))) AS name
             FROM users u
             JOIN user_roles ur ON ur.user_id = u.id AND ur.role != 'client'
             LEFT JOIN profiles p ON p.id = u.id
             WHERE u.is_active = 1
             ORDER BY name",
            []
        );
    }
}
