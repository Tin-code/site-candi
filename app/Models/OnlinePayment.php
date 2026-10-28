<?php

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

class OnlinePayment
{
    public static function create(array $data): string
    {
        $pdo = Database::getInstance();
        $id  = Auth::uuid();

        $pdo->prepare(
            'INSERT INTO online_payments
                (id, order_id, user_id, reference_id, purpose, provider,
                 amount, currency, status, pay_token, payment_url, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, "cinetpay", ?, "GNF", "initie", ?, ?, NOW(), NOW())'
        )->execute([
            $id,
            $data['order_id'],
            $data['user_id'],
            $data['reference_id'],
            $data['purpose'],
            $data['amount'],
            $data['pay_token']   ?? null,
            $data['payment_url'] ?? null,
        ]);

        return $id;
    }

    public static function findByOrderId(string $orderId): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare('SELECT * FROM online_payments WHERE order_id = ? LIMIT 1');
        $stmt->execute([$orderId]);
        return $stmt->fetch() ?: null;
    }

    public static function updateStatus(string $id, string $status, ?string $txnid, mixed $raw): bool
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'UPDATE online_payments
             SET status = ?, txnid = ?, raw_response = ?, updated_at = NOW()
             WHERE id = ? AND status = "initie"'
        );
        $stmt->execute([$status, $txnid, json_encode($raw), $id]);
        return $stmt->rowCount() > 0;
    }

    public static function forUser(string $userId): array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare(
            'SELECT op.*, e.enrollment_number, p.title AS program_title
             FROM online_payments op
             LEFT JOIN enrollments e ON e.id = op.reference_id AND op.purpose = "enrollment"
             LEFT JOIN training_programs p ON p.id = e.program_id
             WHERE op.user_id = ?
             ORDER BY op.created_at DESC
             LIMIT 50'
        );
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
