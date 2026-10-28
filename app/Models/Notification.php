<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Model;

class Notification extends Model
{
    protected static string $table = 'notifications';

    public static function forUser(string $userId, int $limit = 50): array
    {
        return static::query(
            'SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?',
            [$userId, $limit]
        );
    }

    public static function unreadCount(string $userId): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    public static function markRead(string $id, string $userId): void
    {
        static::db()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?'
        )->execute([$id, $userId]);
    }

    public static function markAllRead(string $userId): void
    {
        static::db()->prepare(
            'UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0'
        )->execute([$userId]);
    }

    public static function createNotification(string $userId, string $title, ?string $body = null, string $level = 'info', ?string $module = null, ?string $link = null): string
    {
        $id  = Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO notifications (id, user_id, title, body, level, module, link, is_read, created_at)
             VALUES (?,?,?,?,?,?,?,0,?)'
        )->execute([$id, $userId, $title, $body, $level, $module, $link, $now]);
        return $id;
    }

    public static function deleteRead(string $userId): void
    {
        static::db()->prepare(
            'DELETE FROM notifications WHERE user_id = ? AND is_read = 1'
        )->execute([$userId]);
    }

    /**
     * Creates a notification for a single user (convenience wrapper).
     */
    public static function notifyUser(
        string $userId,
        string $title,
        ?string $body   = null,
        string $level   = 'info',
        ?string $module = null,
        ?string $link   = null
    ): void {
        if (!$userId) return;
        self::createNotification($userId, $title, $body, $level, $module, $link);
    }

    /**
     * Creates a notification for every user who holds at least one of the given roles.
     * Deduplicates so a super_admin with multiple roles only gets one notification.
     */
    public static function notifyRole(
        array $roles,
        string $title,
        ?string $body   = null,
        string $level   = 'info',
        ?string $module = null,
        ?string $link   = null,
        ?string $excludeUserId = null
    ): void {
        if (empty($roles)) return;
        $pdo   = Database::getInstance();
        $marks = implode(',', array_fill(0, count($roles), '?'));
        $stmt  = $pdo->prepare(
            "SELECT DISTINCT user_id FROM user_roles WHERE role IN ($marks)"
        );
        $stmt->execute($roles);
        foreach ($stmt->fetchAll() as $row) {
            $uid = $row['user_id'];
            if ($excludeUserId && $uid === $excludeUserId) continue;
            self::createNotification($uid, $title, $body, $level, $module, $link);
        }
    }
}
