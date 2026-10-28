<?php use function App\Core\e; ?>
<div class="flex min-h-[60vh] flex-col items-center justify-center gap-6 px-4 text-center">
  <p class="font-display text-8xl font-bold text-primary/20"><?= e($code) ?></p>
  <h1 class="font-display text-2xl font-bold text-foreground"><?= e($title) ?></h1>
  <?php if (!empty($message)): ?>
    <p class="text-muted-foreground"><?= e($message) ?></p>
  <?php endif; ?>
  <a href="<?= rtrim(\App\Core\env('APP_URL', ''), '/') ?>/"
     class="inline-flex h-10 items-center justify-center rounded-md bg-primary px-6 text-sm font-medium text-primary-foreground shadow transition-colors hover:bg-primary/90">
    Retour à l'accueil
  </a>
</div>
