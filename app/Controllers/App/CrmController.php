<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Models\CrmClient;
use App\Models\CrmPayment;
use App\Models\CrmProduct;
use App\Models\CrmSubscription;
use App\Models\FinancialAccount;

class CrmController extends AppController
{
    private function requireAccess(): void
    {
        if (!Auth::hasRole(['super_admin', 'admin', 'commercial', 'comptable'])) {
            $this->abort(403, 'Accès CRM réservé au personnel.');
        }
    }

    private function canManageCatalog(): bool
    {
        return Auth::hasRole(['super_admin', 'admin']);
    }

    private function canManageSubs(): bool
    {
        return Auth::hasRole(['super_admin', 'admin', 'commercial']);
    }

    private function canPay(): bool
    {
        return Auth::hasRole(['super_admin', 'admin', 'comptable']);
    }

    public function index(Request $r): void
    {
        $this->requireAccess();

        $products = CrmProduct::allAdmin();
        $clients  = CrmClient::allAdmin();
        $subs     = CrmSubscription::allWithDetails();
        $payments = CrmPayment::allWithDetails();
        $accounts = FinancialAccount::allForSelect();

        $nowMonth = date('Y-m-01');

        // Compute debt/advance per subscription
        $paymentsBySub = [];
        foreach ($payments as $p) {
            $paymentsBySub[$p['subscription_id']][] = $p;
        }

        $rows = [];
        foreach ($subs as $sub) {
            $paid     = $paymentsBySub[$sub['id']] ?? [];
            $paidCnt  = count($paid);
            $paidAmt  = array_sum(array_column($paid, 'amount'));

            $elapsed = 0;
            if ($sub['status'] === 'actif' && $sub['start_month']) {
                $start  = new \DateTime(substr($sub['start_month'], 0, 10));
                $now    = new \DateTime(date('Y-m-01'));
                $diff   = $now->diff($start);
                $elapsed = $diff->y * 12 + $diff->m + 1;
                $elapsed = max(0, $elapsed);
            } else {
                $elapsed = $paidCnt;
            }

            $dueCnt   = max(0, $elapsed - $paidCnt);
            $advCnt   = max(0, $paidCnt - $elapsed);
            $debt     = $dueCnt * (float) $sub['monthly_price'];
            $nextMonth = $this->addMonths($sub['start_month'] ?? date('Y-m-01'), $paidCnt);

            $rows[] = array_merge($sub, [
                'paid_count'    => $paidCnt,
                'paid_amount'   => $paidAmt,
                'due_count'     => $dueCnt,
                'advance_count' => $advCnt,
                'debt'          => $debt,
                'next_month'    => $nextMonth,
            ]);
        }

        $totalCollected = array_sum(array_column($payments, 'amount'));
        $totalDebt      = array_sum(array_column($rows, 'debt'));
        $activeClients  = count(array_unique(array_column(
            array_filter($subs, fn($s) => $s['status'] === 'actif'),
            'client_id'
        )));

        // Filters from query string
        $filterCrm  = $r->get('crm', 'all');
        $filterFrom = $r->get('from', '');
        $filterTo   = $r->get('to', '');

        $filteredPayments = array_values(array_filter($payments, function ($p) use ($filterCrm, $filterFrom, $filterTo) {
            if ($filterCrm !== 'all' && $p['crm_id'] !== $filterCrm) return false;
            $paidDate = substr($p['paid_at'] ?? '', 0, 10);
            if ($filterFrom && $paidDate < $filterFrom) return false;
            if ($filterTo   && $paidDate > $filterTo)   return false;
            return true;
        }));
        $filteredCollected = array_sum(array_column($filteredPayments, 'amount'));

        // Stats by product (using filtered payments)
        $byCrm = array_map(function ($p) use ($rows, $filteredPayments) {
            $subIds = array_column(array_filter($rows, fn($s) => $s['crm_id'] === $p['id']), 'id');
            $coll   = array_sum(array_column(
                array_filter($filteredPayments, fn($fp) => $fp['crm_id'] === $p['id']),
                'amount'
            ));
            $debt = array_sum(array_column(
                array_filter($rows, fn($row) => $row['crm_id'] === $p['id']),
                'debt'
            ));
            return [
                'id'        => $p['id'],
                'name'      => $p['name'],
                'subs'      => count($subIds),
                'collected' => $coll,
                'debt'      => $debt,
            ];
        }, $products);

        // Stats by client (using filtered payments)
        $byClientMap = [];
        foreach ($rows as $row) {
            $cid = $row['client_id'];
            if (!isset($byClientMap[$cid])) {
                $byClientMap[$cid] = [
                    'name'      => $row['company_name'] ?: $row['client_name'],
                    'collected' => 0.0,
                    'debt'      => 0.0,
                    'advance'   => 0.0,
                ];
            }
            $byClientMap[$cid]['debt']    += $row['debt'];
            $byClientMap[$cid]['advance'] += $row['advance_count'] * (float) $row['monthly_price'];
        }
        foreach ($filteredPayments as $p) {
            if (isset($byClientMap[$p['client_id']])) {
                $byClientMap[$p['client_id']]['collected'] += (float) $p['amount'];
            }
        }
        usort($byClientMap, fn($a, $b) => $b['debt'] <=> $a['debt']);

        $this->view('app/crm/index', [
            'pageTitle'         => 'CRM — Progiciels — CaNDI',
            'products'          => $products,
            'clients'           => $clients,
            'subs'              => $rows,
            'payments'          => $payments,
            'filteredPayments'  => $filteredPayments,
            'filteredCollected' => $filteredCollected,
            'accounts'          => $accounts,
            'totalCollected'    => $totalCollected,
            'totalDebt'         => $totalDebt,
            'activeClients'     => $activeClients,
            'byCrm'             => $byCrm,
            'byClient'          => array_slice(array_values($byClientMap), 0, 30),
            'filterCrm'         => $filterCrm,
            'filterFrom'        => $filterFrom,
            'filterTo'          => $filterTo,
            'canCatalog'        => $this->canManageCatalog(),
            'canSubs'           => $this->canManageSubs(),
            'canPay'            => $this->canPay(),
            'success'           => Session::flash('success'),
            'error'             => Session::flash('error'),
        ]);
    }

