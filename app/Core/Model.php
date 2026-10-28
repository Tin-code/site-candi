<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected static string $table = '';
    protected static string $primaryKey = 'id';

    protected static function db(): PDO
    {
        return Database::getInstance();
    }

    // -------------------------------------------------------------------------
    // Lecture
    // -------------------------------------------------------------------------

    public static function find(int|string $id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findOrFail(int|string $id): array
    {
        $row = static::find($id);
        if (!$row) {
            http_response_code(404);
            throw new \RuntimeException(static::$table . ' #' . $id . ' introuvable.');
        }
        return $row;
    }

    public static function all(string $orderBy = '', string $direction = 'ASC'): array
    {
        $sql = 'SELECT * FROM `' . static::$table . '`';
        if ($orderBy) {
            $sql .= ' ORDER BY `' . $orderBy . '` ' . ($direction === 'DESC' ? 'DESC' : 'ASC');
        }
        return static::db()->query($sql)->fetchAll();
    }

    public static function where(string $column, mixed $value, string $op = '='): array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` ' . $op . ' ?'
        );
        $stmt->execute([$value]);
        return $stmt->fetchAll();
    }

    public static function first(string $column, mixed $value): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM `' . static::$table . '` WHERE `' . $column . '` = ? LIMIT 1'
        );
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function count(string $where = '', array $params = []): int
    {
        $sql = 'SELECT COUNT(*) FROM `' . static::$table . '`';
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    public static function paginate(int $page, int $perPage = 20, string $where = '', array $params = [], string $orderBy = '', string $direction = 'ASC'): array
    {
        $offset = ($page - 1) * $perPage;
        $total  = static::count($where, $params);

        $sql = 'SELECT * FROM `' . static::$table . '`';
        if ($where) {
            $sql .= ' WHERE ' . $where;
        }
        if ($orderBy) {
            $sql .= ' ORDER BY `' . $orderBy . '` ' . ($direction === 'DESC' ? 'DESC' : 'ASC');
        }
        $sql .= ' LIMIT ? OFFSET ?';

        $stmt = static::db()->prepare($sql);
        $stmt->execute([...$params, $perPage, $offset]);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => max(1, (int) ceil($total / $perPage)),
        ];
    }

    // -------------------------------------------------------------------------
    // Écriture
    // -------------------------------------------------------------------------

    public static function create(array $data): int
    {
        $data['created_at'] = $data['created_at'] ?? date('Y-m-d H:i:s');
        $data['updated_at'] = $data['updated_at'] ?? date('Y-m-d H:i:s');

        $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $plhs = implode(', ', array_fill(0, count($data), '?'));

        $stmt = static::db()->prepare(
            'INSERT INTO `' . static::$table . "` ({$cols}) VALUES ({$plhs})"
        );
        $stmt->execute(array_values($data));
        return (int) static::db()->lastInsertId();
    }

    public static function update(int|string $id, array $data): bool
    {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $sets = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));

        $stmt = static::db()->prepare(
            'UPDATE `' . static::$table . "` SET {$sets} WHERE `" . static::$primaryKey . '` = ?'
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    public static function delete(int|string $id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM `' . static::$table . '` WHERE `' . static::$primaryKey . '` = ?'
        );
        return $stmt->execute([$id]);
    }

    // -------------------------------------------------------------------------
    // Requête brute (préparée uniquement)
    // -------------------------------------------------------------------------

    public static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function execute(string $sql, array $params = []): bool
    {
        $stmt = static::db()->prepare($sql);
        return $stmt->execute($params);
    }
}
