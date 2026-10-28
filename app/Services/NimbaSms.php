<?php

namespace App\Services;

use function App\Core\env;

class NimbaSms
{
    private static function config(): ?array
    {
        try {
            $pdo  = \App\Core\Database::getInstance();
            $stmt = $pdo->prepare("SELECT client_id, client_secret, merchant_key, api_base_url, is_enabled FROM payment_gateway_settings WHERE provider = 'nimba_sms' LIMIT 1");
            $stmt->execute();
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($row && $row['is_enabled'] && !empty($row['client_id']) && !empty($row['client_secret'])) {
                return [
                    'sid'      => $row['client_id'],
                    'token'    => $row['client_secret'],
                    'sender'   => $row['merchant_key'] ?: 'CaNDI',
                    'base_url' => rtrim($row['api_base_url'] ?: 'https://api.nimbasms.com', '/'),
                ];
            }
        } catch (\Throwable $e) { /* fallback to env */ }

        $sid   = env('NIMBA_SID', '');
        $token = env('NIMBA_SECRET_TOKEN', '');
        if ($sid && $token) {
            return [
                'sid'      => $sid,
                'token'    => $token,
                'sender'   => env('NIMBA_SENDER_NAME', 'CaNDI'),
                'base_url' => 'https://api.nimbasms.com',
            ];
        }
        return null;
    }

    private static function normalizePhone(string $phone): string
    {
        $p = preg_replace('/\D/', '', $phone);
        if (str_starts_with($p, '00')) $p = substr($p, 2);
        if (strlen($p) === 9) $p = '224' . $p;
        return $p;
    }

    public static function send(string $phone, string $message): bool
    {
        $cfg = self::config();

        if ($cfg === null) {
            error_log("[NimbaSms] Config manquante — TO:{$phone} MSG:{$message}");
            return true;
        }

        $auth    = base64_encode("{$cfg['sid']}:{$cfg['token']}");
        $payload = json_encode([
            'sender_name' => $cfg['sender'],
            'to'          => [self::normalizePhone($phone)],
            'message'     => $message,
        ]);

        $ch = curl_init("{$cfg['base_url']}/v1/messages");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Basic ' . $auth,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT    => 10,
        ]);
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            error_log("[NimbaSms] ERREUR HTTP {$status} — {$resp}");
            return false;
        }
        return true;
    }

    public static function sendOtp(string $phone, string $code): bool
    {
        return self::send(
            $phone,
            "CaNDI — votre code de vérification : {$code}. Valable 10 minutes."
        );
    }
}