    // ─── Produits ─────────────────────────────────────────────────────

    public function storeProduct(Request $r): void
    {
        if (!$this->canManageCatalog()) $this->abort(403);
        Csrf::check();

        $code  = trim($r->input('code', ''));
        $name  = trim($r->input('name', ''));
        $price = (float) $r->input('monthly_price', 0);

        if (!$code || !$name || $price <= 0) {
            Session::flash('error', 'Code, nom et prix sont obligatoires.');
            $this->redirect('/app/crm');
        }

        CrmProduct::upsert([
            'code'          => $code,
            'name'          => $name,
            'description'   => trim($r->input('description', '')) ?: null,
            'monthly_price' => $price,
            'is_active'     => 1,
        ]);

        Session::flash('success', 'Progiciel créé.');
        $this->redirect('/app/crm');
    }

    public function updateProduct(Request $r): void
    {
        if (!$this->canManageCatalog()) $this->abort(403);
        Csrf::check();

        $id = $r->param('id', '');
        if (!CrmProduct::findById($id)) $this->abort(404);

        CrmProduct::upsert([
            'code'          => trim($r->input('code', '')),
            'name'          => trim($r->input('name', '')),
            'description'   => trim($r->input('description', '')) ?: null,
            'monthly_price' => (float) $r->input('monthly_price', 0),
            'is_active'     => (int) $r->input('is_active', 1),
        ], $id);

        Session::flash('success', 'Progiciel mis à jour.');
        $this->redirect('/app/crm');
    }

    // ─── Clients ──────────────────────────────────────────────────────

    public function storeClient(Request $r): void
    {
        if (!$this->canManageSubs()) $this->abort(403);
        Csrf::check();

        $name = trim($r->input('full_name', ''));
        if (!$name) {
            Session::flash('error', 'Le nom complet est obligatoire.');
            $this->redirect('/app/crm');
        }

        CrmClient::upsert([
            'full_name'    => $name,
            'company_name' => trim($r->input('company_name', '')) ?: null,
            'phone'        => trim($r->input('phone', '')) ?: null,
            'email'        => trim($r->input('email', '')) ?: null,
            'city'         => trim($r->input('city', '')) ?: null,
            'client_type'  => $r->input('client_type', 'particulier'),
            'created_by'   => Auth::id(),
        ]);

        Session::flash('success', 'Client enregistré.');
        $this->redirect('/app/crm');
    }

    public function updateClient(Request $r): void
    {
        if (!$this->canManageSubs()) $this->abort(403);
        Csrf::check();

        $id = $r->param('id', '');
        if (!CrmClient::findById($id)) $this->abort(404);

        CrmClient::upsert([
            'full_name'    => trim($r->input('full_name', '')),
            'company_name' => trim($r->input('company_name', '')) ?: null,
            'phone'        => trim($r->input('phone', '')) ?: null,
            'email'        => trim($r->input('email', '')) ?: null,
            'city'         => trim($r->input('city', '')) ?: null,
            'client_type'  => $r->input('client_type', 'particulier'),
        ], $id);

        Session::flash('success', 'Client mis à jour.');
        $this->redirect('/app/crm');
    }

