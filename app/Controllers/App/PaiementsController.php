<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Models\Enrollment;
use App\Models\OnlinePayment;
use App\Services\CinetPay;

class PaiementsController extends AppController
{
    public function index(Request $request): void
    {
        $userId  = Auth::id();
        $config  = CinetPay::getConfig();
        $enabled = CinetPay::isReady($config);

        // Inscriptions en attente de paiement (enrolled par cet utilisateur)
        $pending = [];
        if ($enabled) {
            $all     = Enrollment::forUser($userId);
            $pending = array_filter($all, fn($e) =>
                in_array($e['status'], [
                    'en_attente_paiement', 'paiement_partiel', 'confirmee',
                ], true)
                && ((float) $e['agreed_amount'] - (float) $e['paid_amount']) > 0
            );
            $pending = array_values($pending);
        }

        $history = OnlinePayment::forUser($userId);

        $this->view('app/paiements/index', [
            'pageTitle' => 'Payer en ligne — CaNDI',
            'enabled'   => $enabled,
            'pending'   => $pending,
            'history'   => $history,
        ]);
    }

    public function show(Request $request): void
    {
        $this->abort(404);
    }

    // -----------------------------------------------------------------------
    // Initiation CinetPay (POST /app/paiements/{id}/payer)
    // -----------------------------------------------------------------------

    public function initiate(Request $request): void
    {
        Csrf::check();

        $enrollId = $request->param('id', '');
        $userId   = Auth::id();

        // Charger l'inscription depuis DB (jamais depuis le navigateur)
        $enrollment = Enrollment::findWithDetails($enrollId);
        if (!$enrollment || $enrollment['user_id'] !== $userId) {
            $this->withError('Inscription introuvable.')->redirect('/app/paiements');
        }

        $due = (float) $enrollment['agreed_amount'] - (float) $enrollment['paid_amount'];
        if ($due <= 0) {
            $this->withError('Aucun montant à payer pour cette inscription.')->redirect('/app/paiements');
        }

        // Montant partiel optionnel — capé au solde réel côté serveur
        $requestedAmount = (float) $request->input('amount', 0);
        $amount = ($requestedAmount > 0 && $requestedAmount < $due) ? $requestedAmount : $due;

        $config = CinetPay::getConfig();
        if (!CinetPay::isReady($config)) {
            $this->withError('Le paiement en ligne n\'est pas encore activé.')->redirect('/app/paiements');
        }

        $orderId = 'ORD-' . strtoupper(substr(Auth::uuid(), 0, 12));
        $base    = rtrim(\App\Core\env('APP_URL', ''), '/');

        try {
            $result = CinetPay::createPayment($config, [
                'transaction_id' => $orderId,
                'amount'         => $amount,
                'description'    => 'Formation: ' . ($enrollment['program_title'] ?? $enrollId),
                'return_url'     => $base . '/app/paiement-retour?ref=' . urlencode($orderId),
                'notify_url'     => $base . '/api/cinetpay/notify',
            ]);
        } catch (\RuntimeException $e) {
            $this->withError($e->getMessage())->redirect('/app/paiements');
        }

        // Stocker en DB AVANT la redirection
        OnlinePayment::create([
            'order_id'    => $orderId,
            'user_id'     => $userId,
            'reference_id'=> $enrollId,
            'purpose'     => 'enrollment',
            'amount'      => $amount,
            'pay_token'   => $result['pay_token'],
            'payment_url' => $result['payment_url'],
        ]);

        // Rediriger l'utilisateur vers CinetPay
        header('Location: ' . $result['payment_url'], true, 302);
        exit;
    }

    // -----------------------------------------------------------------------
    // Page de retour (ne valide JAMAIS un paiement)
    // -----------------------------------------------------------------------

    public function returnUrl(Request $request): void
    {
        $ref = $request->get('ref', '');
        $this->view('app/paiements/retour', [
            'pageTitle' => 'Paiement en cours — CaNDI',
            'ref'       => $ref,
        ]);
    }
}
