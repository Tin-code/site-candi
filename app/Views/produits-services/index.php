<?php
use function App\Core\e;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
?>

<!-- En-tête -->
<section class="border-b border-border bg-surface-gradient py-16 text-primary-foreground">
  <div class="mx-auto w-full max-w-6xl px-4">
    <h1 class="font-display text-3xl font-bold uppercase sm:text-4xl">Nos produits et services</h1>
    <p class="mt-3 max-w-2xl text-primary-foreground/80">
      Formation, accompagnement numérique et plateformes de gestion CaNDI. Consultez librement
      nos offres et testez les versions de démonstration, sans avoir besoin d'un compte.
    </p>
  </div>
</section>

<div class="mx-auto w-full max-w-6xl space-y-14 px-4 py-12">

  <!-- Services -->
  <section>
    <h2 class="font-display text-2xl font-bold">Nos services</h2>
    <p class="mt-1 text-sm text-muted-foreground">Ce que CaNDI réalise pour les particuliers, les entreprises et les institutions.</p>

    <?php if (empty($services)): ?>
      <p class="mt-6 text-muted-foreground">Aucun service publié pour le moment.</p>
    <?php else: ?>
      <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($services as $item): ?>
          <?php include __DIR__ . '/partials/offering-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <!-- Plateformes -->
  <section>
    <h2 class="font-display text-2xl font-bold">Nos plateformes</h2>
    <p class="mt-1 text-sm text-muted-foreground">Nos progiciels de gestion, avec leur version de démonstration lorsqu'elle est disponible.</p>

    <?php if (empty($produits)): ?>
      <p class="mt-6 text-muted-foreground">Aucune plateforme publiée pour le moment.</p>
    <?php else: ?>
      <div class="mt-6 grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($produits as $item): ?>
          <?php include __DIR__ . '/partials/offering-card.php'; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

</div>