    public function destroyClient(Request $r): void
    {
        if (!$this->canManageCatalog()) $this->abort(403);
        Csrf::check();

        CrmClient::delete($r->param('id', ''));
        Session::flash('success', 'Client supprimé.');
        $this->redirect('/app/crm');
    }

    // ─── Abonnements ──────────────────────────────────────────────────

    public function storeSub(Request $r): void
    {
        if (!$this->canManageSubs()) $this->abort(403);
        Csrf::check();

        $clientId = $r->input('client_id', '');
        $crmId    = $r->input('crm_id', '');
        $start    = $r->input('start_month', '');
        $price    = (float) $r->input('monthly_price', 0);

        if (!$clientId || !$crmId || !$start || $price <= 0) {
            Session::flash('error', 'Client, progiciel, mois de démarrage et prix sont obligatoires.');
            $this->redirect('/app/crm');
        }

        // Normalize to YYYY-MM-01
        $startNorm = substr($start, 0, 7) . '-01';

        CrmSubscription::createSub([
            'client_id'     => $clientId,
            'crm_id'        => $crmId,
            'monthly_price' => $price,
            'start_month'   => $startNorm,
            'notes'         => trim($r->input('notes', '')) ?: null,
            'created_by'    => Auth::id(),
        ]);

        Session::flash('success', 'Abonnement créé.');
        $this->redirect('/app/crm');
    }

    public function updateSub(Request $r): void
    {
        if (!$this->canManageSubs()) $this->abort(403);
        Csrf::check();

        $id     = $r->param('id', '');
        $sub    = CrmSubscription::findById($id);
        if (!$sub) $this->abort(404);

        $status = $r->input('status', '');
        if (in_array($status, ['actif', 'annule'], true)) {
            CrmSubscription::updateStatus($id, $status);
        }

        Session::flash('success', $status === 'actif' ? 'Abonnement réactivé.' : 'Abonnement annulé.');
        $this->redirect('/app/crm');
    }

    public function destroySub(Request $r): void
    {
        if (!$this->canManageSubs()) $this->abort(403);
        Csrf::check();

        $id = $r->param('id', '');
        if (CrmSubscription::hasPayments($id)) {
            Session::flash('error', 'Impossible de supprimer : des paiements sont rattachés. Utilisez l\'annulation.');
            $this->redirect('/app/crm');
        }
        CrmSubscription::delete($id);
        Session::flash('success', 'Abonnement supprimé.');
        $this->redirect('/app/crm');
    }

    // ─── Paiements ────────────────────────────────────────────────────

    public function storePayment(Request $r): void
    {
        if (!$this->canPay()) $this->abort(403);
        Csrf::check();

        $subId = $r->param('id', '') ?: $r->input('subscription_id', '');
        $sub   = CrmSubscription::findById($subId);
        if (!$sub) $this->abort(404, 'Abonnement introuvable.');

        $amount   = (float) $r->input('amount', 0);
        $monthsRaw = $r->input('months', '');
        $accountId = $r->input('account_id', '') ?: null;
        $method   = $r->input('payment_method', 'especes');
        $paidAt   = $r->input('paid_at', date('Y-m-d'));

        if ($amount <= 0) {
            Session::flash('error', 'Montant invalide.');
            $this->redirect('/app/crm');
        }

        $monthList = array_filter(array_map('trim', explode(',', $monthsRaw)));
        if (empty($monthList)) {
            // Default: next unpaid month
            $paid = CrmPayment::forSubscription($subId);
            $next = $this->addMonths($sub['start_month'] ?? date('Y-m-01'), count($paid));
            $monthList = [$next];
        }

        $product = CrmProduct::findById($sub['crm_id']);
        $client  = CrmClient::findById($sub['client_id']);

        CrmPayment::createBatch([
            'client_id'      => $sub['client_id'],
            'crm_id'         => $sub['crm_id'],
            'subscription_id'=> $subId,
            'amount'         => $amount,
            'currency'       => 'GNF',
            'payment_method' => $method,
            'paid_at'        => $paidAt,
            'notes'          => trim($r->input('notes', '')) ?: null,
            'account_id'     => $accountId,
            'created_by'     => Auth::id(),
            'product_name'   => $product['name'] ?? '',
            'client_name'    => $client['full_name'] ?? '',
        ], $monthList);

        Session::flash('success', 'Paiement enregistré.');
        $this->redirect('/app/crm');
    }

    // ─── Helper ───────────────────────────────────────────────────────

    private function addMonths(string $iso, int $count): string
    {
        $d = new \DateTime(substr($iso, 0, 10));
        $d->modify("+{$count} months");
        return $d->format('Y-m-01');
    }
}
