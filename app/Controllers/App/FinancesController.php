<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Request;
use App\Core\Session;
use App\Core\Database;
use App\Models\Enrollment;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FundTransfer;
use App\Models\Notification;
use App\Models\Transaction;

class FinancesController extends AppController
{
    private const FINANCE_ROLES = ['super_admin', 'admin', 'comptable'];
    private const STAFF_ROLES   = ['super_admin', 'admin', 'comptable', 'responsable_formation', 'commercial'];

    public function index(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) {
            $this->redirect('/app/tableau-de-bord');
        }

        $filters = [
            'direction'   => $r->get('direction', ''),
            'account_id'  => $r->get('account', ''),
            'category_id' => $r->get('categorie', ''),
            'status'      => $r->get('status', ''),
            'month'       => $r->get('mois', ''),
            'search'      => $r->get('q', ''),
        ];
        $page   = max(1, (int) $r->get('page', 1));
        $result = Transaction::listAll(array_filter($filters), $page, 25);

        // Soldes par client (non-clos, triés par créance décroissante)
        $clientBalances = Database::getInstance()->query(
            "SELECT e.user_id,
                    TRIM(CONCAT(COALESCE(pr.first_name,''), ' ', COALESCE(pr.last_name,''))) AS learner_name,
                    COUNT(e.id) AS enrollment_count,
                    SUM(GREATEST(e.agreed_amount - e.discount, 0)) AS total_due,
                    SUM(e.paid_amount) AS total_paid,
                    SUM(GREATEST(e.agreed_amount - e.discount - e.paid_amount, 0)) AS total_remaining
             FROM enrollments e
             LEFT JOIN profiles pr ON pr.id = e.user_id
             WHERE e.status NOT IN ('annulee','refusee','remboursee','abandonnee')
             GROUP BY e.user_id, learner_name
             HAVING total_remaining > 0
             ORDER BY total_remaining DESC
             LIMIT 100"
        )->fetchAll();

