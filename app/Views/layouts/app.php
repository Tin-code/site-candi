<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — CaNDI' : 'Espace CaNDI' ?></title>
  <meta name="robots" content="noindex">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/assets/css/app.css">
  <link rel="icon" type="image/png" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/apple-touch-icon.png">
  <link rel="manifest" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/manifest.webmanifest">
  <meta name="theme-color" content="#126875">
  <meta name="application-name" content="CaNDI">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="CaNDI">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="mobile-web-app-capable" content="yes">
</head>
<?php
use function App\Core\e;
use App\Core\Auth;
use App\Core\Session;

$base    = rtrim(\App\Core\env('APP_URL', ''), '/');
$curPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
$appBase = parse_url($base, PHP_URL_PATH) ?? '';
if ($appBase && str_starts_with($curPath, $appBase)) {
    $curPath = substr($curPath, strlen($appBase));
}
$curPath = $curPath ?: '/';

$user = Auth::user();
$role = Auth::role() ?? 'client';

$isClient   = $role === 'client';
$isStaff    = !$isClient;
$isFinance  = in_array($role, ['super_admin', 'admin', 'comptable'], true);
$isAdmin    = in_array($role, ['super_admin', 'admin'], true);
$isImmo     = in_array($role, ['super_admin', 'gestionnaire_immobilier'], true);
$isImmoOnly = $role === 'gestionnaire_immobilier'; // masque formation/finances pour ce rôle
$isRapports = in_array($role, ['super_admin', 'admin', 'comptable', 'responsable_formation'], true);
$isCrm      = in_array($role, ['super_admin', 'admin', 'comptable', 'commercial'], true);
$isTrainer  = in_array($role, ['super_admin', 'admin', 'responsable_formation', 'formateur'], true);
// Inscriptions staff : exclut formateur (React ne lui montre pas)
$canSeeInscriptions = $isStaff && !$isImmoOnly && $role !== 'formateur';
// Catalogue back-office : exclut commercial (React ne lui montre pas)
$canSeeCatalogue    = $isStaff && !$isImmoOnly && $role !== 'commercial';

// Nav items: [href, label, icon, show?]
// null  = show to everyone
// bool  = show only when true (e.g. $isStaff, $isAdmin)
$navGroups = [
  ['label' => 'Pilotage', 'items' => [
    ['/app/tableau-de-bord', 'Tableau de bord',  'dashboard', null],
  ]],
  ['label' => 'Formation', 'items' => [
    ['/app/catalogue',       'Formations',        'book',      $canSeeCatalogue],
    ['/app/inscriptions',    'Inscriptions',      'users',     $canSeeInscriptions],
    ['/app/espace-apprenant','Espace apprenant',  'grad',      $isClient || $isTrainer],
    ['/app/inscriptions',    'Mes inscriptions',  'users',     $isClient],
  ]],
  ['label' => 'Immobilier', 'items' => [
    ['/app/immobilier',      'Immobilier & loyers',   'building',  $isImmo],
    ['/app/paiements-loyers','Gestion des paiements', 'wallet',    $isImmo],
    ['/app/mon-logement',    'Mon logement',           'home',      $isClient],
  ]],
  ['label' => 'Finances', 'items' => [
    ['/app/paiements',       'Payer en ligne',    'wallet',    $isClient],
    ['/app/finances',        'Finances',          'chart',     $isFinance],
  ]],
  ['label' => 'Communication', 'items' => [
    ['/app/publications',    'Publications',      'book',      $isStaff && !$isImmoOnly],
    ['/app/messages-sms',    'SMS automatiques',  'bell',      $isAdmin],
  ]],
  ['label' => 'Outils', 'items' => [
    ['/app/taches',          'Tâches',            'check',     $isStaff && !$isImmoOnly],
    ['/app/rapports',        'Rapports',          'chart',     $isRapports],
    ['/app/crm',             'Progiciels CRM',    'users',     $isCrm],
  ]],
  ['label' => 'Administration', 'items' => [
    ['/app/utilisateurs',    'Utilisateurs',      'users',     $isAdmin],
    ['/app/parametres',      'Paramètres',        'gear',      $isAdmin],
  ]],
  ['label' => 'Mon compte', 'items' => [
    ['/app/ai',              'CaNDI AI',          'ai',        null],
    ['/app/profil',          'Mon profil',        'user',      null],
    ['/app/notifications',   'Notifications',     'bell',      null],
  ]],
];

