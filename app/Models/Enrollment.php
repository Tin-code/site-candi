<?php

namespace App\Models;

use App\Core\Database;
use App\Core\Model;

class Enrollment extends Model
{
    protected static string $table = 'enrollments';

    public static function forUser(string $userId): array
    {
        return static::query(
            'SELECT e.id, e.enrollment_number, e.program_id, e.session_id,
                    e.status, e.agreed_amount, e.paid_amount, e.created_at,
                    p.title AS program_title, p.code AS program_code, p.total_hours,
                    s.id AS session_id_val, s.code AS session_code,
                    s.start_date, s.end_date, s.start_time, s.end_time,
                    s.location, s.room, s.meeting_url, s.modality
             FROM enrollments e
             LEFT JOIN training_programs p ON p.id = e.program_id
             LEFT JOIN training_sessions s ON s.id = e.session_id
             WHERE e.user_id = ?
             ORDER BY e.created_at DESC',
            [$userId]
        );
    }

    public static function findWithDetails(string $id): ?array
    {
        return static::queryOne(
            'SELECT e.*,
                    p.title AS program_title, p.code AS program_code,
                    p.price AS program_price, p.total_hours,
                    s.start_date AS session_start, s.end_date AS session_end,
                    s.start_time, s.end_time, s.location AS session_location,
                    s.room, s.meeting_url, s.modality AS session_modality,
                    pr.first_name, pr.last_name, pr.phone AS client_phone,
                    u.email AS user_email
             FROM enrollments e
             LEFT JOIN training_programs p ON p.id = e.program_id
             LEFT JOIN training_sessions s ON s.id = e.session_id
             LEFT JOIN profiles pr ON pr.id = e.user_id
             LEFT JOIN users u ON u.id = e.user_id
             WHERE e.id = ?
             LIMIT 1',
            [$id]
        );
    }

    public static function listAll(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $where  = ['1 = 1'];
        $params = [];

        if (!empty($filters['search'])) {
            $where[]  = '(e.enrollment_number LIKE ? OR p.title LIKE ? OR pr.first_name LIKE ? OR pr.last_name LIKE ?)';
            $s = '%' . $filters['search'] . '%';
            array_push($params, $s, $s, $s, $s);
        }
        if (!empty($filters['status'])) {
            $where[]  = 'e.status = ?';
            $params[] = $filters['status'];
        }

        $offset = ($page - 1) * $perPage;
        $sql = 'SELECT e.id, e.enrollment_number, e.status, e.agreed_amount, e.paid_amount, e.created_at,
                       p.title AS program_title,
                       pr.first_name, pr.last_name
                FROM enrollments e
                LEFT JOIN training_programs p ON p.id = e.program_id
                LEFT JOIN profiles pr ON pr.id = e.user_id
                WHERE ' . implode(' AND ', $where) . '
                ORDER BY e.created_at DESC
                LIMIT ? OFFSET ?';

        $params[] = $perPage;
        $params[] = $offset;

        $rows = static::query($sql, $params);

        // Count total
        $countSql = 'SELECT COUNT(*) FROM enrollments e
                     LEFT JOIN training_programs p ON p.id = e.program_id
                     LEFT JOIN profiles pr ON pr.id = e.user_id
                     WHERE ' . implode(' AND ', $where);
        array_pop($params); // remove offset
        array_pop($params); // remove limit
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();

        return [
            'data'       => $rows,
            'total'      => $total,
            'page'       => $page,
            'per_page'   => $perPage,
            'last_page'  => (int) ceil($total / $perPage),
        ];
    }

