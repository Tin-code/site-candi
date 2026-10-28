<?php

namespace App\Models;

use App\Core\Model;

class Post extends Model
{
    protected static string $table = 'posts';

    public static function recent(int $limit = 3): array
    {
        return static::query(
            'SELECT id, title, slug, excerpt, cover_url, category, published_at, created_at
             FROM posts
             WHERE status = "publie" AND published_at <= NOW()
             ORDER BY published_at DESC
             LIMIT ?',
            [$limit]
        );
    }

    public static function published(int $page = 1, int $perPage = 12): array
    {
        return static::paginate(
            $page,
            $perPage,
            'status = "publie" AND published_at <= NOW()',
            [],
            'published_at',
            'DESC'
        );
    }

    public static function findBySlug(string $slug): ?array
    {
        return static::first('slug', $slug);
    }

    public static function allAdmin(): array
    {
        return static::query(
            'SELECT p.id, p.slug, p.title, p.excerpt, p.category, p.status,
                    p.cover_url, p.video_url, p.published_at, p.created_at, p.updated_at, p.author_id,
                    CONCAT(pr.first_name, " ", pr.last_name) AS author_name
             FROM posts p
             LEFT JOIN profiles pr ON pr.id = p.author_id
             ORDER BY p.created_at DESC'
        );
    }

    public static function createAdmin(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO posts
             (id, slug, title, content, excerpt, category, cover_url, video_url,
              status, author_id, published_at, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $data['slug'],
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? null,
            $data['category'] ?? 'actualite',
            $data['cover_url'] ?? null,
            $data['video_url'] ?? null,
            $data['status'] ?? 'brouillon',
            $data['author_id'] ?? null,
            $data['published_at'] ?? null,
            $now,
            $now,
        ]);
        return $id;
    }

    public static function updateAdmin(string $id, array $data): void
    {
        static::db()->prepare(
            'UPDATE posts SET
             slug=?, title=?, content=?, excerpt=?, category=?, cover_url=?,
             video_url=?, status=?, published_at=?, updated_at=?
             WHERE id=?'
        )->execute([
            $data['slug'],
            $data['title'],
            $data['content'],
            $data['excerpt'] ?? null,
            $data['category'] ?? 'actualite',
            $data['cover_url'] ?? null,
            $data['video_url'] ?? null,
            $data['status'] ?? 'brouillon',
            $data['published_at'] ?? null,
            date('Y-m-d H:i:s'),
            $id,
        ]);
    }

    public static function slugify(string $value): string
    {
        $value = mb_strtolower($value, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');
        return mb_substr($value, 0, 80);
    }
}