function appIcon(string $name, string $cls = 'size-4 shrink-0'): string {
    $p = match ($name) {
        'dashboard' => 'M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z',
        'users'     => 'M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z',
        'book'      => 'M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25',
        'wallet'    => 'M21 12a2.25 2.25 0 00-2.25-2.25H15a3 3 0 11-6 0H5.25A2.25 2.25 0 003 12m18 0v6a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 18v-6m18 0V9M3 12V9m18 0a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 9m18 0V6a2.25 2.25 0 00-2.25-2.25H5.25A2.25 2.25 0 003 6v3',
        'user'      => 'M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z',
        'bell'      => 'M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0',
        'gear'      => 'M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
        'chart'     => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'grad'      => 'M4.26 10.147a60.436 60.436 0 00-.491 6.347A48.627 48.627 0 0112 20.904a48.627 48.627 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.57 50.57 0 00-2.658-.813A59.905 59.905 0 0112 3.493a59.902 59.902 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.697 50.697 0 0112 13.489a50.702 50.702 0 017.74-3.342M6.75 15a.75.75 0 100-1.5.75.75 0 000 1.5zm0 0v-3.675A55.378 55.378 0 0112 8.443m-7.007 11.55A5.981 5.981 0 006.75 15.75v-1.5',
        'menu'      => 'M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5',
        'x'         => 'M6 18L18 6M6 6l12 12',
        'building'  => 'M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z',
        'home'      => 'M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25',
        'ai'        => 'M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456zM16.894 20.567L16.5 21.75l-.394-1.183a2.25 2.25 0 00-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 001.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 001.423 1.423l1.183.394-1.183.394a2.25 2.25 0 00-1.423 1.423z',
        'check'     => 'M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
        'logout'    => 'M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9',
        default     => '',
    };
    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" class="%s" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="%s"/></svg>',
        htmlspecialchars($cls, ENT_QUOTES),
        $p
    );
}

function isActive(string $href, string $cur): bool {
    if ($href === '/app/tableau-de-bord') return $cur === '/app/tableau-de-bord';
    return str_starts_with($cur, $href);
}

$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($displayName === '') $displayName = $user['email'] ?? 'Mon compte';

$roleLabels = [
    'super_admin'             => 'Super Admin',
    'admin'                   => 'Administrateur',
    'comptable'               => 'Comptable',
    'responsable_formation'   => 'Resp. formation',
    'formateur'               => 'Formateur',
    'commercial'              => 'Commercial',
    'client'                  => 'Apprenant',
    'gestionnaire_immobilier' => 'Gest. immobilier',
];
$roleLabel = $roleLabels[$role] ?? ucfirst($role);

$notifCount = 0;
try {
    $pdo  = \App\Core\Database::getInstance();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$user['id'] ?? '']);
    $notifCount = (int) $stmt->fetchColumn();
} catch (\Throwable $e) { /* silent */ }
?>
<body class="flex h-screen overflow-hidden bg-background text-foreground antialiased">

<!-- ═══════════ SIDEBAR ═══════════ -->
<aside id="sidebar"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col
              bg-sidebar text-sidebar-foreground
              border-r border-sidebar-border
              transition-transform duration-200 ease-in-out
              -translate-x-full lg:translate-x-0">

  <!-- Logo -->
  <div class="flex h-14 shrink-0 items-center gap-2 border-b border-sidebar-border px-4">
    <a href="<?= $base ?>/" class="font-display text-lg font-bold text-sidebar-primary">CaNDI</a>
    <span class="text-xs text-sidebar-foreground/60">Espace</span>
    <button type="button" id="sidebar-close"
            class="ml-auto rounded p-1 text-sidebar-foreground/60 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground lg:hidden"
            aria-label="Fermer le menu">
      <?= appIcon('x', 'size-5') ?>
    </button>
  </div>

  <!-- Nav -->
  <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-1">
    <?php foreach ($navGroups as $group):
      // Check if any item in this group would be visible
      $hasVisible = false;
      foreach ($group['items'] as [,,,$show]) {
          if ($show === null || $show) { $hasVisible = true; break; }
      }
      if (!$hasVisible) continue;
    ?>
      <div class="mb-1">
        <p class="mb-0.5 px-2 pt-2 text-[10px] font-semibold uppercase tracking-wider text-sidebar-foreground/50">
          <?= e($group['label']) ?>
        </p>
        <?php foreach ($group['items'] as [$href, $label, $icon, $show]): ?>
          <?php
            // null = show to all, bool = show only if true
            if ($show !== null && !$show) continue;
            $active = isActive($href, $curPath);
          ?>
          <a href="<?= $base . $href ?>"
             class="flex items-center gap-2.5 rounded-md px-2.5 py-2 text-sm font-medium transition-colors
                    <?= $active
                        ? 'bg-sidebar-accent text-sidebar-accent-foreground'
                        : 'text-sidebar-foreground/75 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' ?>">
            <?= appIcon($icon, 'size-4 shrink-0') ?>
            <?= e($label) ?>
          </a>
        <?php endforeach ?>
      </div>
    <?php endforeach ?>
  </nav>

  <!-- User block -->
  <div class="shrink-0 border-t border-sidebar-border p-3">
    <div class="flex items-center gap-2.5 rounded-md bg-sidebar-accent/50 px-3 py-2 mb-1">
      <span class="flex size-8 shrink-0 items-center justify-center rounded-full bg-sidebar-primary/20 text-sm font-bold text-sidebar-primary">
        <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
      </span>
      <div class="min-w-0 flex-1">
        <p class="truncate text-sm font-semibold text-sidebar-foreground"><?= e($displayName) ?></p>
        <p class="truncate text-xs text-sidebar-foreground/60"><?= e($roleLabel) ?></p>
      </div>
    </div>
    <form method="POST" action="<?= $base ?>/auth/logout">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit"
              class="flex w-full items-center gap-2 rounded-md px-2.5 py-1.5 text-xs text-sidebar-foreground/60
                     hover:bg-sidebar-accent/60 hover:text-sidebar-foreground transition-colors">
        <?= appIcon('logout', 'size-4 shrink-0') ?>
        Se déconnecter
      </button>
    </form>
  </div>
