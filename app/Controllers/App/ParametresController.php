<?php

namespace App\Controllers\App;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Database;
use App\Core\Request;
use App\Core\Session;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\SettingsOption;
use App\Services\NimbaSms;

class ParametresController extends AppController
{
    private const OPTION_CATEGORIES = [
        ['category' => 'payment_method',     'title' => 'Moyens de paiement',          'description' => 'Espèces, virement…'],
        ['category' => 'client_type',        'title' => 'Types de client',             'description' => 'Particulier, entreprise, ONG…'],
        ['category' => 'client_sector',      'title' => 'Secteurs d\'activité',        'description' => 'Éducation, santé, commerce…'],
        ['category' => 'program_level',      'title' => 'Niveaux de formation',        'description' => 'Débutant, intermédiaire, avancé'],
        ['category' => 'account_type',       'title' => 'Types de compte',             'description' => 'Caisse, banque, mobile money'],
        ['category' => 'task_module',        'title' => 'Modules de tâches',           'description' => 'Rattachement des tâches internes'],
        ['category' => 'apartment_status',   'title' => 'États des appartements',      'description' => 'Libre, occupé, en maintenance'],
        ['category' => 'lease_status',       'title' => 'Statuts de bail',             'description' => 'Actif, suspendu, terminé'],
        ['category' => 'offering_kind',      'title' => 'Types d\'offre',              'description' => 'Service, plateforme / produit'],
    ];

