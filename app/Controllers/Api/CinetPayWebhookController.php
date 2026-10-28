<?php

namespace App\Controllers\Api;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Models\Notification;
use App\Models\OnlinePayment;
use App\Services\CinetPay;

class CinetPayWebhookController extends Controller
{
    public function handle(Request $request): void
    {
        // CinetPay envoie POST avec cpm_trans_id dans le body
        $body          = file_get_contents('php://input');
        $data          = json_decode($body ?: '', true);
        $transactionId = isset($data['cpm_trans_id']) ? trim((string) $data['cpm_trans_id']) : '';

        if ($transactionId === '' || strlen($transactionId) > 200) {
            http_response_code(400);
            echo 'Bad request';
            exit;
        }

        $payment = OnlinePayment::findByOrderId($transactionId);
        if (!$payment) {
            http_response_code(200); // ne pas exposer l'erreur
            echo 'ok';
            exit;
        }

        // Déjà traité — idempotent
        if ($payment['status'] !== 'initie') {
            http_response_code(200);
            echo 'ok';
            exit;
        }

        $config = CinetPay::getConfig();
        if (!CinetPay::isReady($config)) {
            http_response_code(200);
            echo 'ok';
            exit;
        }

        // Vérifier avec CinetPay (ne jamais faire confiance au body seul)
        $st = CinetPay::checkStatus($config, $transactionId);

        if ($st['status'] === 'SUCCESS') {
            $updated = OnlinePayment::updateStatus($payment['id'], 'reussi', $st['txnid'], $st['raw']);
            if ($updated) {
                $this->postSuccessActions($payment, $st['txnid']);
            }
        } elseif ($st['status'] === 'FAILED') {
            OnlinePayment::updateStatus($payment['id'], 'echoue', $st['txnid'], $st['raw']);
        }
        // PENDING → on ne fait rien, CinetPay retentera

        http_response_code(200);
        echo 'ok';
        exit;
    }

    // -----------------------------------------------------------------------

    private function postSuccessActions(array $payment, ?string $txnid): void
    {
        if ($payment['purpose'] !== 'enrollment') return;

        $enrollId = $payment['reference_id'];
        $amount   = (float) $payment['amount'];
        $ref      = $txnid ?? $payment['order_id'];

        // Mettre à jour paid_amount sur l'inscription
        Database::getInstance()->prepare(
            'UPDATE enrollments SET paid_amount = paid_amount + ?, updated_at = NOW() WHERE id = ?'
        )->execute([$amount, $enrollId]);

        // Notifier les rôles finance
        Notification::notifyRole(
            ['super_admin', 'admin', 'comptable'],
            'Paiement en ligne reçu',
            number_format($amount, 0, ',', ' ') . ' GNF via CinetPay (réf. ' . $ref . '). À valider en caisse.',
            'succes', 'inscriptions', '/app/finances'
        );

        // Notifier le payeur
        if (!empty($payment['user_id'])) {
            Notification::notifyUser(
                $payment['user_id'],
                'Paiement confirmé',
                'Votre paiement de ' . number_format($amount, 0, ',', ' ') . ' GNF a bien été reçu (réf. ' . $ref . '). Merci !',
                'succes', 'paiements', '/app/paiements'
            );
        }
    }
}
