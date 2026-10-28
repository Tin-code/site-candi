<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
?>
<div class="mx-auto max-w-md py-12 text-center">
  <div class="mx-auto mb-6 flex size-16 items-center justify-center rounded-full bg-primary/10">
    <svg xmlns="http://www.w3.org/2000/svg" class="size-8 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
    </svg>
  </div>
  <h1 class="font-display text-2xl font-bold">Paiement en cours de traitement</h1>
  <p class="mt-3 text-sm text-muted-foreground">
    Votre paiement a été transmis à CinetPay. Votre dossier sera mis à jour automatiquement
    dans quelques instants une fois la confirmation reçue.
  </p>
  <?php if ($ref ?? ''): ?>
    <p class="mt-2 text-xs text-muted-foreground">Référence : <?= e($ref) ?></p>
  <?php endif ?>
  <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:justify-center">
    <a href="<?= $base ?>/app/paiements"
       class="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90">
      Voir mes paiements
    </a>
    <a href="<?= $base ?>/app/tableau-de-bord"
       class="rounded-lg border border-border px-5 py-2.5 text-sm font-medium hover:bg-muted">
      Tableau de bord
    </a>
  </div>
</div>
