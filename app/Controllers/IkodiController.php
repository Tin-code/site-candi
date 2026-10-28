<?php

namespace App\Controllers;

use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Controller;

class IkodiController extends Controller
{
    private const SYSTEM_PROMPT =
        "Tu t'appelles CaNDI AI, l'assistant IA public de CaNDI, un centre de formation, "
        . "de services numériques et de gestion immobilière en Guinée (Conakry — Kaloum, et Kankan). "
        . "Tu réponds toujours en français, de façon chaleureuse, concise et professionnelle. "
        . "Les montants sont en francs guinéens (GNF). "
        . "Tu parles à un visiteur du site : il n'a peut-être pas de compte. "
        . "Tu ne disposes que des informations publiques ci-dessous (catalogue de formations publiées, "
        . "sessions ouvertes, actualités publiées, produits et services, service immobilier et logements libres). "
        . "Tu n'as accès à aucune donnée interne (inscriptions, finances, clients, locataires, baux, propriétaires) : "
        . "si on t'en demande, invite poliment la personne à créer un compte ou à contacter l'équipe. "
        . "N'invente jamais un prix, une date, un logement ou un contact.\n\n"

        . "=== SERVICE IMMOBILIER CaNDI ===\n"
        . "CaNDI assure la gestion locative d'immeubles et d'appartements en Guinée.\n"
        . "Pour les propriétaires : recherche et sélection des locataires, gestion des contrats de bail, "
        . "encaissement sécurisé des loyers, suivi financier transparent et reversement régulier.\n"
        . "Pour les locataires : logement attribué avec un code d'appartement, espace en ligne « Mon logement » "
        . "pour déclarer chaque paiement et suivre son historique.\n"
        . "Pour trouver un logement ou confier un bien en gestion : appeler ou écrire sur WhatsApp au "
        . "+224 626 01 17 17 (service immobilier). Visites uniquement sur rendez-vous.\n"
        . "Ne communique jamais le nom d'un locataire, d'un propriétaire, un montant de loyer encaissé "
        . "ni un code d'appartement : ces informations sont confidentielles.\n\n"

        . "=== À PROPOS DE CaNDI ===\n"
        . "CaNDI a été fondée en 2023 par Mohamed SOUARE, actuel Directeur Général.\n"
        . "Devise : « Bâtir les compétences, propulser l'excellence. »\n"
        . "Trois pôles : formation professionnelle certifiante, édition de progiciels de gestion, gestion immobilière locative.\n"
        . "CaNDI a reçu le trophée de meilleure entreprise de formation.\n"
        . "Implantations : siège à Kaloum (Conakry) et agence à Sogbè (Kankan). "
        . "Immatriculation : RCCM/GN.TCC.2022.A.11573.\n"
        . "Contacts : 626 01 17 17 / 625 29 54 56 / 622 98 44 45 — Conakry, Kaloum / Kankan, Sogbè. "
        . "E-mail : info@candi-group.com. Service immobilier : +224 626 01 17 17.";

    public function index(Request $r): void
    {
        $this->view('ikodi/index', [
            'pageTitle' => 'CaNDI AI — Assistant IA de CaNDI',
            'metaDesc'  => 'Posez vos questions à CaNDI AI : formations, sessions, tarifs et actualités de CaNDI. Accessible sans compte.',
        ], 'layouts/main');
    }

