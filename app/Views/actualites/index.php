<?php
use function App\Core\e;
use App\Helpers\Format;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
?>

<!-- En-tête -->
<section class="border-b border-border bg-surface-gradient py-16 text-primary-foreground">
  <div class="mx-auto w-full max-w-6xl px-4">
    <h1 class="font-display text-3xl font-bold sm:text-4xl">Actualités de CaNDI</h1>
    <p class="mt-3 max-w-2xl text-primary-foreground/80">
      Toutes les activités de l'entreprise : formations, événements, partenariats et annonces.
      La lecture est libre, aucun compte n'est nécessaire.
    </p>
  </div>
</section>

<div class="mx-auto w-full max-w-6xl px-4 py-12">
  <?php if (empty($posts)): ?>
    <p class="text-muted-foreground">Aucune publication pour le moment.</p>
  <?php else: ?>
    <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($posts as $post): ?>
        <div class="flex flex-col overflow-hidden rounded-xl border border-border bg-card transition-shadow hover:shadow-lg">
          <?php if (!empty($post['cover_url'])): ?>
            <img src="<?= e(\App\Core\asset_url($post['cover_url'])) ?>" alt="<?= e($post['title']) ?>"
                 class="h-44 w-full object-cover" loading="lazy">
          <?php endif; ?>
          <div class="flex flex-1 flex-col p-5">
            <div class="flex items-center gap-2">
              <?php if (!empty($post['category'])): ?>
                <span class="inline-flex items-center rounded-full border border-secondary-foreground/20 bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                  <?= e($post['category']) ?>
                </span>
              <?php endif; ?>
              <?php if (!empty($post['video_url'])): ?>
                <span class="inline-flex items-center gap-1 rounded-full border border-border px-2.5 py-0.5 text-xs font-medium">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <polygon points="5,3 19,12 5,21"/>
                  </svg>
                  Vidéo
                </span>
              <?php endif; ?>
              <span class="text-xs text-muted-foreground">
                <?= Format::date($post['published_at'] ?? $post['created_at'] ?? null) ?>
              </span>
            </div>
            <h2 class="mt-2 font-display text-lg font-bold leading-snug">
              <a href="<?= $appUrl ?>/actualites/<?= e($post['slug']) ?>" class="hover:text-primary">
                <?= e($post['title']) ?>
              </a>
            </h2>
            <p class="mt-1.5 line-clamp-3 flex-1 text-sm text-muted-foreground"><?= e($post['excerpt'] ?? '') ?></p>
            <a href="<?= $appUrl ?>/actualites/<?= e($post['slug']) ?>"
               class="mt-4 inline-block text-sm font-medium text-primary hover:underline">
              Lire l'article
            </a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <?php if (($pagination['last_page'] ?? 1) > 1): ?>
      <div class="mt-10 flex items-center justify-center gap-2">
        <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
          <a href="<?= $appUrl ?>/actualites?page=<?= $p ?>"
             class="flex size-9 items-center justify-center rounded-md border text-sm font-medium transition-colors
                    <?= $p === ($pagination['current_page'] ?? 1) ? 'border-primary bg-primary text-primary-foreground' : 'border-border hover:bg-muted' ?>">
            <?= $p ?>
          </a>
        <?php endfor; ?>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
