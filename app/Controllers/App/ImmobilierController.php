<?php
namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\Apartment;
use App\Models\FinancialAccount;
use App\Models\Lease;
use App\Models\Notification;
use App\Models\Property;
use App\Models\PropertyOwner;
use App\Models\RentPayment;

class ImmobilierController extends AppController
{
    private const IMMO_ROLES = ['super_admin', 'admin', 'gestionnaire_immobilier'];

    // ─── Gestion principale ────────────────────────────────────────────

    public function index(Request $r): void
    {
        $this->requireImmo();

        $apartments    = Apartment::allWithLease();
        $properties    = Property::allActive();
        $owners        = PropertyOwner::allActive();
        $activeLeases  = Lease::allActive();
        $leases        = Lease::allLeases();
        $pending       = RentPayment::pendingList();
        $accounts   = FinancialAccount::allForSelect();

        // KPI: appartements occupés / total
        $occupiedCount = count(array_filter($apartments, fn($a) => $a['status'] === 'occupe'));
        $totalAptCount = count($apartments);

        // KPI: Encaissé ce mois (validées)
        $encaisseThisMonth = RentPayment::encaisseThisMonth();

        // allSituations: dette/avance par bail
        $validatedByLease = RentPayment::validatedByLease();
        $now              = new \DateTime();
        $currentMonthStr  = $now->format('Y-m');
        $totalDebt        = 0.0;
        $situations       = [];

        foreach ($activeLeases as $l) {
            $rent      = (float) $l['monthly_rent'];
            $startDate = new \DateTime($l['start_month']);
            $cursor    = clone $startDate;
            $paid      = $validatedByLease[$l['id']] ?? [];
            $dueMonths = [];

            while ($cursor->format('Y-m') <= $currentMonthStr) {
                $mk = $cursor->format('Y-m');
                if (($paid[$mk] ?? 0) < $rent) {
                    $dueMonths[] = $mk;
                }
                $cursor->modify('+1 month');
            }

            $debt = 0.0;
            foreach ($dueMonths as $mk) {
                $debt += max($rent - ($paid[$mk] ?? 0), 0);
            }
            $advance = count(array_filter(array_keys($paid), fn($m) => $m > $currentMonthStr));

            $situations[$l['id']] = [
                'due_months' => $dueMonths,
                'debt'       => $debt,
                'advance'    => $advance,
            ];
            $totalDebt += $debt;
        }

        // Owners with computed stats (based on active leases only for revenue/debt)
        $ownersWithStats = [];
        foreach ($owners as $o) {
            $ownedAptIds = array_map(
                fn($a) => $a['id'],
                array_filter($apartments, function ($a) use ($o, $properties) {
                    if ($a['owner_id'] === $o['id']) return true;
                    if ($a['property_id']) {
                        foreach ($properties as $p) {
                            if ($p['id'] === $a['property_id'] && $p['owner_id'] === $o['id']) return true;
                        }
                    }
                    return false;
                })
            );
            $ownedLeases    = array_filter($activeLeases, fn($l) => in_array($l['apartment_id'], $ownedAptIds, true));
            $monthlyRevenue = array_sum(array_map(fn($l) => (float) $l['monthly_rent'], $ownedLeases));
            $ownerDebt      = array_sum(array_map(fn($l) => $situations[$l['id']]['debt'] ?? 0, $ownedLeases));
            $commission     = ($o['commission_rate'] ?? 0) > 0
                ? ($monthlyRevenue * (float) $o['commission_rate']) / 100
                : 0;

            $ownersWithStats[] = array_merge($o, [
                'apt_count'       => count($ownedAptIds),
                'lease_count'     => count($ownedLeases),
                'monthly_revenue' => $monthlyRevenue,
                'owner_debt'      => $ownerDebt,
                'commission'      => $commission,
                'net'             => $monthlyRevenue - $commission,
            ]);
        }

        // Historique des loyers (60 derniers validés)
        $lastPayments = RentPayment::listAll(['status' => 'validee'], 1, 60);

        $this->view('app/immobilier/index', [
            'pageTitle'       => 'Immobilier — appartements & loyers',
            'apartments'      => $apartments,
            'properties'      => $properties,
            'owners'          => $owners,
            'ownersWithStats' => $ownersWithStats,
            'leases'          => $leases,
            'pending'         => $pending,
            'accounts'        => $accounts,
            'situations'      => $situations,
            'lastPayments'    => $lastPayments['rows'],
            'propertySelect'      => Property::forSelect(),
            'apartmentSelect'     => Apartment::forSelect(),
            'freeApartmentSelect' => Apartment::freeForSelect(),
            'stats' => [
                'apartments_occupied' => $occupiedCount,
                'apartments_total'    => $totalAptCount,
                'encaisse_this_month' => $encaisseThisMonth,
                'total_debt'          => $totalDebt,
                'nb_attente'          => count($pending),
            ],
        ]);
    }