    public function index(Request $r): void
    {
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/tableau-de-bord');
        }
        $grouped = SettingsOption::allGrouped();
        $this->view('app/parametres/index', [
            'pageTitle'        => 'Paramétrage de la plateforme',
            'grouped'          => $grouped,
            'optionCategories' => self::OPTION_CATEGORIES,
            'cinetpay'         => $this->getCinetPayRow(),
            'nimba'            => $this->getNimbaRow(),
            'isSuperAdmin'     => Auth::hasRole('super_admin'),
            'finAccounts'      => FinancialAccount::allActive(),
            'finCategories'    => FinancialCategory::allActive(),
        ]);
    }

    public function update(Request $r): void
    {
        Csrf::check();
        if (!Auth::hasRole(['super_admin', 'admin'])) {
            $this->redirect('/app/parametres');
        }
        // Dispatch action
        $action = $r->input('_action', '');
        match ($action) {
            'cinetpay'        => $this->saveCinetPay($r),
            'nimba_sms'       => $this->saveNimba($r),
            'test_sms'        => $this->testSms($r),
            'add_option'      => $this->addOption($r),
            'toggle_option'   => $this->toggleOption($r),
            'delete_option'   => $this->deleteOption($r),
            'add_account'     => $this->addAccount($r),
            'delete_account'  => $this->deleteAccount($r),
            'add_fin_cat'     => $this->addFinCategory($r),
            'delete_fin_cat'  => $this->deleteFinCategory($r),
            default           => null,
        };
        $this->redirect('/app/parametres');
    }

    private function saveCinetPay(Request $r): void
    {
        if (!Auth::hasRole('super_admin')) return;
        $pdo = Database::getInstance();
        $row = $this->getCinetPayRow();
        $isEnabled  = $r->input('is_enabled') ? 1 : 0;
        $clientId   = trim($r->input('client_id', ''));
        $clientSecret = trim($r->input('client_secret', ''));
        $currency   = strtoupper(trim($r->input('currency', 'GNF')));
        $env        = $r->input('environment', 'sandbox') === 'production' ? 'production' : 'sandbox';

        if ($row) {
            $pdo->prepare(
                'UPDATE payment_gateway_settings SET client_id=?, client_secret=?, currency=?, environment=?, is_enabled=?, updated_by=?, updated_at=? WHERE provider="cinetpay"'
            )->execute([$clientId, $clientSecret, $currency, $env, $isEnabled, Auth::id(), date('Y-m-d H:i:s')]);
        } else {
            $pdo->prepare(
                'INSERT INTO payment_gateway_settings (provider, api_base_url, client_id, client_secret, currency, environment, is_enabled, updated_by, updated_at) VALUES ("cinetpay","https://api-checkout.cinetpay.com/v2",?,?,?,?,?,?,?)'
            )->execute([$clientId, $clientSecret, $currency, $env, $isEnabled, Auth::id(), date('Y-m-d H:i:s')]);
        }
        Session::flash('success', 'Configuration CinetPay sauvegardée.');
    }

    private function addOption(Request $r): void
    {
        $category = $r->input('category', '');
        $label    = trim($r->input('label', ''));
        if (!$label || !$category) return;
        $value = mb_strtolower($label, 'UTF-8');
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;
        $value = preg_replace('/[^a-z0-9]+/', '_', $value) ?? '';
        $value = trim($value, '_');
        $value = mb_substr($value, 0, 40);
        if (!$value) return;
        SettingsOption::createOption(compact('category', 'value', 'label'));
        Session::flash('success', 'Élément ajouté.');
    }

    private function toggleOption(Request $r): void
    {
        $id     = $r->input('option_id', '');
        $active = (bool) $r->input('is_active', 0);
        if ($id) SettingsOption::toggleActive($id, $active);
    }

    private function deleteOption(Request $r): void
    {
        $id = $r->input('option_id', '');
        if ($id) SettingsOption::delete($id);
        Session::flash('success', 'Élément supprimé.');
    }

    private function saveNimba(Request $r): void
    {
        if (!Auth::hasRole('super_admin')) return;
        $pdo       = Database::getInstance();
        $row       = $this->getNimbaRow();
        $isEnabled = $r->input('is_enabled') ? 1 : 0;
        $sid       = trim($r->input('sid', ''));
        $secret    = trim($r->input('token_nimba', ''));
        $sender    = trim($r->input('sender', 'CaNDI')) ?: 'CaNDI';
        $baseUrl   = trim($r->input('api_base_url', 'https://api.nimbasms.com')) ?: 'https://api.nimbasms.com';
        $now       = date('Y-m-d H:i:s');

        // Authorization Header optionnel : Basic base64(sid:secret)
        $authHeader = trim($r->input('auth_header', ''));
        if ($authHeader && (!$sid || !$secret)) {
            $decoded = base64_decode(preg_replace('/^Basic\s+/i', '', $authHeader));
            if ($decoded && str_contains($decoded, ':')) {
                [$sid, $secret] = explode(':', $decoded, 2);
            }
        }

        $fields = [
            'merchant_key' => $sender,
            'api_base_url' => $baseUrl,
            'is_enabled'   => $isEnabled,
            'updated_by'   => Auth::id(),
            'updated_at'   => $now,
        ];
        // Only update credentials if new values provided (not masked ••••)
        if ($sid && !str_starts_with($sid, '••••'))         $fields['client_id']     = $sid;
        if ($secret && !str_starts_with($secret, '••••'))   $fields['client_secret'] = $secret;

        if ($row) {
            $set  = implode(', ', array_map(fn($k) => "$k=?", array_keys($fields)));
            $pdo->prepare("UPDATE payment_gateway_settings SET $set WHERE provider='nimba_sms'")
                ->execute([...array_values($fields)]);
        } else {
            $fields['provider']    = 'nimba_sms';
            $fields['currency']    = 'GNF';
            $fields['environment'] = 'production';
            if (empty($fields['client_id']))     $fields['client_id']     = '';
            if (empty($fields['client_secret'])) $fields['client_secret'] = '';
            $cols = implode(', ', array_keys($fields));
            $plh  = implode(', ', array_fill(0, count($fields), '?'));
            $pdo->prepare("INSERT INTO payment_gateway_settings ($cols) VALUES ($plh)")
                ->execute(array_values($fields));
        }
        Session::flash('success', 'Configuration Nimba SMS sauvegardée.');
    }

    private function testSms(Request $r): void
    {
        if (!Auth::hasRole('super_admin')) return;
        $phone = trim($r->input('test_phone', ''));
        if (strlen($phone) < 8) {
            Session::flash('error', 'Numéro de téléphone invalide.');
            return;
        }
        $sent = NimbaSms::send($phone, 'Test CaNDI : la configuration Nimba SMS fonctionne correctement.');
        if ($sent) {
            Session::flash('sms_ok', 'SMS de test envoyé vers ' . $phone . '.');
        } else {
            Session::flash('sms_error', 'Échec de l\'envoi — vérifiez les identifiants Nimba SMS (détail dans les logs PHP).');
        }
    }

    private function getNimbaRow(): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM payment_gateway_settings WHERE provider = 'nimba_sms' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    private function addAccount(Request $r): void
    {
        $name = trim($r->input('acc_name', ''));
        if (!$name) { Session::flash('error', 'Nom du compte requis.'); return; }
        FinancialAccount::createAccount([
            'name'            => $name,
            'type'            => $r->input('acc_type', 'caisse'),
            'opening_balance' => (float) $r->input('acc_balance', 0),
        ]);
        Session::flash('success', 'Compte financier créé.');
    }

    private function deleteAccount(Request $r): void
    {
        $id = $r->input('account_id', '');
        if ($id) {
            Database::getInstance()->prepare(
                'UPDATE financial_accounts SET is_active=0, updated_at=NOW() WHERE id=?'
            )->execute([$id]);
            Session::flash('success', 'Compte désactivé.');
        }
    }

    private function addFinCategory(Request $r): void
    {
        $name = trim($r->input('fincat_name', ''));
        $dir  = $r->input('fincat_direction', 'entree');
        if (!$name) { Session::flash('error', 'Nom de la catégorie requis.'); return; }
        FinancialCategory::createCategory($name, $dir);
        Session::flash('success', 'Catégorie financière créée.');
    }

    private function deleteFinCategory(Request $r): void
    {
        $id = $r->input('fincat_id', '');
        if ($id) {
            Database::getInstance()->prepare(
                'UPDATE financial_categories SET is_active=0 WHERE id=?'
            )->execute([$id]);
            Session::flash('success', 'Catégorie désactivée.');
        }
    }

    private function getCinetPayRow(): ?array
    {
        $pdo  = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM payment_gateway_settings WHERE provider = 'cinetpay' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ?: null;
    }

    // Legacy stubs
    public function show(Request $r): void       { $this->abort(404); }
    public function create(Request $r): void     { $this->redirect('/app/parametres'); }
    public function store(Request $r): void      { $this->redirect('/app/parametres'); }
    public function edit(Request $r): void       { $this->redirect('/app/parametres'); }
    public function destroy(Request $r): void    { $this->redirect('/app/parametres'); }
    public function initiate(Request $r): void   { $this->redirect('/app/paiements'); }
    public function returnUrl(Request $r): void  { $this->redirect('/app/paiements'); }
    public function toggle(Request $r): void     { $this->redirect('/app/parametres'); }
    public function updateRole(Request $r): void { $this->redirect('/app/parametres'); }
    public function updatePassword(Request $r): void { $this->redirect('/app/profil'); }
    public function markRead(Request $r): void   { $this->redirect('/app/notifications'); }
    public function loyers(Request $r): void     { $this->redirect('/app/parametres'); }
    public function monLogement(Request $r): void { $this->redirect('/app/parametres'); }
}
