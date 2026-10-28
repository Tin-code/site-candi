<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= isset($pageTitle) ? htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') . ' — CaNDI' : 'CaNDI — Formation & Numérique en Guinée' ?></title>
  <meta name="description" content="<?= isset($metaDesc) ? htmlspecialchars($metaDesc, ENT_QUOTES, 'UTF-8') : 'CaNDI : formation professionnelle, progiciels de gestion et services numériques en Guinée.' ?>">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&family=Syne:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Tailwind CSS compilé -->
  <link rel="stylesheet" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/assets/css/app.css">

  <!-- Favicon & PWA -->
  <link rel="icon" type="image/png" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/favicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/apple-touch-icon.png">
  <link rel="manifest" href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/manifest.webmanifest">
  <meta name="theme-color" content="#126875">
  <meta name="application-name" content="CaNDI">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="CaNDI">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="mobile-web-app-capable" content="yes">

  <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body class="flex min-h-screen flex-col bg-background text-foreground antialiased">

  <?php require __DIR__ . '/../partials/header.php'; ?>

  <main class="flex-1">
    <?= $content ?>
  </main>

  <?php require __DIR__ . '/../partials/footer.php'; ?>

  <!-- Bannière PWA install -->
  <div id="pwa-banner" style="display:none"
       class="fixed bottom-20 left-4 right-4 z-50 sm:left-auto sm:right-20 sm:w-72">
    <div class="flex items-center gap-3 rounded-2xl border border-border bg-card px-4 py-3 shadow-lg">
      <img src="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/icon-192.png" alt="CaNDI" class="size-10 rounded-xl shrink-0">
      <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold leading-tight">Installer CaNDI</p>
        <p class="text-xs text-muted-foreground">Accès rapide depuis votre écran</p>
      </div>
      <div class="flex flex-col gap-1 shrink-0">
        <button id="pwa-install-btn"
                class="rounded-lg bg-primary px-3 py-1 text-xs font-semibold text-primary-foreground hover:opacity-90">
          Installer
        </button>
        <button id="pwa-dismiss-btn"
                class="rounded-lg border border-border px-3 py-1 text-xs text-muted-foreground hover:bg-muted">
          Plus tard
        </button>
      </div>
    </div>
  </div>

  <!-- IkodiBubble (assistant flottant) -->
  <div id="ikodi-bubble" class="fixed bottom-6 right-6 z-50">
    <a href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/ikodi"
       class="flex h-12 w-12 items-center justify-center rounded-full bg-primary text-primary-foreground shadow-elevated transition-transform hover:scale-110"
       title="CaNDI AI">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
      </svg>
    </a>
  </div>

  <?php if (isset($extraScripts)) echo $extraScripts; ?>

  <!-- Service Worker + PWA install prompt -->
  <script>
    (function () {
      // Service Worker
      if ('serviceWorker' in navigator) {
        const swUrl   = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/') . '/sw.js') ?>;
        const swScope = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/') . '/') ?>;
        navigator.serviceWorker.register(swUrl, { scope: swScope }).catch(() => {});
      }

      // PWA install prompt
      let deferredPrompt = null;
      const banner      = document.getElementById('pwa-banner');
      const installBtn  = document.getElementById('pwa-install-btn');
      const dismissBtn  = document.getElementById('pwa-dismiss-btn');

      // Ne plus montrer si l'utilisateur a déjà refusé
      if (localStorage.getItem('pwa-dismissed')) return;

      window.addEventListener('beforeinstallprompt', function (e) {
        e.preventDefault();
        deferredPrompt = e;
        banner.style.display = '';
      });

      installBtn.addEventListener('click', function () {
        banner.style.display = 'none';
        if (deferredPrompt) {
          deferredPrompt.prompt();
          deferredPrompt.userChoice.then(function () { deferredPrompt = null; });
        }
      });

      dismissBtn.addEventListener('click', function () {
        banner.style.display = 'none';
        localStorage.setItem('pwa-dismissed', '1');
      });

      // Masquer si déjà installée
      window.addEventListener('appinstalled', function () {
        banner.style.display = 'none';
      });
    })();
  </script>
</body>
</html>
