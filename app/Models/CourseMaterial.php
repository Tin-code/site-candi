<?php
namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class CourseMaterial extends Model
{
    protected static string $table = 'course_materials';

    public static function forPrograms(array $programIds): array
    {
        if (empty($programIds)) return [];
        $placeholders = implode(',', array_fill(0, count($programIds), '?'));
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare(
            "SELECT * FROM course_materials WHERE program_id IN ($placeholders) ORDER BY created_at ASC"
        );
        $stmt->execute($programIds);
        return $stmt->fetchAll();
    }

    public static function allAdmin(): array
    {
        $pdo = Database::getInstance();
        return $pdo->query(
            "SELECT cm.*, p.title AS program_title, p.code AS program_code,
                    s.code AS session_code, s.start_date AS session_date
             FROM course_materials cm
             LEFT JOIN training_programs p ON p.id = cm.program_id
             LEFT JOIN training_sessions s ON s.id = cm.session_id
             ORDER BY cm.created_at DESC"
        )->fetchAll();
    }

    public static function createMaterial(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        Database::getInstance()->prepare(
            'INSERT INTO course_materials (id, program_id, session_id, title, file_path, file_name, created_by, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
        )->execute([
            $id,
            $data['program_id'],
            $data['session_id'] ?? null,
            $data['title'],
            $data['file_path'],
            $data['file_name'] ?? null,
            $data['created_by'] ?? null,
        ]);
        return $id;
    }

    public static function findById(string $id): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM course_materials WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function deleteMaterial(string $id): void
    {
        Database::getInstance()->prepare("DELETE FROM course_materials WHERE id = ?")
            ->execute([$id]);
    }
}
