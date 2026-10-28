<?php

namespace App\Services;

use App\Core\Database;

class CinetPay
{
    private const API_BASE = 'https://api-checkout.cinetpay.com/v2';

    // -------------------------------------------------------------------------
    // Configuration depuis la DB
    // -------------------------------------------------------------------------

    public static function getConfig(): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM payment_gateway_settings WHERE provider = 'cinetpay' LIMIT 1");
        $stmt->execute();
        $row  = $stmt->fetch();
        if (!$row) return null;

        return [
            'is_enabled'  => (bool) $row['is_enabled'],
            'site_id'     => $row['client_id'] ?? null,
            'api_key'     => $row['client_secret'] ?? null,
            'currency'    => $row['currency'] ?? 'GNF',
            'environment' => $row['environment'] ?? 'sandbox',
        ];
    }

    public static function isReady(?array $config): bool
    {
        return $config !== null
            && $config['is_enabled']
            && !empty($config['site_id'])
            && !empty($config['api_key']);
    }

    // -------------------------------------------------------------------------
    // Initialisation d'un paiement
    // -------------------------------------------------------------------------

    public static function createPayment(array $config, array $params): array
    {
        $body = json_encode([
            'apikey'         => $config['api_key'],
            'site_id'        => $config['site_id'],
            'transaction_id' => $params['transaction_id'],
            'amount'         => (int) round($params['amount']),
            'currency'       => $config['currency'],
            'description'    => mb_substr($params['description'] ?? '', 0, 255),
            'return_url'     => $params['return_url'],
            'notify_url'     => $params['notify_url'],
            'channels'       => 'ALL',
            'lang'           => 'fr',
        ]);

        $ch = curl_init(self::API_BASE . '/payment');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 15,
        ]);
        $resp   = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($resp ?: '', true);

        if (($json['code'] ?? '') !== '201' || empty($json['data']['payment_url'])) {
            error_log('[CinetPay] createPayment error ' . $status . ': ' . $resp);
            throw new \RuntimeException(
                $json['message'] ?? 'CinetPay n\'a pas pu initialiser le paiement. Réessayez plus tard.'
            );
        }

        return [
            'payment_url' => $json['data']['payment_url'],
            'pay_token'   => $json['data']['payment_token'] ?? $params['transaction_id'],
        ];
    }

    // -------------------------------------------------------------------------
    // Vérification du statut (webhook)
    // -------------------------------------------------------------------------

    public static function checkStatus(array $config, string $transactionId): array
    {
        $body = json_encode([
            'apikey'         => $config['api_key'],
            'site_id'        => $config['site_id'],
            'transaction_id' => $transactionId,
        ]);

        $ch = curl_init(self::API_BASE . '/payment/check');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_TIMEOUT        => 10,
        ]);
        $resp = curl_exec($ch);
        curl_close($ch);

        $json   = json_decode($resp ?: '', true);
        $status = strtoupper((string) ($json['data']['status'] ?? ''));

        if ($status === 'ACCEPTED') {
            $normalized = 'SUCCESS';
        } elseif (in_array($status, ['REFUSED', 'CANCELLED', 'EXPIRED'], true)) {
            $normalized = 'FAILED';
        } else {
            $normalized = 'PENDING';
        }

        return [
            'status' => $normalized,
            'txnid'  => $transactionId,
            'raw'    => $json,
        ];
    }
}