    public static function updateStatus(string $id, string $status, string $updatedBy): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE enrollments SET status = ?, updated_at = NOW() WHERE id = ?'
        );
        $stmt->execute([$status, $id]);

        if ($stmt->rowCount() > 0) {
            $pdo->prepare(
                'INSERT INTO enrollment_status_history (id, enrollment_id, new_status, changed_by)
                 VALUES (?, ?, ?, ?)'
            )->execute([\App\Core\Auth::uuid(), $id, $status, $updatedBy]);
            return true;
        }
        return false;
    }

    public static function pendingCount(): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT COUNT(*) FROM enrollments
             WHERE status IN ("nouvelle_demande","a_contacter","contacte","en_attente_paiement","paiement_partiel")'
        );
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function confirmedCount(): int
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM enrollments WHERE status IN ("confirmee","en_cours")');
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    public static function createAdmin(array $data): string
    {
        $id  = \App\Core\Auth::uuid();
        $now = date('Y-m-d H:i:s');
        $num = 'INS-' . date('Ymd') . '-' . strtoupper(substr(str_replace('-', '', $id), 0, 6));

        static::db()->prepare(
            'INSERT INTO enrollments
             (id, enrollment_number, program_id, session_id, user_id, status,
              agreed_amount, paid_amount, discount, currency, payment_mode,
              notes, created_by, created_at, updated_at)
             VALUES (?,?,?,?,?,?,?,0,0,"GNF",?,?,?,?,?)'
        )->execute([
            $id, $num,
            $data['program_id'],
            !empty($data['session_id']) ? $data['session_id'] : null,
            !empty($data['user_id'])    ? $data['user_id']    : null,
            $data['status']       ?? 'nouvelle_demande',
            (float) ($data['agreed_amount'] ?? 0),
            $data['payment_mode'] ?? 'comptant',
            $data['notes']        ?? null,
            $data['created_by'],
            $now, $now,
        ]);

        static::db()->prepare(
            'INSERT INTO enrollment_status_history (id, enrollment_id, new_status, changed_by)
             VALUES (?,?,?,?)'
        )->execute([\App\Core\Auth::uuid(), $id, $data['status'] ?? 'nouvelle_demande', $data['created_by']]);

        return $id;
    }

    public static function updateDetails(string $id, array $data, string $updatedBy): void
    {
        $pdo  = Database::getInstance();
        $sets = ['updated_at = NOW()'];
        $params = [];

        if (isset($data['agreed_amount']))  { $sets[] = 'agreed_amount = ?';    $params[] = (float) $data['agreed_amount']; }
        if (isset($data['discount']))       { $sets[] = 'discount = ?';         $params[] = (float) $data['discount']; }
        if (array_key_exists('contact_notes', $data)) { $sets[] = 'contact_notes = ?'; $params[] = $data['contact_notes'] ?: null; }
        if (array_key_exists('notes', $data))         { $sets[] = 'notes = ?';         $params[] = $data['notes'] ?: null; }
        if (array_key_exists('next_followup_at', $data)) {
            $sets[]   = 'next_followup_at = ?';
            $params[] = $data['next_followup_at'] ? $data['next_followup_at'] . ' 00:00:00' : null;
        }
        if (!empty($data['status'])) {
            $sets[]   = 'status = ?';
            $params[] = $data['status'];
            if (!empty($data['status']) && $data['status'] === 'confirmee') {
                $sets[] = 'confirmed_at = NOW()';
            }
            $pdo->prepare(
                'INSERT INTO enrollment_status_history (id, enrollment_id, new_status, changed_by)
                 VALUES (?, ?, ?, ?)'
            )->execute([\App\Core\Auth::uuid(), $id, $data['status'], $updatedBy]);
        }
        if (!empty($data['last_contact_at'])) {
            $sets[]   = 'last_contact_at = NOW()';
        }

        if (count($params) === 0) return;
        $params[] = $id;
        $pdo->prepare('UPDATE enrollments SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    }

    public static function openForCashier(): array
    {
        return static::query(
            "SELECT e.id, e.enrollment_number, e.status, e.agreed_amount, e.paid_amount, e.discount, e.user_id,
                    tp.title AS program_title,
                    p.first_name, p.last_name, p.phone
             FROM enrollments e
             LEFT JOIN training_programs tp ON tp.id = e.program_id
             LEFT JOIN profiles p ON p.id = e.user_id
             WHERE e.status IN ('nouvelle_demande','a_contacter','contacte',
                               'en_attente_documents','en_attente_paiement','paiement_partiel')
               AND e.archived_at IS NULL
             ORDER BY e.created_at DESC
             LIMIT 200"
        );
    }

    public static function addPayment(string $id, float $amount, float $discount = 0): void
    {
        $pdo  = Database::getInstance();
        $row  = $pdo->prepare('SELECT agreed_amount, paid_amount, discount FROM enrollments WHERE id = ? LIMIT 1');
        $row->execute([$id]);
        $enr  = $row->fetch();
        if (!$enr) return;

        $newPaid     = (float) $enr['paid_amount'] + $amount;
        $newDiscount = max((float) $enr['discount'], $discount);
        $agreed      = (float) $enr['agreed_amount'];
        $newStatus   = ($newPaid + $newDiscount) >= $agreed ? 'confirmee' : 'paiement_partiel';

        $pdo->prepare(
            'UPDATE enrollments SET paid_amount = ?, discount = ?, status = ?,
             confirmed_at = IF(? = "confirmee", NOW(), confirmed_at), updated_at = NOW()
             WHERE id = ?'
        )->execute([$newPaid, $newDiscount, $newStatus, $newStatus, $id]);
    }

    public static function clientsForSelect(): array
    {
        return static::query(
            'SELECT u.id, u.email, p.first_name, p.last_name, p.phone
             FROM users u
             LEFT JOIN profiles p ON p.id = u.id
             JOIN user_roles ur ON ur.user_id = u.id AND ur.role = "client"
             WHERE u.is_active = 1
             ORDER BY p.last_name, p.first_name'
        );
    }
}
