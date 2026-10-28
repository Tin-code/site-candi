<?php

use App\Controllers\HomeController;
use App\Controllers\AuthController;
use App\Controllers\FormationsController;
use App\Controllers\AProposController;
use App\Controllers\ContactController;
use App\Controllers\ProduitsServicesController;
use App\Controllers\ActualitesController;
use App\Controllers\App\DashboardController;
use App\Controllers\App\ProfilController;
use App\Controllers\App\InscriptionsController;
use App\Controllers\App\PaiementsController;
use App\Controllers\App\UtilisateursController;
use App\Controllers\App\FormationsAppController;
use App\Controllers\App\PublicationsController;
use App\Controllers\App\ParametresController;
use App\Controllers\App\EspaceApprenantController;
use App\Controllers\App\FinancesController;
use App\Controllers\App\CrmController;
use App\Controllers\App\ImmobilierController;
use App\Controllers\App\TachesController;
use App\Controllers\App\MessagesSmsController;
use App\Controllers\App\NotificationsController;
use App\Controllers\App\RapportsController;
use App\Controllers\App\AssistantController;
use App\Controllers\IkodiController;
use App\Controllers\Api\CinetPayWebhookController;
use App\Middleware\AuthMiddleware;
use App\Middleware\GuestMiddleware;
use App\Middleware\CsrfMiddleware;

/** @var \App\Core\Router $router */

// ============================================================
// Pages publiques
// ============================================================
$router->get('/', [HomeController::class, 'index']);
$router->get('/a-propos', [AProposController::class, 'index']);
$router->get('/formations', [FormationsController::class, 'index']);
$router->get('/produits-services', [ProduitsServicesController::class, 'index']);
$router->get('/actualites', [ActualitesController::class, 'index']);
$router->get('/actualites/{slug}', [ActualitesController::class, 'show']);
$router->get('/contact', [ContactController::class, 'index']);
$router->get('/ikodi',          [IkodiController::class, 'index']);
$router->post('/ikodi/demander',[IkodiController::class, 'ask']);

// ============================================================
// Authentification (invités seulement)
// ============================================================
$router->group('', function ($router) {
    $router->get('/auth', [AuthController::class, 'showLogin']);
    $router->post('/auth/login', [AuthController::class, 'login']);
    $router->post('/auth/register', [AuthController::class, 'register']);
    $router->post('/auth/otp/send', [AuthController::class, 'sendOtp']);
    $router->post('/auth/otp/verify', [AuthController::class, 'verifyOtp']);
    $router->get('/auth/reset', [AuthController::class, 'showReset']);
    $router->post('/auth/reset', [AuthController::class, 'sendReset']);
    $router->get('/auth/reset/{token}', [AuthController::class, 'showNewPassword']);
    $router->post('/auth/reset/{token}', [AuthController::class, 'setNewPassword']);
    $router->get('/confirmer-telephone', [AuthController::class, 'confirmPhone']);
    $router->post('/confirmer-telephone', [AuthController::class, 'verifyPhone']);
}, [GuestMiddleware::class]);

// Déconnexion (POST, authentifié)
$router->post('/auth/logout', [AuthController::class, 'logout']);

// ============================================================
// Demande d'inscription à une formation (public + auth)
// ============================================================
$router->post('/formations/{id}/inscrire', [FormationsController::class, 'enroll']);

