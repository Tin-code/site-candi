<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Database;
use App\Core\Request;

class RapportsController extends AppController
{
    public function index(Request $r): void
    {
        if (Auth::role() === 'client') $this->abort(403, 'Accès réservé au personnel.');

        $pdo = Database::getInstance();

        $enrollments = $pdo->query(
            "SELECT id, status, agreed_amount, paid_amount FROM enrollments WHERE archived_at IS NULL"
        )->fetchAll();

        $transactions = $pdo->query(
            "SELECT id, direction, amount, status, tx_date, payment_method, description, reference FROM transactions WHERE archived_at IS NULL ORDER BY tx_date DESC"
        )->fetchAll();

        $income = $expense = $outstanding = 0.0;
        $byStatus = [];

        foreach ($transactions as $t) {
            if ($t['status'] !== 'validee') continue;
            if ($t['direction'] === 'entree') $income  += (float) $t['amount'];
            if ($t['direction'] === 'sortie') $expense += (float) $t['amount'];
        }
        foreach ($enrollments as $e) {
            $outstanding += max(0, (float) $e['agreed_amount'] - (float) $e['paid_amount']);
            $s = $e['status'] ?? 'inconnu';
            if (!isset($byStatus[$s])) $byStatus[$s] = ['count' => 0, 'amount' => 0.0];
            $byStatus[$s]['count']++;
            $byStatus[$s]['amount'] += (float) $e['agreed_amount'];
        }

        $this->view('app/rapports/index', [
            'pageTitle'    => 'Rapports & exports — CaNDI',
            'enrollCount'  => count($enrollments),
            'income'       => $income,
            'expense'      => $expense,
            'outstanding'  => $outstanding,
            'byStatus'     => $byStatus,
        ]);
    }

    public function exportCsv(Request $r): void
    {
        if (Auth::role() === 'client') $this->abort(403);

        $type = $r->get('type', 'inscriptions');
        $pdo  = Database::getInstance();

        switch ($type) {
            case 'transactions':
                $rows = $pdo->query(
                    "SELECT t.id, t.direction, t.amount, t.currency, t.status, t.tx_date,
                            t.payment_method, t.description, t.reference, fa.name AS compte
                     FROM transactions t
                     LEFT JOIN financial_accounts fa ON fa.id = t.account_id
                     WHERE t.archived_at IS NULL ORDER BY t.tx_date DESC"
                )->fetchAll();
                $filename = 'transactions-' . date('Y-m-d');
                break;

            case 'clients':
                $rows = $pdo->query(
                    "SELECT id, full_name, company_name, client_type, phone, email, city, status, created_at
                     FROM clients ORDER BY full_name"
                )->fetchAll();
                $filename = 'clients-' . date('Y-m-d');
                break;

            default:
                $rows = $pdo->query(
                    "SELECT e.id, e.enrollment_number, e.status, e.agreed_amount, e.paid_amount,
                            e.payment_mode, e.created_at,
                            p.first_name, p.last_name, p.phone,
                            tp.title AS programme
                     FROM enrollments e
                     LEFT JOIN profiles p ON p.id = e.user_id
                     LEFT JOIN training_programs tp ON tp.id = e.program_id
                     WHERE e.archived_at IS NULL ORDER BY e.created_at DESC"
                )->fetchAll();
                $filename = 'inscriptions-' . date('Y-m-d');
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
        header('Cache-Control: no-cache');
        echo "\xEF\xBB\xBF";

        if (!empty($rows)) {
            $out = fopen('php://output', 'w');
            fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $row) fputcsv($out, $row, ';');
            fclose($out);
        }
        exit;
    }
}
