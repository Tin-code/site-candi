<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;

class AssistantController extends AppController
{
    private const SYSTEM_PROMPT = "Tu t'appelles CaNDI AI, l'assistant IA de CaNDI, un centre de formation en Guinée.\n"
        . "Tu réponds toujours en français, de façon concise et professionnelle.\n"
        . "Les montants sont en francs guinéens (GNF). Tu aides l'équipe à interpréter les\n"
        . "inscriptions, les finances, la trésorerie, les clients, le catalogue, les tâches,\n"
        . "les publications, les utilisateurs et l'immobilier (immeubles, appartements,\n"
        . "locataires, loyers, propriétaires).\n"
        . "Le contexte ci-dessous est déjà filtré selon les droits d'accès du compte connecté :\n"
        . "tu ne vois que ce que cet utilisateur a le droit de voir. Si une donnée est absente\n"
        . "ou vide, dis simplement qu'elle n'est pas disponible pour ce profil — n'invente rien.";

    public function index(Request $r): void
    {
        $this->view('app/assistant/index', [
            'pageTitle' => 'CaNDI AI',
        ]);
    }

    public function ask(Request $r): void
    {
        header('Content-Type: application/json; charset=utf-8');

        Csrf::check();

        $apiKey = \App\Core\env('ANTHROPIC_API_KEY', '');
        if (!$apiKey) {
            echo json_encode(['error' => 'CaNDI AI indisponible : clé ANTHROPIC_API_KEY non configurée dans les paramètres.']);
            exit;
        }

        $body     = (string) file_get_contents('php://input');
        $payload  = json_decode($body, true);
        $messages = $payload['messages'] ?? [];

        if (empty($messages) || !is_array($messages)) {
            echo json_encode(['error' => 'Aucun message fourni.']);
            exit;
        }

        // Sanitize and limit to last 10 messages
        $validMessages = [];
        foreach (array_slice($messages, -10) as $m) {
            if (isset($m['role'], $m['content'])
                && in_array($m['role'], ['user', 'assistant'], true)
                && is_string($m['content'])
            ) {
                $validMessages[] = ['role' => $m['role'], 'content' => $m['content']];
            }
        }
        if (empty($validMessages)) {
            echo json_encode(['error' => 'Messages invalides.']);
            exit;
        }

        $context    = $this->buildContext();
        $fullSystem = self::SYSTEM_PROMPT . "\n\nContexte actuel:\n" . $context;

        $apiPayload = [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'system'     => $fullSystem,
            'messages'   => $validMessages,
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($apiPayload),
            CURLOPT_HTTPHEADER     => [
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01',
                'content-type: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr  = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            echo json_encode(['error' => 'Erreur réseau : ' . $curlErr]);
            exit;
        }
        if ($httpCode === 429) {
            echo json_encode(['error' => 'Trop de requêtes vers CaNDI AI. Réessayez dans un instant.']);
            exit;
        }
        if ($httpCode === 401) {
            echo json_encode(['error' => 'Clé API Anthropic invalide.']);
            exit;
        }
        if ($httpCode !== 200) {
            $errData = json_decode($response, true);
            $errMsg  = $errData['error']['message'] ?? 'Erreur inconnue';
            echo json_encode(['error' => "Erreur CaNDI AI ({$httpCode}) : {$errMsg}"]);
            exit;
        }

        $data  = json_decode($response, true);
        $reply = '';
        foreach ($data['content'] ?? [] as $block) {
            if (($block['type'] ?? '') === 'text') {
                $reply = $block['text'];
                break;
            }
        }

        echo json_encode(['reply' => $reply ?: 'Aucune réponse générée.']);
        exit;
    }

    // ─── Context builder ─────────────────────────────────────────────────────

    private function buildContext(): string
    {
        $pdo  = Database::getInstance();
        $role = Auth::role() ?? 'client';
        $uid  = Auth::id();

        $lines = [];

        // ── Inscriptions ──────────────────────────────────────────────────
        if (in_array($role, ['super_admin', 'admin', 'secretaire', 'finance', 'formateur'], true)) {
            $enrolls = $pdo->query(
                "SELECT enrollment_number, status, payment_mode,
                        agreed_amount, discount, paid_amount, created_at
                 FROM enrollments WHERE archived_at IS NULL
                 ORDER BY created_at DESC LIMIT 500"
            )->fetchAll();
        } elseif ($role === 'client') {
            $stmt = $pdo->prepare(
                "SELECT enrollment_number, status, payment_mode,
                        agreed_amount, discount, paid_amount, created_at
                 FROM enrollments WHERE user_id = ? AND archived_at IS NULL
                 ORDER BY created_at DESC LIMIT 50"
            );
            $stmt->execute([$uid]);
            $enrolls = $stmt->fetchAll();
        } else {
            $enrolls = [];
        }

        $totalDue = 0;
        $byStatus = [];
        foreach ($enrolls as $e) {
            $byStatus[$e['status']] = ($byStatus[$e['status']] ?? 0) + 1;
            $totalDue += max((float)$e['agreed_amount'] - (float)$e['discount'], 0) - (float)$e['paid_amount'];
        }
        $lines[] = "=== INSCRIPTIONS ===";
        $lines[] = "Total actives: " . count($enrolls);
        $lines[] = "Par statut: " . json_encode($byStatus);
        $lines[] = "Reste à encaisser (GNF): " . round($totalDue);
        if ($enrolls) {
            $lines[] = "10 dernières: " . json_encode(array_map(fn($e) => [
                'num' => $e['enrollment_number'], 'statut' => $e['status'],
                'convenu' => (float)$e['agreed_amount'], 'payé' => (float)$e['paid_amount'],
                'créé' => $e['created_at'],
            ], array_slice($enrolls, 0, 10)));
        }

        // ── Finances ──────────────────────────────────────────────────────
        if (in_array($role, ['super_admin', 'admin', 'finance'], true)) {
            $txs      = $pdo->query(
                "SELECT reference, direction, tx_date, amount, status,
                        payment_method, third_party, description, account_id
                 FROM transactions WHERE archived_at IS NULL
                 ORDER BY tx_date DESC LIMIT 500"
            )->fetchAll();
            $accounts = $pdo->query(
                "SELECT id, label AS name, type, currency, opening_balance, is_active
                 FROM financial_accounts LIMIT 50"
            )->fetchAll();
            $transfers = $pdo->query(
                "SELECT reference, amount, status, description AS note, created_at
                 FROM fund_transfers ORDER BY created_at DESC LIMIT 50"
            )->fetchAll();

            $validated = array_filter($txs, fn($t) => $t['status'] === 'validee');
            $income    = array_sum(array_map(fn($t) => (float)$t['amount'], array_filter($validated, fn($t) => $t['direction'] === 'entree')));
            $expense   = array_sum(array_map(fn($t) => (float)$t['amount'], array_filter($validated, fn($t) => $t['direction'] === 'sortie')));

            $balances = [];
            foreach ($accounts as $a) {
                $mine   = array_filter($validated, fn($t) => $t['account_id'] === $a['id']);
                $inc    = array_sum(array_map(fn($t) => (float)$t['amount'], array_filter($mine, fn($t) => $t['direction'] === 'entree')));
                $exp    = array_sum(array_map(fn($t) => (float)$t['amount'], array_filter($mine, fn($t) => $t['direction'] === 'sortie')));
                $balances[] = ['compte' => $a['name'], 'type' => $a['type'], 'solde' => round((float)$a['opening_balance'] + $inc - $exp)];
            }

            $txByStatus = [];
            foreach ($txs as $t) { $txByStatus[$t['status']] = ($txByStatus[$t['status']] ?? 0) + 1; }

            $lines[] = "";
            $lines[] = "=== FINANCES ===";
            $lines[] = "Encaissements validés (GNF): " . round($income);
            $lines[] = "Dépenses validées (GNF): " . round($expense);
            $lines[] = "Résultat net (GNF): " . round($income - $expense);
            $lines[] = "Transactions par statut: " . json_encode($txByStatus);
            $lines[] = "Soldes par compte: " . json_encode($balances);
            $lines[] = "10 dernières transactions: " . json_encode(array_map(fn($t) => [
                'ref' => $t['reference'], 'sens' => $t['direction'],
                'date' => $t['tx_date'], 'montant' => (float)$t['amount'],
                'statut' => $t['status'], 'tiers' => $t['third_party'], 'libellé' => $t['description'],
            ], array_slice($txs, 0, 10)));
            $lines[] = "Transferts de caisse: " . json_encode(array_slice($transfers, 0, 10));
        }

        // ── Catalogue ─────────────────────────────────────────────────────
        $programs = $pdo->query(
            "SELECT code, title, level, price, promo_price, duration_label, is_published
             FROM training_programs WHERE archived_at IS NULL LIMIT 100"
        )->fetchAll();
        $sessions = $pdo->query(
            "SELECT code, program_id, start_date, end_date, capacity, seats_taken,
                    status, modality, location, is_published
             FROM training_sessions WHERE archived_at IS NULL
             ORDER BY start_date ASC LIMIT 50"
        )->fetchAll();
        $today    = date('Y-m-d');
        $upcoming = array_filter($sessions, fn($s) => ($s['start_date'] ?? '') >= $today);

        $lines[] = "";
        $lines[] = "=== CATALOGUE ===";
        $lines[] = "Programmes: " . count($programs) . " (publiés: " . count(array_filter($programs, fn($p) => $p['is_published'])) . ")";
        $lines[] = "Programmes: " . json_encode(array_map(fn($p) => [
            'code' => $p['code'], 'titre' => $p['title'],
            'prix' => (float)$p['price'], 'publié' => (bool)$p['is_published'],
        ], $programs));
        $lines[] = "Prochaines sessions: " . json_encode(array_values(array_map(fn($s) => [
            'code' => $s['code'], 'début' => $s['start_date'],
            'places' => $s['seats_taken'] . '/' . $s['capacity'],
            'statut' => $s['status'], 'modalité' => $s['modality'],
        ], array_slice($upcoming, 0, 15))));

        // ── Tâches ────────────────────────────────────────────────────────
        if (in_array($role, ['super_admin', 'admin', 'secretaire', 'finance', 'formateur',
                              'gestionnaire_immobilier', 'commercial'], true)) {
            $tasks = $pdo->query(
                "SELECT title, status, priority, module, due_date, assigned_to
                 FROM tasks WHERE archived_at IS NULL LIMIT 300"
            )->fetchAll();

            $openTasks = array_filter($tasks, fn($t) => !in_array($t['status'], ['terminee', 'annulee'], true));
            $lateTasks = array_filter($openTasks, fn($t) => $t['due_date'] && $t['due_date'] < $today);
            $taskByStatus = [];
            foreach ($tasks as $t) { $taskByStatus[$t['status']] = ($taskByStatus[$t['status']] ?? 0) + 1; }

            $lines[] = "";
            $lines[] = "=== TÂCHES ===";
            $lines[] = "Ouvertes: " . count($openTasks) . " — en retard: " . count($lateTasks);
            $lines[] = "Par statut: " . json_encode($taskByStatus);
            $urgent  = array_values(array_filter($openTasks, fn($t) => $t['priority'] === 'urgente'));
            $late    = array_values($lateTasks);
            $lines[] = "Urgentes ou en retard: " . json_encode(array_slice(array_merge($urgent, $late), 0, 15));
        }

        // ── Clients / CRM ─────────────────────────────────────────────────
        if (in_array($role, ['super_admin', 'admin', 'commercial', 'finance'], true)) {
            $clients = $pdo->query(
                "SELECT full_name, company_name, client_type, status, city, created_at
                 FROM clients WHERE archived_at IS NULL
                 ORDER BY created_at DESC LIMIT 300"
            )->fetchAll();
            $byType = [];
            foreach ($clients as $c) { $byType[$c['client_type']] = ($byType[$c['client_type']] ?? 0) + 1; }

            $lines[] = "";
            $lines[] = "=== CLIENTS & CRM ===";
            $lines[] = "Total clients: " . count($clients);
            $lines[] = "Par type: " . json_encode($byType);
            $lines[] = "10 derniers: " . json_encode(array_map(fn($c) => [
                'nom' => $c['full_name'], 'entreprise' => $c['company_name'],
                'type' => $c['client_type'], 'statut' => $c['status'],
            ], array_slice($clients, 0, 10)));
        }

        // ── Immobilier ────────────────────────────────────────────────────
        if (in_array($role, ['super_admin', 'admin', 'gestionnaire_immobilier', 'finance'], true)) {
            $properties  = $pdo->query("SELECT name, city, is_active FROM properties WHERE is_active = 1 LIMIT 100")->fetchAll();
            $apartments  = $pdo->query("SELECT code, label, status, monthly_rent FROM apartments LIMIT 200")->fetchAll();
            $leases      = $pdo->query("SELECT tenant_name, monthly_rent, start_month, status FROM leases LIMIT 200")->fetchAll();
            $rentPaymts  = $pdo->query(
                "SELECT period_month, amount, status, payment_method
                 FROM rent_payments ORDER BY created_at DESC LIMIT 300"
            )->fetchAll();
            $owners = $pdo->query(
                "SELECT full_name, owner_type, commission_rate, is_active FROM property_owners LIMIT 100"
            )->fetchAll();

            $aptByStatus = [];
            foreach ($apartments as $a) { $aptByStatus[$a['status']] = ($aptByStatus[$a['status']] ?? 0) + 1; }
            $leaseByStatus = [];
            foreach ($leases as $l) { $leaseByStatus[$l['status']] = ($leaseByStatus[$l['status']] ?? 0) + 1; }
            $loyers = array_sum(array_map(fn($p) => (float)$p['amount'], array_filter($rentPaymts, fn($p) => $p['status'] === 'validee')));
            $loyersAttente = count(array_filter($rentPaymts, fn($p) => $p['status'] === 'en_attente'));

            $lines[] = "";
            $lines[] = "=== IMMOBILIER ===";
            $lines[] = "Immeubles: " . count($properties) . " — Appartements: " . count($apartments);
            $lines[] = "Appartements par statut: " . json_encode($aptByStatus);
            $lines[] = "Baux par statut: " . json_encode($leaseByStatus);
            $lines[] = "Loyers validés (GNF): " . round($loyers);
            $lines[] = "Loyers en attente de validation: " . $loyersAttente;
            $lines[] = "Appartements: " . json_encode(array_slice($apartments, 0, 30));
            $lines[] = "Locataires: " . json_encode(array_map(fn($l) => [
                'locataire' => $l['tenant_name'], 'loyer' => (float)$l['monthly_rent'],
                'début' => $l['start_month'], 'statut' => $l['status'],
            ], array_slice($leases, 0, 30)));
            $lines[] = "10 derniers paiements loyer: " . json_encode(array_slice($rentPaymts, 0, 10));
            $lines[] = "Propriétaires: " . json_encode($owners);
        } elseif ($role === 'client') {
            // Tenant: only their own lease
            $stmt = $pdo->prepare(
                "SELECT l.tenant_name, l.monthly_rent, l.start_month, l.status,
                        a.label AS appartement
                 FROM leases l
                 JOIN apartments a ON a.id = l.apartment_id
                 WHERE l.tenant_user_id = ? AND l.status = 'actif' LIMIT 1"
            );
            $stmt->execute([$uid]);
            $myLease = $stmt->fetch();
            if ($myLease) {
                $lines[] = "";
                $lines[] = "=== MON LOGEMENT ===";
                $lines[] = json_encode($myLease);
            }
        }

        return implode("\n", $lines);
    }
}
