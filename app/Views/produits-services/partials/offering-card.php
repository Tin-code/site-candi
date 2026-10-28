<?php
// $item est attendu dans le scope parent
use function App\Core\e;
$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
?>
<div class="flex flex-col overflow-hidden rounded-xl border border-border bg-card transition-shadow hover:shadow-lg">
  <?php if (!empty($item['image_url'])): ?>
    <img src="<?= e(\App\Core\asset_url($item['image_url'])) ?>" alt="<?= e($item['title']) ?>"
         class="h-44 w-full object-cover" loading="lazy">
  <?php endif; ?>
  <div class="flex flex-1 flex-col gap-3 p-6">
    <div class="flex flex-wrap items-center gap-2">
      <span class="inline-flex items-center rounded-full border border-secondary-foreground/20 bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
        <?= ($item['kind'] ?? '') === 'produit' ? 'Plateforme' : 'Service' ?>
      </span>
      <?php if (!empty($item['price_label'])): ?>
        <span class="rounded-md bg-primary/10 px-2 py-0.5 font-display text-sm font-bold text-primary ring-1 ring-primary/20">
          <?= e($item['price_label']) ?>
        </span>
      <?php endif; ?>
    </div>
    <h3 class="font-display text-lg font-bold leading-snug text-primary"><?= e($item['title']) ?></h3>
    <?php if (!empty($item['summary'])): ?>
      <p class="text-sm text-muted-foreground"><?= e($item['summary']) ?></p>
    <?php endif; ?>
    <?php if (!empty($item['description'])): ?>
      <p class="whitespace-pre-line text-sm text-muted-foreground"><?= e($item['description']) ?></p>
    <?php endif; ?>
    <?php if (!empty($item['features'])): ?>
      <ul class="mt-1 space-y-1.5">
        <?php foreach ($item['features'] as $feature): ?>
          <li class="flex items-start gap-2 text-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span><?= e($feature) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <div class="mt-auto flex flex-wrap gap-2 pt-4">
      <?php if (!empty($item['demo_url'])): ?>
        <a href="<?= e($item['demo_url']) ?>" target="_blank" rel="noreferrer"
           class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow">
          Tester la démo
          <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
          </svg>
        </a>
      <?php endif; ?>
      <a href="<?= $appUrl ?>/contact"
         class="inline-flex h-9 items-center justify-center rounded-md border border-border px-4 text-sm font-medium transition-colors hover:bg-muted">
        Demander une présentation
      </a>
    </div>
  </div>
</div>