    // ─── Propriétaires ────────────────────────────────────────────────

    public function storeOwner(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();

        $name = trim($r->input('full_name', ''));
        if (!$name) {
            Session::flash('error', 'Le nom du propriétaire est requis.');
            $this->redirect('/app/immobilier');
        }
        PropertyOwner::createOwner([
            'full_name'       => $name,
            'owner_type'      => $r->input('owner_type', 'particulier'),
            'phone'           => trim($r->input('phone', '')) ?: null,
            'email'           => trim($r->input('email', '')) ?: null,
            'address'         => trim($r->input('address', '')) ?: null,
            'city'            => trim($r->input('city', '')) ?: null,
            'commission_rate' => (float) $r->input('commission_rate', 0),
            'notes'           => trim($r->input('notes', '')) ?: null,
            'created_by'      => Auth::id(),
        ]);
        Session::flash('success', 'Propriétaire créé.');
        $this->redirect('/app/immobilier');
    }

    public function updateOwner(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id = $r->param('id', '');

        $name = trim($r->input('full_name', ''));
        if (!$name) {
            Session::flash('error', 'Le nom du propriétaire est requis.');
            $this->redirect('/app/immobilier');
        }
        PropertyOwner::updateOwner($id, [
            'full_name'       => $name,
            'owner_type'      => $r->input('owner_type', 'particulier'),
            'phone'           => trim($r->input('phone', '')) ?: null,
            'email'           => trim($r->input('email', '')) ?: null,
            'address'         => trim($r->input('address', '')) ?: null,
            'city'            => trim($r->input('city', '')) ?: null,
            'commission_rate' => (float) $r->input('commission_rate', 0),
            'notes'           => trim($r->input('notes', '')) ?: null,
            'is_active'       => 1,
        ]);
        Session::flash('success', 'Propriétaire modifié.');
        $this->redirect('/app/immobilier');
    }

    public function destroyOwner(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        PropertyOwner::deleteOwner($r->param('id', ''));
        Session::flash('success', 'Propriétaire archivé.');
        $this->redirect('/app/immobilier');
    }

    // ─── Propriétés ───────────────────────────────────────────────────

    public function storeProperty(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();

        $name = trim($r->input('name', ''));
        if (!$name) {
            Session::flash('error', 'Le nom du bâtiment est requis.');
            $this->redirect('/app/immobilier');
        }
        Property::createProperty([
            'name'     => $name,
            'address'  => trim($r->input('address', '')) ?: null,
            'city'     => trim($r->input('city', '')) ?: null,
            'notes'    => trim($r->input('notes', '')) ?: null,
            'owner_id' => $r->input('owner_id') ?: null,
        ]);
        Session::flash('success', 'Propriété créée.');
        $this->redirect('/app/immobilier');
    }

    public function updateProperty(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id   = $r->param('id', '');
        $name = trim($r->input('name', ''));
        if (!$name) {
            Session::flash('error', 'Le nom du bâtiment est requis.');
            $this->redirect('/app/immobilier');
        }
        Property::updateProperty($id, [
            'name'     => $name,
            'address'  => trim($r->input('address', '')) ?: null,
            'city'     => trim($r->input('city', '')) ?: null,
            'notes'    => trim($r->input('notes', '')) ?: null,
            'owner_id' => $r->input('owner_id') ?: null,
        ]);
        Session::flash('success', 'Propriété modifiée.');
        $this->redirect('/app/immobilier');
    }

    public function destroyProperty(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        Property::deleteProperty($r->param('id', ''));
        Session::flash('success', 'Propriété archivée.');
        $this->redirect('/app/immobilier');
    }

    // ─── Appartements ─────────────────────────────────────────────────

    public function storeApartment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();