// ============================================================
// Espace application (authentifié)
// ============================================================
$router->group('/app', function ($router) {

    $router->get('/tableau-de-bord', [DashboardController::class, 'index']);
    $router->get('/profil', [ProfilController::class, 'index']);
    $router->post('/profil', [ProfilController::class, 'update']);
    $router->post('/profil/password', [ProfilController::class, 'updatePassword']);

    // Inscriptions — literal routes BEFORE {id}
    $router->get('/inscriptions', [InscriptionsController::class, 'index']);
    $router->get('/inscriptions/creer', [InscriptionsController::class, 'create']);
    $router->post('/inscriptions', [InscriptionsController::class, 'store']);
    $router->get('/inscriptions/{id}', [InscriptionsController::class, 'show']);
    $router->post('/inscriptions/{id}/modifier', [InscriptionsController::class, 'update']);
    $router->post('/inscriptions/{id}/valider', [InscriptionsController::class, 'confirm']);
    $router->post('/inscriptions/{id}/refuser', [InscriptionsController::class, 'refuse']);

    // Paiements
    $router->get('/paiements', [PaiementsController::class, 'index']);
    $router->get('/paiements/{id}', [PaiementsController::class, 'show']);
    $router->post('/paiements/{id}/payer', [PaiementsController::class, 'initiate']);
    $router->get('/paiement-retour', [PaiementsController::class, 'returnUrl']);

    // Utilisateurs
    $router->get('/utilisateurs', [UtilisateursController::class, 'index']);
    $router->post('/utilisateurs', [UtilisateursController::class, 'store']);
    $router->post('/utilisateurs/{id}/toggle', [UtilisateursController::class, 'toggle']);
    $router->post('/utilisateurs/{id}/role', [UtilisateursController::class, 'updateRole']);
    $router->post('/utilisateurs/{id}/supprimer', [UtilisateursController::class, 'destroy']);

    // Formations (back-office)
    $router->get('/catalogue', [FormationsAppController::class, 'index']);
    // Formations (back-office catalogue) — literal routes BEFORE {id} to avoid conflict
    $router->get('/catalogue/creer', [FormationsAppController::class, 'create']);
    $router->get('/catalogue/sessions/{id}/modifier', [FormationsAppController::class, 'editSession']);
    $router->post('/catalogue/sessions/{id}', [FormationsAppController::class, 'updateSession']);
    $router->post('/catalogue/sessions/{id}/supprimer', [FormationsAppController::class, 'destroySession']);
    $router->get('/catalogue/{programId}/sessions/creer', [FormationsAppController::class, 'createSession']);
    $router->post('/catalogue/{programId}/sessions', [FormationsAppController::class, 'storeSession']);
    $router->get('/catalogue/{id}/modifier', [FormationsAppController::class, 'edit']);
    $router->post('/catalogue/{id}', [FormationsAppController::class, 'update']);
    $router->post('/catalogue/{id}/supprimer', [FormationsAppController::class, 'destroy']);
    $router->post('/catalogue', [FormationsAppController::class, 'store']);

    // Espace apprenant — literal routes BEFORE {id}
    $router->get('/espace-apprenant', [EspaceApprenantController::class, 'index']);
    $router->post('/espace-apprenant/materiaux', [EspaceApprenantController::class, 'uploadMaterial']);
    $router->post('/espace-apprenant/materiaux/{id}/supprimer', [EspaceApprenantController::class, 'destroyMaterial']);
    $router->get('/espace-apprenant/{id}/ics', [EspaceApprenantController::class, 'icsDownload']);
    $router->get('/espace-apprenant/{id}/attestation', [EspaceApprenantController::class, 'attestation']);

    // Publications — literal routes BEFORE {id}
    $router->get('/publications', [PublicationsController::class, 'index']);
    $router->get('/publications/creer', [PublicationsController::class, 'create']);
    $router->get('/publications/{id}/modifier', [PublicationsController::class, 'edit']);
    $router->post('/publications/{id}/toggle', [PublicationsController::class, 'toggle']);
    $router->post('/publications/{id}', [PublicationsController::class, 'update']);
    $router->post('/publications/{id}/supprimer', [PublicationsController::class, 'destroy']);
    $router->post('/publications', [PublicationsController::class, 'store']);

    // Finances — literal routes BEFORE {id}
    $router->get('/finances', [FinancesController::class, 'index']);
    $router->get('/finances/exporter', [FinancesController::class, 'exportJournal']);
    $router->post('/finances/encaisser-inscription', [FinancesController::class, 'cashierEnrollment']);
    $router->post('/finances/transferts', [FinancesController::class, 'storeTransfer']);
    $router->get('/finances/creer', [FinancesController::class, 'create']);
    $router->post('/finances/compte', [FinancesController::class, 'storeAccount']);
    $router->post('/finances/transferts/{id}/valider', [FinancesController::class, 'approveTransfer']);
    $router->post('/finances/transferts/{id}/refuser', [FinancesController::class, 'refuseTransfer']);
    $router->get('/finances/{id}/modifier', [FinancesController::class, 'edit']);
    $router->post('/finances/{id}/valider', [FinancesController::class, 'approuver']);
    $router->post('/finances/{id}/annuler', [FinancesController::class, 'cancel']);
    $router->post('/finances/{id}/supprimer', [FinancesController::class, 'destroy']);
    $router->post('/finances/{id}', [FinancesController::class, 'update']);
    $router->post('/finances', [FinancesController::class, 'store']);

    // CRM
    $router->get('/crm', [CrmController::class, 'index']);

    // Immobilier — literal routes BEFORE {id}
    $router->get('/immobilier', [ImmobilierController::class, 'index']);
    $router->get('/immobilier/rapports/export', [ImmobilierController::class, 'exportRapport']);
    $router->post('/immobilier/proprietaires', [ImmobilierController::class, 'storeOwner']);
    $router->post('/immobilier/proprietes', [ImmobilierController::class, 'storeProperty']);
    $router->post('/immobilier/appartements', [ImmobilierController::class, 'storeApartment']);
    $router->post('/immobilier/baux', [ImmobilierController::class, 'storeLease']);
    $router->post('/immobilier/paiements', [ImmobilierController::class, 'storePayment']);
    // Immobilier — {id} routes
    $router->post('/immobilier/proprietaires/{id}', [ImmobilierController::class, 'updateOwner']);
    $router->post('/immobilier/proprietaires/{id}/supprimer', [ImmobilierController::class, 'destroyOwner']);
    $router->post('/immobilier/proprietes/{id}', [ImmobilierController::class, 'updateProperty']);
    $router->post('/immobilier/proprietes/{id}/supprimer', [ImmobilierController::class, 'destroyProperty']);
    $router->post('/immobilier/appartements/{id}', [ImmobilierController::class, 'updateApartment']);
    $router->post('/immobilier/appartements/{id}/supprimer', [ImmobilierController::class, 'destroyApartment']);
    $router->post('/immobilier/baux/{id}', [ImmobilierController::class, 'updateLease']);
    $router->post('/immobilier/baux/{id}/supprimer', [ImmobilierController::class, 'destroyLease']);
    // Paiements loyers
    $router->get('/paiements-loyers', [ImmobilierController::class, 'loyers']);
    $router->post('/paiements-loyers/{id}/valider', [ImmobilierController::class, 'validatePayment']);
    $router->post('/paiements-loyers/{id}/refuser', [ImmobilierController::class, 'refusePayment']);
    $router->get('/paiements-loyers/{id}/quittance', [ImmobilierController::class, 'quittance']);
    $router->post('/paiements-loyers/{id}/modifier', [ImmobilierController::class, 'editPayment']);
    $router->post('/paiements-loyers/{id}/supprimer', [ImmobilierController::class, 'destroyPayment']);
    // Mon logement
    $router->get('/mon-logement', [ImmobilierController::class, 'monLogement']);
    $router->post('/mon-logement/relier', [ImmobilierController::class, 'claimLease']);
    $router->post('/mon-logement/paiement', [ImmobilierController::class, 'storeTenantPayment']);
    // CaNDI AI
    $router->get('/ai',         [AssistantController::class, 'index']);
    $router->post('/ai/demander', [AssistantController::class, 'ask']);

    // Tâches
    $router->get('/taches', [TachesController::class, 'index']);
    $router->post('/taches', [TachesController::class, 'store']);
    $router->post('/taches/{id}', [TachesController::class, 'update']);
    $router->post('/taches/{id}/supprimer', [TachesController::class, 'destroy']);

    // SMS automatiques
    $router->get('/messages-sms', [MessagesSmsController::class, 'index']);
    $router->post('/messages-sms/envoyer', [MessagesSmsController::class, 'dispatch']);

    // Notifications — literal routes BEFORE {id}
    $router->get('/notifications', [NotificationsController::class, 'index']);
    $router->post('/notifications/lire-tout', [NotificationsController::class, 'markAllRead']);
    $router->post('/notifications/supprimer-lues', [NotificationsController::class, 'deleteRead']);
    $router->post('/notifications/{id}/lire', [NotificationsController::class, 'markRead']);

    // Rapports
    $router->get('/rapports', [RapportsController::class, 'index']);
    $router->get('/rapports/export', [RapportsController::class, 'exportCsv']);

    // CRM
    $router->post('/crm/produits', [CrmController::class, 'storeProduct']);
    $router->post('/crm/produits/{id}', [CrmController::class, 'updateProduct']);
    $router->post('/crm/clients', [CrmController::class, 'storeClient']);
    $router->post('/crm/clients/{id}', [CrmController::class, 'updateClient']);
    $router->post('/crm/clients/{id}/supprimer', [CrmController::class, 'destroyClient']);
    $router->post('/crm/abonnements', [CrmController::class, 'storeSub']);
    $router->post('/crm/abonnements/{id}/statut', [CrmController::class, 'updateSub']);
    $router->post('/crm/abonnements/{id}/supprimer', [CrmController::class, 'destroySub']);
    $router->post('/crm/abonnements/{id}/paiement', [CrmController::class, 'storePayment']);

    // Paramètres
    $router->get('/parametres', [ParametresController::class, 'index']);
    $router->post('/parametres', [ParametresController::class, 'update']);

}, [AuthMiddleware::class]);

// ============================================================
// API Webhooks (pas de CSRF — vérification via signature)
// ============================================================
$router->post('/api/cinetpay/notify', [CinetPayWebhookController::class, 'handle']);

// ============================================================
// Sitemap
// ============================================================
$router->get('/sitemap.xml', function (\App\Core\Request $req) {
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
    $base = rtrim(\App\Core\env('APP_URL', ''), '/');
    $pages = ['/', '/formations', '/produits-services', '/actualites', '/a-propos', '/contact'];
    foreach ($pages as $p) {
        echo '<url><loc>' . htmlspecialchars($base . $p) . '</loc></url>';
    }
    echo '</urlset>';
});