</aside>

<!-- Overlay mobile -->
<div id="sidebar-overlay"
     class="fixed inset-0 z-30 bg-black/40 hidden lg:hidden"
     aria-hidden="true"></div>

<!-- ═══════════ ZONE PRINCIPALE ═══════════ -->
<div class="flex flex-1 flex-col overflow-hidden lg:pl-64">

  <!-- Top bar -->
  <header class="flex h-14 shrink-0 items-center gap-3 border-b border-border bg-card px-4 shadow-sm">
    <!-- Burger mobile -->
    <button type="button" id="sidebar-open"
            class="rounded p-1.5 text-muted-foreground hover:bg-muted lg:hidden"
            aria-label="Ouvrir le menu">
      <?= appIcon('menu', 'size-5') ?>
    </button>

    <!-- Titre page -->
    <span class="flex-1 truncate text-sm font-semibold text-foreground">
      <?= e($pageTitle ?? 'Espace CaNDI') ?>
    </span>

    <!-- Notifications -->
    <a href="<?= $base ?>/app/notifications"
       class="relative rounded-md p-1.5 text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">
      <?= appIcon('bell', 'size-5') ?>
      <?php if ($notifCount > 0): ?>
        <span class="absolute -right-0.5 -top-0.5 flex size-4 items-center justify-center rounded-full
                     bg-destructive text-[9px] font-bold text-white">
          <?= $notifCount > 9 ? '9+' : $notifCount ?>
        </span>
      <?php endif ?>
    </a>

    <!-- Site public -->
    <a href="<?= $base ?>/"
       class="hidden sm:flex items-center gap-1 rounded-md px-2.5 py-1.5 text-xs text-muted-foreground hover:bg-muted hover:text-foreground transition-colors">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 003 8.25v10.5A2.25 2.25 0 005.25 21h10.5A2.25 2.25 0 0018 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25"/></svg>
      Site public
    </a>
  </header>

  <!-- Contenu page -->
  <main class="flex-1 overflow-y-auto bg-muted/30 p-4 md:p-6">
    <?php include __DIR__ . '/../partials/flash.php'; ?>
    <?= $content ?>
  </main>

</div>

<script>
(function () {
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebar-overlay');
  const btnOpen  = document.getElementById('sidebar-open');
  const btnClose = document.getElementById('sidebar-close');

  function open() {
    sidebar.classList.remove('-translate-x-full');
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }
  function close() {
    sidebar.classList.add('-translate-x-full');
    overlay.classList.add('hidden');
    document.body.style.overflow = '';
  }

  btnOpen?.addEventListener('click', open);
  btnClose?.addEventListener('click', close);
  overlay?.addEventListener('click', close);
})();
</script>

  <!-- Service Worker -->
  <script>
    if ('serviceWorker' in navigator) {
      const swUrl   = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/') . '/sw.js') ?>;
      const swScope = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/') . '/') ?>;
      navigator.serviceWorker.register(swUrl, { scope: swScope }).catch(() => {});
    }
  </script>
</body>
</html>
