<?php

namespace App\Models;

use App\Core\Model;

class TrainingSession extends Model
{
    protected static string $table = 'training_sessions';

    public static function upcoming(int $limit = 5): array
    {
        return static::query(
            'SELECT s.id, s.start_date, s.end_date, s.location, s.modality,
                    s.capacity, s.seats_taken, s.status, s.program_id,
                    p.title AS program_title
             FROM training_sessions s
             LEFT JOIN training_programs p ON p.id = s.program_id
             WHERE s.start_date >= CURDATE()
               AND s.is_published = 1
             ORDER BY s.start_date ASC
             LIMIT ?',
            [$limit]
        );
    }

    public static function forProgram(string $programId): array
    {
        return static::query(
            'SELECT * FROM training_sessions
             WHERE program_id = ? AND start_date >= CURDATE() AND is_published = 1
             ORDER BY start_date ASC',
            [$programId]
        );
    }

    public static function allAdmin(?string $programId = null): array
    {
        if ($programId) {
            return static::query(
                'SELECT s.*, b.name AS branch_name FROM training_sessions s
                 LEFT JOIN branches b ON b.id = s.branch_id
                 WHERE s.program_id = ? AND s.archived_at IS NULL
                 ORDER BY s.start_date DESC',
                [$programId]
            );
        }
        return static::query(
            'SELECT s.*, p.title AS program_title, b.name AS branch_name
             FROM training_sessions s
             LEFT JOIN training_programs p ON p.id = s.program_id
             LEFT JOIN branches b ON b.id = s.branch_id
             WHERE s.archived_at IS NULL
             ORDER BY s.start_date DESC'
        );
    }

    public static function createAdmin(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $now = date('Y-m-d H:i:s');
        static::db()->prepare(
            'INSERT INTO training_sessions
             (id, code, program_id, branch_id, start_date, end_date, location,
              capacity, modality, status, is_published, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $id,
            $data['code'],
            $data['program_id'],
            $data['branch_id'] ?: null,
            $data['start_date'],
            $data['end_date'],
            $data['location'] ?? null,
            (int) ($data['capacity'] ?? 20),
            $data['modality'] ?? 'presentiel',
            $data['status'] ?? 'planifiee',
            (int) ($data['is_published'] ?? 0),
            $now,
            $now,
        ]);
        return $id;
    }

    public static function updateAdmin(string $id, array $data): void
    {
        static::db()->prepare(
            'UPDATE training_sessions SET
             code=?, branch_id=?, start_date=?, end_date=?, location=?,
             capacity=?, modality=?, status=?, is_published=?, updated_at=?
             WHERE id=?'
        )->execute([
            $data['code'],
            $data['branch_id'] ?: null,
            $data['start_date'],
            $data['end_date'],
            $data['location'] ?? null,
            (int) ($data['capacity'] ?? 20),
            $data['modality'] ?? 'presentiel',
            $data['status'] ?? 'planifiee',
            (int) ($data['is_published'] ?? 0),
            date('Y-m-d H:i:s'),
            $id,
        ]);
    }

    public static function archiveAdmin(string $id): void
    {
        static::db()->prepare(
            'UPDATE training_sessions SET archived_at=?, is_published=0, updated_at=? WHERE id=?'
        )->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s'), $id]);
    }
}
