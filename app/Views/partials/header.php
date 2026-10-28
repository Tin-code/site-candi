<?php
use App\Core\Auth;
use function App\Core\e;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
$currentPath = (new \App\Core\Request())->path();

$nav = [
    '/'                 => 'Accueil',
    '/a-propos'         => 'À propos',
    '/actualites'       => 'Actualités',
    '/formations'       => 'Formations',
    '/produits-services' => 'Produits & services',
    '/ikodi'            => 'CaNDI AI',
    '/contact'          => 'Contact',
];

$user = Auth::user();
?>
<header class="sticky top-0 z-40 border-b border-border bg-background/85 backdrop-blur">
  <div class="mx-auto flex h-20 w-full max-w-6xl items-center justify-between gap-4 px-4">

    <a href="<?= $appUrl ?>/" class="flex items-center">
      <img
        src="<?= $appUrl ?>/images/candi-logo.png"
        alt="Logo CaNDI"
        class="h-20 w-auto scale-110 object-contain drop-shadow-sm"
      />
    </a>

    <!-- Navigation desktop -->
    <nav aria-label="Navigation principale" class="hidden items-center gap-1 md:flex">
      <?php foreach ($nav as $path => $label): ?>
        <a href="<?= $appUrl . $path ?>"
           class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground<?= $currentPath === $path ? ' text-primary' : '' ?>">
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <!-- Boutons auth desktop -->
    <div class="hidden items-center gap-2 md:flex">
      <?php if ($user): ?>
        <a href="<?= $appUrl ?>/app/tableau-de-bord"
           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
          Mon espace
        </a>
      <?php else: ?>
        <a href="<?= $appUrl ?>/auth"
           class="inline-flex h-9 items-center justify-center rounded-md px-4 py-2 text-sm font-medium transition-colors hover:bg-muted">
          Se connecter
        </a>
        <a href="<?= $appUrl ?>/auth?mode=signup"
           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
          Créer un compte
        </a>
      <?php endif; ?>
    </div>

    <!-- Bouton menu mobile -->
    <button
      type="button"
      id="mobile-menu-toggle"
      aria-label="Ouvrir le menu"
      aria-expanded="false"
      aria-controls="mobile-menu"
      class="inline-flex h-9 w-9 items-center justify-center rounded-md border border-border bg-background transition-colors hover:bg-muted md:hidden"
    >
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Menu mobile (caché par défaut) -->
  <div id="mobile-menu" class="hidden border-t border-border bg-background px-4 pb-4 md:hidden">
    <nav class="mt-4 flex flex-col gap-1">
      <?php foreach ($nav as $path => $label): ?>
        <a href="<?= $appUrl . $path ?>"
           class="rounded-md px-3 py-2 text-sm font-medium text-muted-foreground transition-colors hover:bg-muted hover:text-foreground<?= $currentPath === $path ? ' text-primary' : '' ?>">
          <?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="mt-4 flex flex-col gap-2">
      <?php if ($user): ?>
        <a href="<?= $appUrl ?>/app/tableau-de-bord"
           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
          Mon espace
        </a>
      <?php else: ?>
        <a href="<?= $appUrl ?>/auth"
           class="inline-flex h-9 items-center justify-center rounded-md border border-border px-4 py-2 text-sm font-medium">
          Se connecter
        </a>
        <a href="<?= $appUrl ?>/auth?mode=signup"
           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground">
          Créer un compte
        </a>
      <?php endif; ?>
    </div>
  </div>
</header>

<script>
document.getElementById('mobile-menu-toggle')?.addEventListener('click', function() {
  const menu = document.getElementById('mobile-menu');
  const open = menu.classList.toggle('hidden') === false;
  this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