        $label = trim($r->input('label', ''));
        if (!$label) {
            Session::flash('error', 'Le nom de l\'appartement est requis.');
            $this->redirect('/app/immobilier');
        }
        // Auto-generate code if not provided (like React)
        $code = trim($r->input('code', ''));
        if (!$code) {
            $propertyId = $r->input('property_id') ?: '';
            $prefix     = $propertyId ? strtoupper(substr($propertyId, 0, 3)) : 'APT';
            $code       = $prefix . '-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 4));
        }
        Apartment::createApartment([
            'label'        => $label,
            'code'         => $code,
            'property_id'  => $r->input('property_id') ?: null,
            'owner_id'     => $r->input('owner_id')   ?: null,
            'monthly_rent' => (float) $r->input('monthly_rent', 0),
            'currency'     => 'GNF',
            'rooms'        => $r->input('rooms') ? (int) $r->input('rooms') : null,
            'status'       => 'libre',
        ]);
        Session::flash('success', 'Appartement créé.');
        $this->redirect('/app/immobilier');
    }

    public function updateApartment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id    = $r->param('id', '');
        $label = trim($r->input('label', ''));
        if (!$label) {
            Session::flash('error', 'Le nom de l\'appartement est requis.');
            $this->redirect('/app/immobilier');
        }
        Apartment::updateApartment($id, [
            'label'        => $label,
            'code'         => trim($r->input('code', '')) ?: null,
            'property_id'  => $r->input('property_id') ?: null,
            'owner_id'     => $r->input('owner_id')   ?: null,
            'monthly_rent' => (float) $r->input('monthly_rent', 0),
            'rooms'        => $r->input('rooms') ? (int) $r->input('rooms') : null,
            'status'       => $r->input('status', 'libre'),
        ]);
        Session::flash('success', 'Appartement modifié.');
        $this->redirect('/app/immobilier');
    }

    public function destroyApartment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        Apartment::deleteApartment($r->param('id', ''));
        Session::flash('success', 'Appartement supprimé.');
        $this->redirect('/app/immobilier');
    }

    // ─── Baux ─────────────────────────────────────────────────────────

    public function storeLease(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();

        $apartmentId = $r->input('apartment_id', '');
        $tenantName  = trim($r->input('tenant_name', ''));
        $startMonth  = $r->input('start_month', '');
        $rent        = (float) $r->input('monthly_rent', 0);

        if (!$apartmentId || !$tenantName || !$startMonth || $rent <= 0) {
            Session::flash('error', 'Champs obligatoires manquants pour créer le bail.');
            $this->redirect('/app/immobilier');
        }

        // Guard: refuse if this apartment already has an active lease
        $existing = Database::getInstance()
            ->prepare("SELECT id FROM leases WHERE apartment_id = ? AND status = 'actif' LIMIT 1");
        $existing->execute([$apartmentId]);
        if ($existing->fetch()) {
            Session::flash('error', 'Ce logement est déjà attribué à un locataire actif.');
            $this->redirect('/app/immobilier');
        }

        $tenantPhone  = trim($r->input('tenant_phone', '')) ?: null;
        $accessCode   = $r->input('access_code') ?: 'LOC-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));

        // Auto-create tenant account if phone provided
        $tenantUserId = null;
        $accountMsg   = '';
        if ($tenantPhone) {
            $wasCreated   = false;
            $tenantUserId = Auth::findOrCreateByPhone($tenantPhone, $tenantName, $wasCreated);
            if ($wasCreated) {
                $accountMsg = ' Compte locataire créé (tél : ' . $tenantPhone . ', mot de passe par défaut : 12345678).';
            } else {
                $accountMsg = ' Compte existant rattaché au bail.';
            }
        }

        Lease::createLease([
            'apartment_id'   => $apartmentId,
            'tenant_name'    => $tenantName,
            'tenant_phone'   => $tenantPhone,
            'tenant_email'   => trim($r->input('tenant_email', '')) ?: null,
            'tenant_user_id' => $tenantUserId,
            'monthly_rent'   => $rent,
            'currency'       => 'GNF',
            'deposit'        => (float) $r->input('deposit', 0),
            'start_month'    => $startMonth . '-01',
            'end_date'       => $r->input('end_date') ?: null,
            'access_code'    => $accessCode,
            'status'         => 'actif',
            'notes'          => trim($r->input('notes', '')) ?: null,
            'created_by'     => Auth::id(),
        ]);

        Notification::notifyRole(
            ['super_admin', 'admin'],
            'Nouveau bail créé',
            $tenantName . ' — loyer ' . number_format($rent, 0, ',', ' ') . ' GNF/mois à partir de ' . $startMonth . '.',
            'info', 'immobilier', '/app/immobilier',
            Auth::id()
        );

        Session::flash('success', 'Bail créé. Code d\'accès : ' . $accessCode . '.' . $accountMsg);
        $this->redirect('/app/immobilier');
    }

    public function updateLease(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id         = $r->param('id', '');
        $tenantName = trim($r->input('tenant_name', ''));
        $startMonth = $r->input('start_month', '');
        $rent       = (float) $r->input('monthly_rent', 0);

        if (!$tenantName || !$startMonth || $rent <= 0) {
            Session::flash('error', 'Champs obligatoires manquants.');
            $this->redirect('/app/immobilier');
        }
        $code = trim($r->input('access_code', ''));
        if (!$code) {
            $code = 'LOC-' . strtoupper(substr(str_shuffle('ABCDEFGHJKLMNPQRSTUVWXYZ23456789'), 0, 6));
        }
        Lease::updateLease($id, [
            'apartment_id'  => $r->input('apartment_id', ''),
            'tenant_name'   => $tenantName,
            'tenant_phone'  => trim($r->input('tenant_phone', '')) ?: null,
            'tenant_email'  => trim($r->input('tenant_email', '')) ?: null,
            'monthly_rent'  => $rent,
            'deposit'       => (float) $r->input('deposit', 0),
            'start_month'   => strlen($startMonth) === 7 ? $startMonth . '-01' : $startMonth,
            'end_date'      => $r->input('end_date') ?: null,
            'access_code'   => $code,
            'status'        => $r->input('status', 'actif'),
            'notes'         => trim($r->input('notes', '')) ?: null,
        ]);
        Session::flash('success', 'Bail modifié.');
        $this->redirect('/app/immobilier');
    }

    public function destroyLease(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        Lease::deleteLease($r->param('id', ''));
        Session::flash('success', 'Bail supprimé.');
        $this->redirect('/app/immobilier');
    }

    // ─── Paiements loyers (staff) ─────────────────────────────────────

    public function storePayment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();

        $leaseId = $r->input('lease_id', '');
        $period  = $r->input('period_month', '');
        $amount  = (float) $r->input('amount', 0);

        if (!$leaseId || !$period || $amount <= 0) {
            Session::flash('error', 'Champs obligatoires manquants.');
            $this->redirect('/app/paiements-loyers');
        }
        $lease = Lease::find($leaseId);
        if (!$lease) {
            Session::flash('error', 'Bail introuvable.');
            $this->redirect('/app/paiements-loyers');
        }
        RentPayment::createPayment([
            'lease_id'           => $leaseId,
            'apartment_id'       => $lease['apartment_id'],
            'period_month'       => $period . '-01',
            'amount'             => $amount,
            'currency'           => 'GNF',
            'payment_method'     => $r->input('payment_method', 'especes'),
            'payer_phone'        => trim($r->input('payer_phone', '')) ?: null,
            'provider_reference' => trim($r->input('provider_reference', '')) ?: null,
            'paid_at'            => ($r->input('paid_at') ?: date('Y-m-d')) . ' 00:00:00',
            'status'             => 'validee',
            'notes'              => trim($r->input('notes', '')) ?: null,
            'account_id'         => $r->input('account_id') ?: null,
            'declared_by'        => Auth::id(),
        ]);
        Session::flash('success', 'Loyer encaissé et ajouté à la caisse.');
        $this->redirect('/app/paiements-loyers');
    }

    // ─── Page paiements loyers ─────────────────────────────────────────

    public function loyers(Request $r): void
    {
        $this->requireImmo();

        $filters = [
            'status'       => $r->input('status', ''),
            'month'        => $r->input('month', ''),
            'owner_id'     => $r->input('owner_id', ''),
            'apartment_id' => $r->input('apartment_id', ''),
            'q'            => trim($r->input('q', '')),
            'date_from'    => $r->input('date_from', ''),
            'date_to'      => $r->input('date_to', ''),
        ];
        $page    = max(1, (int) $r->input('page', 1));
        $paged   = RentPayment::listAll($filters, $page, 50);
        $pending = RentPayment::pendingList();
        $stats   = RentPayment::stats();
        $leases  = Lease::allActive();
        $owners  = PropertyOwner::forSelect();
        $accounts = FinancialAccount::allForSelect();

        // Build lease map for "total dû / reste" calculation
        $leaseMap = [];
        foreach ($leases as $l) {
            $leaseMap[$l['id']] = $l;
        }

        // Validated payments grouped by (lease_id, month) for "reste à payer"
        $validatedByLease = RentPayment::validatedByLease();

        $this->view('app/immobilier/loyers', [
            'pageTitle'        => 'Gestion des paiements',
            'payments'         => $paged['rows'],
            'pending'          => $pending,
            'stats'            => $stats,
            'leases'           => $leases,
            'leaseMap'         => $leaseMap,
            'validatedByLease' => $validatedByLease,
            'owners'           => $owners,
            'accounts'         => $accounts,
            'filters'          => $filters,
            'page'             => $page,
            'total'            => $paged['total'],
        ]);
    }

    public function validatePayment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id = $r->param('id', '');
        if (!RentPayment::findById($id)) {
            Session::flash('error', 'Paiement introuvable.');
            $this->redirect('/app/paiements-loyers');
        }
        $payment = RentPayment::findById($id);
        RentPayment::approuver($id, Auth::id());

        // Notify tenant if they have a user account linked to this lease
        if ($payment) {
            $lease = Lease::find($payment['lease_id'] ?? '');
            if ($lease && !empty($lease['tenant_user_id'])) {
                $period = $payment['period_month'] ? date('m/Y', strtotime($payment['period_month'])) : '';
                Notification::notifyUser(
                    $lease['tenant_user_id'],
                    'Loyer validé',
                    'Votre paiement de ' . number_format((float)$payment['amount'], 0, ',', ' ') . ' GNF' . ($period ? ' (mois ' . $period . ')' : '') . ' a été confirmé.',
                    'succes', 'immobilier', '/app/mon-logement'
                );
            }
        }

        Session::flash('success', 'Paiement validé.');
        $this->redirect('/app/paiements-loyers');
    }

    public function refusePayment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        RentPayment::refuser($r->param('id', ''));
        Session::flash('success', 'Paiement refusé.');
        $this->redirect('/app/paiements-loyers');
    }

    public function editPayment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id = $r->param('id', '');
        RentPayment::updatePayment($id, [
            'paid_at'            => $r->input('paid_at') ?: date('Y-m-d'),
            'provider_reference' => trim($r->input('provider_reference', '')) ?: null,
            'notes'              => trim($r->input('notes', '')) ?: null,
        ]);
        Session::flash('success', 'Paiement modifié.');
        $this->redirect('/app/paiements-loyers');
    }

    public function destroyPayment(Request $r): void
    {
        $this->requireImmo();
        Csrf::check();
        $id      = $r->param('id', '');
        $payment = RentPayment::findById($id);
        if ($payment && $payment['status'] === 'validee') {
            Session::flash('error', 'Suppression impossible : ce paiement est déjà validé en caisse.');
            $this->redirect('/app/paiements-loyers');
        }
        RentPayment::deleteById($id);
        Session::flash('success', 'Paiement supprimé.');
        $this->redirect('/app/paiements-loyers');
    }

    // ─── Rapports CSV ─────────────────────────────────────────────────

    public function exportRapport(Request $r): void
    {
        $this->requireImmo();

        $type    = $r->input('type', 'synthese');
        $ownerId = $r->input('owner_id', '');
        $pdo     = Database::getInstance();

        $filename = 'rapport-' . $type . '-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

        $out = fopen('php://output', 'w');

        switch ($type) {
            case 'synthese':
                fputcsv($out, ['Propriétaire', 'Type', 'Appartements', 'Baux actifs', 'Loyers/mois (GNF)', 'Commission %', 'Commission (GNF)', 'Net (GNF)'], ';');
                if ($ownerId) {
                    $oStmt = $pdo->prepare("SELECT * FROM property_owners WHERE id = ? AND is_active = 1");
                    $oStmt->execute([$ownerId]);
                    $exportOwners = $oStmt->fetchAll();
                } else {
                    $exportOwners = $pdo->query("SELECT * FROM property_owners WHERE is_active = 1 ORDER BY full_name")->fetchAll();
                }
                $aptStmt   = $pdo->prepare("SELECT COUNT(*) FROM apartments a LEFT JOIN properties p ON p.id=a.property_id WHERE a.owner_id=? OR p.owner_id=?");
                $leaseStmt = $pdo->prepare("SELECT SUM(l.monthly_rent) AS rev, COUNT(l.id) AS cnt FROM leases l JOIN apartments a ON a.id=l.apartment_id LEFT JOIN properties p ON p.id=a.property_id WHERE l.status='actif' AND (a.owner_id=? OR p.owner_id=?)");
                foreach ($exportOwners as $o) {
                    $aptStmt->execute([$o['id'], $o['id']]);
                    $aptCount  = (int) $aptStmt->fetchColumn();
                    $leaseStmt->execute([$o['id'], $o['id']]);
                    $leaseData = $leaseStmt->fetch();
                    $rev = (float) ($leaseData['rev'] ?? 0);
                    $com = $rev * ((float) ($o['commission_rate'] ?? 0)) / 100;
                    fputcsv($out, [$o['full_name'], $o['owner_type'] ?? '', $aptCount, $leaseData['cnt'] ?? 0, number_format($rev, 0, ',', ' '), $o['commission_rate'] ?? 0, number_format($com, 0, ',', ' '), number_format($rev - $com, 0, ',', ' ')], ';');
                }
                break;

            case 'loyers':
                fputcsv($out, ['Date', 'Référence', 'Locataire', 'Appartement', 'Propriété', 'Mois', 'Montant (GNF)', 'Méthode', 'Statut'], ';');
                $loySql = "SELECT rp.*, a.label AS apt_label, a.code AS apt_code, p.name AS prop_name, l.tenant_name
                           FROM rent_payments rp
                           JOIN apartments a ON a.id = rp.apartment_id
                           LEFT JOIN properties p ON p.id = a.property_id
                           LEFT JOIN leases l ON l.id = rp.lease_id
                           WHERE rp.status != 'refusee'";
                if ($ownerId) {
                    $loyStmt = $pdo->prepare($loySql . " AND (a.owner_id = ? OR p.owner_id = ?) ORDER BY rp.paid_at DESC LIMIT 5000");
                    $loyStmt->execute([$ownerId, $ownerId]);
                } else {
                    $loyStmt = $pdo->query($loySql . " ORDER BY rp.paid_at DESC LIMIT 5000");
                }
                foreach ($loyStmt->fetchAll() as $row) {
                    $ref = $row['provider_reference'] ?: 'CANDI-' . strtoupper(substr($row['id'], 0, 8));
                    fputcsv($out, [date('d/m/Y', strtotime($row['paid_at'])), $ref, $row['tenant_name'] ?? '', $row['apt_code'] ? $row['apt_code'] . ' · ' . $row['apt_label'] : $row['apt_label'], $row['prop_name'] ?? '', date('m/Y', strtotime($row['period_month'])), $row['amount'], $row['payment_method'], $row['status']], ';');
                }
                break;

            case 'dettes':
                fputcsv($out, ['Locataire', 'Téléphone', 'Appartement', 'Propriété', 'Loyer mensuel (GNF)', 'Mois dus', 'Dette totale (GNF)'], ';');
                $exportLeases     = Lease::allActive();
                $validatedByLease = RentPayment::validatedByLease();
                $now              = new \DateTime();
                $currentMonthStr  = $now->format('Y-m');
                foreach ($exportLeases as $l) {
                    $rent      = (float) $l['monthly_rent'];
                    $startDate = new \DateTime($l['start_month']);
                    $cursor    = clone $startDate;
                    $paid      = $validatedByLease[$l['id']] ?? [];
                    $dueMonths = [];
                    while ($cursor->format('Y-m') <= $currentMonthStr) {
                        $mk = $cursor->format('Y-m');
                        if (($paid[$mk] ?? 0) < $rent) $dueMonths[] = $mk;
                        $cursor->modify('+1 month');
                    }
                    if (empty($dueMonths)) continue;
                    $debt = array_sum(array_map(fn($m) => max($rent - ($paid[$m] ?? 0), 0), $dueMonths));
                    fputcsv($out, [$l['tenant_name'], $l['tenant_phone'] ?? '', $l['apartment_code'] ? $l['apartment_code'] . ' · ' . $l['apartment_label'] : $l['apartment_label'], $l['property_name'] ?? '', $rent, count($dueMonths), $debt], ';');
                }
                break;

            case 'biens':
                fputcsv($out, ['Propriété', 'Ville', 'Propriétaire', 'Appartement', 'Code', 'Statut', 'Loyer mensuel (GNF)', 'Locataire', 'Début bail'], ';');
                $bienSql = "SELECT a.*, p.name AS prop_name, p.city AS prop_city,
                                   po.full_name AS owner_name,
                                   l.tenant_name, l.start_month AS lease_start, l.monthly_rent AS lease_rent
                            FROM apartments a
                            LEFT JOIN properties p ON p.id = a.property_id
                            LEFT JOIN property_owners po ON (po.id = a.owner_id OR po.id = p.owner_id)
                            LEFT JOIN leases l ON l.apartment_id = a.id AND l.status = 'actif'";
                if ($ownerId) {
                    $bienStmt = $pdo->prepare($bienSql . " WHERE a.owner_id = ? OR p.owner_id = ? ORDER BY p.name, a.label");
                    $bienStmt->execute([$ownerId, $ownerId]);
                } else {
                    $bienStmt = $pdo->query($bienSql . " ORDER BY p.name, a.label");
                }
                $statusLbls = ['libre' => 'Libre', 'occupe' => 'Occupé', 'maintenance' => 'Maintenance', 'indisponible' => 'Indisponible'];
                foreach ($bienStmt->fetchAll() as $row) {
                    fputcsv($out, [$row['prop_name'] ?? '—', $row['prop_city'] ?? '', $row['owner_name'] ?? '', $row['label'], $row['code'] ?? '', $statusLbls[$row['status']] ?? $row['status'], $row['lease_rent'] ?? $row['monthly_rent'], $row['tenant_name'] ?? '', $row['lease_start'] ? date('m/Y', strtotime($row['lease_start'])) : ''], ';');
                }
                break;
        }

        fclose($out);
        exit;
    }

    // ─── Espace locataire ──────────────────────────────────────────────

    public function monLogement(Request $r): void
    {
        $userId   = Auth::id();
        $lease    = Lease::findByTenantUser($userId);
        $payments = $lease ? RentPayment::forLease($lease['id']) : [];

        $this->view('app/immobilier/mon-logement', [
            'pageTitle' => 'Mon logement',
            'lease'     => $lease,
            'payments'  => $payments,
        ]);
    }

    public function claimLease(Request $r): void
    {
        Csrf::check();
        $code = strtoupper(trim($r->input('code', '')));
        if (strlen($code) < 4) {
            Session::flash('error', 'Code trop court.');
            $this->redirect('/app/mon-logement');
        }
        $lease = Lease::findByAccessCode($code);
        if (!$lease) {
            Session::flash('error', 'Code invalide. Vérifiez le code communiqué par la gestion.');
            $this->redirect('/app/mon-logement');
        }
        if (!empty($lease['tenant_user_id'])) {
            if ($lease['tenant_user_id'] === Auth::id()) {
                // Already linked to this user (auto-created at lease creation)
                Session::flash('success', 'Ce logement est déjà rattaché à votre compte.');
            } else {
                Session::flash('error', 'Ce code est déjà utilisé par un autre compte.');
            }
            $this->redirect('/app/mon-logement');
        }
        Lease::linkTenantUser($lease['id'], Auth::id());
        Session::flash('success', 'Votre appartement a été rattaché à votre compte.');
        $this->redirect('/app/mon-logement');
    }

    public function storeTenantPayment(Request $r): void
    {
        Csrf::check();
        $userId = Auth::id();
        $lease  = Lease::findByTenantUser($userId);
        if (!$lease) {
            Session::flash('error', 'Aucun bail actif trouvé.');
            $this->redirect('/app/mon-logement');
        }

        $period = $r->input('period_month', '');
        $amount = (float) $r->input('amount', 0);
        if (!$period || $amount <= 0) {
            Session::flash('error', 'Mois et montant obligatoires.');
            $this->redirect('/app/mon-logement');
        }

        RentPayment::createPayment([
            'lease_id'           => $lease['id'],
            'apartment_id'       => $lease['apartment_id'],
            'period_month'       => $period . '-01',
            'amount'             => $amount,
            'currency'           => 'GNF',
            'payment_method'     => $r->input('payment_method', 'orange_money'),
            'payer_phone'        => trim($r->input('payer_phone', '')) ?: null,
            'provider_reference' => trim($r->input('provider_reference', '')) ?: null,
            'paid_at'            => ($r->input('paid_at') ?: date('Y-m-d')) . ' 00:00:00',
            'status'             => 'en_attente',
            'notes'              => trim($r->input('notes', '')) ?: null,
            'declared_by'        => $userId,
        ]);
        // Notify gestionnaire_immobilier and admins that a tenant declared a payment
        $apt = Apartment::find($lease['apartment_id'] ?? '');
        $aptLabel = $apt['label'] ?? ('Appt. ' . ($lease['apartment_id'] ?? ''));
        $periodFmt = $period ? date('m/Y', strtotime($period . '-01')) : '';
        Notification::notifyRole(
            ['super_admin', 'admin', 'gestionnaire_immobilier'],
            'Paiement loyer à valider',
            ($lease['tenant_name'] ?? 'Un locataire') . ' a déclaré un paiement de ' . number_format($amount, 0, ',', ' ') . ' GNF pour ' . $aptLabel . ($periodFmt ? ' (' . $periodFmt . ')' : '') . '.',
            'alerte', 'immobilier', '/app/paiements-loyers',
            $userId
        );

        Session::flash('success', 'Paiement déclaré. La gestion va le vérifier et le valider.');
        $this->redirect('/app/mon-logement');
    }

    public function quittance(Request $r): void
    {
        $id  = $r->param('id', '');
        $pdo = Database::getInstance();

        $stmt = $pdo->prepare(
            "SELECT rp.*,
                    a.label AS apartment_label, a.code AS apartment_code,
                    l.tenant_name, l.tenant_phone, l.monthly_rent,
                    po.full_name AS owner_name
             FROM rent_payments rp
             JOIN apartments a ON a.id = rp.apartment_id
             LEFT JOIN leases l ON l.id = rp.lease_id
             LEFT JOIN property_owners po
               ON po.id = COALESCE(a.owner_id,
                  (SELECT p.owner_id FROM properties p WHERE p.id = a.property_id LIMIT 1))
             WHERE rp.id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        $payment = $stmt->fetch();

        if (!$payment || $payment['status'] !== 'validee') {
            Session::flash('error', 'Quittance disponible uniquement pour les paiements validés.');
            $this->redirect('/app/paiements-loyers');
        }

        $role    = Auth::role() ?? 'client';
        $isStaff = !in_array($role, ['client'], true);
        if (!$isStaff) {
            $leaseStmt = $pdo->prepare("SELECT user_id FROM leases WHERE id = ? LIMIT 1");
            $leaseStmt->execute([$payment['lease_id'] ?? '']);
            $lr = $leaseStmt->fetch();
            if (!$lr || $lr['user_id'] !== Auth::id()) $this->abort(403);
        }

        $this->view('app/immobilier/quittance', [
            'pageTitle' => 'Quittance de loyer — CaNDI',
            'payment'   => $payment,
        ], null);
    }

    // ─── Stubs compatibles router ──────────────────────────────────────

    public function show(Request $r): void        { $this->abort(404); }
    public function create(Request $r): void      { $this->redirect('/app/immobilier'); }
    public function store(Request $r): void       { $this->redirect('/app/immobilier'); }
    public function edit(Request $r): void        { $this->redirect('/app/immobilier'); }
    public function update(Request $r): void      { $this->redirect('/app/immobilier'); }
    public function destroy(Request $r): void     { $this->redirect('/app/immobilier'); }
    public function initiate(Request $r): void    { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void   { $this->redirect('/app/immobilier'); }
    public function toggle(Request $r): void      { $this->redirect('/app/immobilier'); }
    public function updateRole(Request $r): void  { $this->redirect('/app/immobilier'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void    { $this->redirect('/app/notifications'); }

    private function requireImmo(): void
    {
        if (!Auth::hasRole(self::IMMO_ROLES)) {
            $this->redirect('/app/tableau-de-bord');
        }
    }
}