    public function ask(Request $r): void
    {
        header('Content-Type: application/json; charset=utf-8');

        Csrf::check();

        $apiKey = \App\Core\env('ANTHROPIC_API_KEY', '');
        if (!$apiKey) {
            echo json_encode(['error' => 'CaNDI AI indisponible pour le moment.']);
            exit;
        }

        $body    = (string) file_get_contents('php://input');
        $payload = json_decode($body, true);
        $messages = $payload['messages'] ?? [];

        if (empty($messages) || !is_array($messages)) {
            echo json_encode(['error' => 'Aucun message fourni.']);
            exit;
        }

        $validMessages = [];
        foreach (array_slice($messages, -8) as $m) {
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

        $context    = $this->buildPublicContext();
        $fullSystem = self::SYSTEM_PROMPT . "\n\nInformations publiques :\n" . $context;

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
            CURLOPT_TIMEOUT => 30,
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
            echo json_encode(['error' => 'Trop de requêtes. Réessayez dans un instant.']);
            exit;
        }
        if ($httpCode === 401) {
            echo json_encode(['error' => 'Clé API invalide.']);
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

    private function buildPublicContext(): string
    {
        $pdo   = Database::getInstance();
        $today = date('Y-m-d');
        $lines = [];

        // Formations publiées
        $programs = $pdo->query(
            "SELECT code, title, summary, level, price, promo_price, duration_label, total_hours
             FROM training_programs
             WHERE is_published = 1 AND archived_at IS NULL
             ORDER BY title ASC LIMIT 100"
        )->fetchAll();

        $lines[] = "=== FORMATIONS PUBLIÉES ===";
        $lines[] = json_encode(array_map(fn($p) => [
            'code'      => $p['code'],
            'titre'     => $p['title'],
            'résumé'    => $p['summary'],
            'niveau'    => $p['level'],
            'prix'      => (float)$p['price'],
            'promo'     => $p['promo_price'] ? (float)$p['promo_price'] : null,
            'durée'     => $p['duration_label'],
            'heures'    => $p['total_hours'],
        ], $programs));

        // Sessions ouvertes (à venir)
        $sessions = $pdo->query(
            "SELECT ts.code, tp.title AS program_title, ts.start_date, ts.end_date,
                    ts.location, ts.modality, ts.capacity, ts.seats_taken
             FROM training_sessions ts
             LEFT JOIN training_programs tp ON tp.id = ts.program_id
             WHERE ts.is_published = 1 AND ts.archived_at IS NULL
               AND ts.start_date >= CURDATE()
             ORDER BY ts.start_date ASC LIMIT 30"
        )->fetchAll();

        $lines[] = "";
        $lines[] = "=== SESSIONS À VENIR ===";
        $lines[] = json_encode(array_map(fn($s) => [
            'code'        => $s['code'],
            'formation'   => $s['program_title'],
            'début'       => $s['start_date'],
            'fin'         => $s['end_date'],
            'lieu'        => $s['location'],
            'modalité'    => $s['modality'],
            'places'      => ($s['seats_taken'] ?? 0) . '/' . ($s['capacity'] ?? '?'),
        ], $sessions));

        // Actualités
        $posts = $pdo->query(
            "SELECT title, slug, excerpt, category, published_at
             FROM posts WHERE status = 'publie'
             ORDER BY published_at DESC LIMIT 10"
        )->fetchAll();

        $lines[] = "";
        $lines[] = "=== ACTUALITÉS ===";
        $lines[] = json_encode($posts);

        // Offres / produits-services
        $offerings = $pdo->query(
            "SELECT kind, title, summary, price_label
             FROM offerings WHERE is_published = 1
             ORDER BY sort_order ASC LIMIT 30"
        )->fetchAll();

        $lines[] = "";
        $lines[] = "=== PRODUITS ET SERVICES ===";
        $lines[] = json_encode($offerings);

        // Logements libres
        $apartments = $pdo->query(
            "SELECT a.label, a.rooms, a.monthly_rent, a.currency, p.name AS immeuble, p.city
             FROM apartments a
             LEFT JOIN properties p ON p.id = a.property_id
             WHERE a.id NOT IN (
                 SELECT apartment_id FROM leases WHERE status = 'actif'
             )
             ORDER BY p.name, a.label LIMIT 50"
        )->fetchAll();

        $lines[] = "";
        $lines[] = "=== LOGEMENTS DISPONIBLES ===";
        $lines[] = "Nombre actuellement disponibles : " . count($apartments);
        $lines[] = json_encode(array_map(fn($a) => [
            'logement'  => $a['label'],
            'pièces'    => $a['rooms'],
            'loyer'     => (float)$a['monthly_rent'],
            'devise'    => $a['currency'],
            'immeuble'  => $a['immeuble'],
            'ville'     => $a['city'],
        ], $apartments));

        return implode("\n", $lines);
    }
}