        $this->view('app/finances/index', [
            'pageTitle'        => 'Finances — CaNDI',
            'accounts'         => FinancialAccount::allActive(),
            'categories'       => FinancialCategory::allActive(),
            'stats'            => FinancialAccount::globalStats(),
            'result'           => $result,
            'filters'          => $filters,
            'openEnrollments'  => Enrollment::openForCashier(),
            'clientBalances'   => $clientBalances,
            'transfers'        => FundTransfer::listRecent(50),
            'canValidateTransfer' => Auth::role() === 'super_admin',
            'success'          => Session::flash('success'),
            'error'            => Session::flash('error'),
        ]);
    }

    public function create(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) {
            $this->redirect('/app/finances');
        }
        $this->view('app/finances/form', [
            'pageTitle'  => 'Nouvelle opération',
            'tx'         => null,
            'accounts'   => FinancialAccount::allForSelect(),
            'categories' => FinancialCategory::allActive(),
            'old'        => Session::flash('old') ?? [],
            'errors'     => Session::flash('errors') ?? [],
        ]);
    }

    public function store(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $data   = $r->only(['account_id', 'direction', 'amount', 'tx_date', 'payment_method', 'category_id', 'description', 'third_party', 'reference', 'notes']);
        $errors = $this->validateTx($data);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/finances/creer');
        }

        $data['created_by'] = Auth::id();
        $data['status']     = 'soumise';
        Transaction::createAdmin($data);

        Session::flash('success', 'Opération enregistrée.');
        $this->redirect('/app/finances');
    }

    public function edit(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        $tx = Transaction::findAdmin($r->param('id', ''));
        if (!$tx || $tx['status'] === 'validee') $this->abort(404);

        $this->view('app/finances/form', [
            'pageTitle'  => 'Modifier l\'opération',
            'tx'         => $tx,
            'accounts'   => FinancialAccount::allForSelect(),
            'categories' => FinancialCategory::allActive(),
            'old'        => Session::flash('old') ?? [],
            'errors'     => Session::flash('errors') ?? [],
        ]);
    }

    public function update(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $id = $r->param('id', '');
        $tx = Transaction::findAdmin($id);
        if (!$tx || $tx['status'] === 'validee') $this->abort(404);

        $data   = $r->only(['account_id', 'direction', 'amount', 'tx_date', 'payment_method', 'category_id', 'description', 'third_party', 'reference', 'notes']);
        $errors = $this->validateTx($data);

        if ($errors) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/app/finances/' . $id . '/modifier');
        }

        Transaction::updateAdmin($id, $data);
        Session::flash('success', 'Opération mise à jour.');
        $this->redirect('/app/finances');
    }

    public function approuver(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $id = $r->param('id', '');
        $tx = Transaction::findAdmin($id);
        if (!$tx) $this->abort(404);

        Transaction::validate($id, Auth::id());
        Session::flash('success', 'Opération validée.');
        $this->redirect('/app/finances');
    }

    public function cancel(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        Transaction::cancel($r->param('id', ''));
        Session::flash('success', 'Opération annulée.');
        $this->redirect('/app/finances');
    }

    public function destroy(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $id = $r->param('id', '');
        $tx = Transaction::findAdmin($id);
        if (!$tx || $tx['status'] === 'validee') {
            Session::flash('error', 'Impossible de supprimer une opération validée.');
            $this->redirect('/app/finances');
        }

        Transaction::archive($id);
        Session::flash('success', 'Opération supprimée.');
        $this->redirect('/app/finances');
    }

    public function cashierEnrollment(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $enrollmentId = trim($r->input('enrollment_id', ''));
        $accountId    = trim($r->input('account_id', ''));
        $amount       = (float) $r->input('amount', 0);
        $discount     = (float) $r->input('discount', 0);
        $method       = $r->input('payment_method', 'especes');
        $txDate       = $r->input('tx_date', date('Y-m-d')) ?: date('Y-m-d');
        $reference    = trim($r->input('reference', ''));
        $notes        = trim($r->input('notes', ''));

        if (!$enrollmentId || !$accountId || $amount <= 0) {
            Session::flash('error', 'Dossier, compte et montant sont obligatoires.');
            $this->redirect('/app/finances');
        }

        $enr = Enrollment::findWithDetails($enrollmentId);
        if (!$enr) {
            Session::flash('error', 'Dossier d\'inscription introuvable.');
            $this->redirect('/app/finances');
        }

        $label = trim(($enr['first_name'] ?? '') . ' ' . ($enr['last_name'] ?? ''));

        Transaction::createForEnrollment([
            'account_id'     => $accountId,
            'amount'         => $amount,
            'tx_date'        => $txDate,
            'payment_method' => $method,
            'enrollment_id'  => $enrollmentId,
            'client_id'      => $enr['user_id'] ?? null,
            'description'    => 'Encaissement inscription ' . ($enr['program_title'] ?? '') . ($label ? ' — ' . $label : ''),
            'reference'      => $reference ?: null,
            'notes'          => $notes ?: null,
            'created_by'     => Auth::id(),
        ]);

        Enrollment::addPayment($enrollmentId, $amount, $discount);

        // Notify client that payment was recorded
        if (!empty($enr['user_id'])) {
            Notification::notifyUser(
                $enr['user_id'],
                'Paiement enregistré',
                'Un paiement de ' . number_format($amount, 0, ',', ' ') . ' GNF a été enregistré pour ' . ($enr['program_title'] ?? 'votre formation') . '.',
                'succes', 'paiements', '/app/inscriptions/' . $enrollmentId
            );
        }
        // Confirm to finance staff (not the one who just recorded it)
        Notification::notifyRole(
            ['super_admin', 'admin', 'comptable'],
            'Encaissement inscription : ' . number_format($amount, 0, ',', ' ') . ' GNF',
            ($label ?: 'Apprenant') . ' — ' . ($enr['program_title'] ?? '') . '. Enregistré par ' . ($enr['first_name'] ?? 'staff') . '.',
            'succes', 'inscriptions', '/app/finances',
            Auth::id()
        );

        Session::flash('success', 'Encaissement enregistré pour ' . ($label ?: $enr['enrollment_number'] ?? 'le dossier') . '.');
        $this->redirect('/app/finances');
    }

    public function storeTransfer(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $fromId = trim($r->input('from_account_id', ''));
        $toId   = trim($r->input('to_account_id', ''));
        $amount = (float) $r->input('amount', 0);
        $note   = trim($r->input('note', ''));

        if (!$fromId || !$toId || $amount <= 0 || $fromId === $toId) {
            Session::flash('error', 'Données invalides pour le transfert.');
            $this->redirect('/app/finances');
        }

        FundTransfer::createTransfer([
            'from_account_id' => $fromId,
            'to_account_id'   => $toId,
            'amount'          => $amount,
            'note'            => $note ?: null,
            'requested_by'    => Auth::id(),
        ]);

        // Notify super_admin that a transfer awaits validation
        Notification::notifyRole(
            ['super_admin'],
            'Transfert de caisse à valider',
            number_format($amount, 0, ',', ' ') . ' GNF en attente de votre confirmation.' . ($note ? ' Note : ' . $note : ''),
            'alerte', 'finances', '/app/finances',
            Auth::id()
        );

        Session::flash('success', 'Virement demandé.');
        $this->redirect('/app/finances');
    }

    public function approveTransfer(Request $r): void
    {
        if (Auth::role() !== 'super_admin') $this->redirect('/app/finances');
        Csrf::check();
        $transferId = $r->param('id', '');
        $transfer   = FundTransfer::findById($transferId);
        try {
            FundTransfer::approve($transferId, Auth::id());
            // Notify the requester that their transfer was approved
            if ($transfer && !empty($transfer['requested_by']) && $transfer['requested_by'] !== Auth::id()) {
                Notification::notifyUser(
                    $transfer['requested_by'],
                    'Transfert validé',
                    'Votre transfert de ' . number_format((float)$transfer['amount'], 0, ',', ' ') . ' GNF (' . ($transfer['reference'] ?? '') . ') a été confirmé.',
                    'succes', 'finances', '/app/finances'
                );
            }
            Session::flash('success', 'Transfert validé — les écritures comptables ont été générées.');
        } catch (\Throwable $e) {
            Session::flash('error', 'Erreur lors de la validation : ' . $e->getMessage());
        }
        $this->redirect('/app/finances');
    }

    public function refuseTransfer(Request $r): void
    {
        if (Auth::role() !== 'super_admin') $this->redirect('/app/finances');
        Csrf::check();
        $transferId = $r->param('id', '');
        $transfer   = FundTransfer::findById($transferId);
        FundTransfer::refuse($transferId, Auth::id());
        // Notify the requester that their transfer was refused
        if ($transfer && !empty($transfer['requested_by']) && $transfer['requested_by'] !== Auth::id()) {
            Notification::notifyUser(
                $transfer['requested_by'],
                'Transfert refusé',
                'Votre transfert de ' . number_format((float)$transfer['amount'], 0, ',', ' ') . ' GNF (' . ($transfer['reference'] ?? '') . ') a été refusé.',
                'alerte', 'finances', '/app/finances'
            );
        }
        Session::flash('success', 'Transfert refusé.');
        $this->redirect('/app/finances');
    }

    public function exportJournal(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');

        $filters = [
            'direction'   => $r->get('direction', ''),
            'account_id'  => $r->get('account', ''),
            'category_id' => $r->get('categorie', ''),
            'status'      => $r->get('status', ''),
            'month'       => $r->get('mois', ''),
            'search'      => $r->get('q', ''),
        ];
        $rows = Transaction::listAll(array_filter($filters), 1, 9999)['data'];

        $accountNames = array_column(FinancialAccount::allActive(), 'name', 'id');

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="journal-finances-' . date('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // UTF-8 BOM
        fputcsv($out, ['Date', 'Reference', 'Caisse', 'Type', 'Description', 'Tiers', 'Moyen', 'Entree (GNF)', 'Sortie (GNF)', 'Statut'], ';');
        foreach ($rows as $tx) {
            fputcsv($out, [
                $tx['tx_date'],
                $tx['reference'] ?? '',
                $accountNames[$tx['account_id']] ?? '',
                $tx['direction'] === 'entree' ? 'Encaissement' : 'Depense',
                $tx['description'] ?? '',
                $tx['third_party'] ?? '',
                $tx['payment_method'] ?? '',
                $tx['direction'] === 'entree' ? number_format((float)$tx['amount'], 0, ',', ' ') : '',
                $tx['direction'] === 'sortie' ? number_format((float)$tx['amount'], 0, ',', ' ') : '',
                $tx['status'] ?? '',
            ], ';');
        }
        fclose($out);
        exit;
    }

    public function storeAccount(Request $r): void
    {
        if (!Auth::hasRole(self::FINANCE_ROLES)) $this->redirect('/app/finances');
        Csrf::check();

        $name    = trim($r->input('name', ''));
        $type    = $r->input('type', 'caisse');
        $balance = (float) $r->input('opening_balance', 0);

        if (!$name) {
            Session::flash('error', 'Le nom du compte est obligatoire.');
            $this->redirect('/app/finances');
        }

        FinancialAccount::createAccount([
            'name'            => $name,
            'type'            => $type,
            'opening_balance' => $balance,
        ]);

        Session::flash('success', 'Compte « ' . $name . ' » créé.');
        $this->redirect('/app/finances');
    }

    // -----------------------------------------------------------------------

    private function validateTx(array $d): array
    {
        $errors = [];
        if (empty($d['account_id']))                 $errors['account_id'] = 'Le compte est obligatoire.';
        if (!in_array($d['direction'] ?? '', ['entree', 'sortie'], true)) $errors['direction'] = 'Direction invalide.';
        if (!is_numeric($d['amount'] ?? '') || (float)$d['amount'] <= 0) $errors['amount']    = 'Montant invalide.';
        if (empty($d['tx_date']))                    $errors['tx_date']    = 'La date est obligatoire.';
        return $errors;
    }

    // Stubs
    public function show(Request $r): void      { $this->abort(404); }
    public function initiate(Request $r): void  { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void { $this->redirect('/app/paiements'); }
    public function toggle(Request $r): void    { $this->redirect('/app/finances'); }
    public function updateRole(Request $r): void     { $this->redirect('/app/utilisateurs'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void  { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void    { $this->redirect('/app/finances'); }
    public function monLogement(Request $r): void { $this->redirect('/app/finances'); }
}
