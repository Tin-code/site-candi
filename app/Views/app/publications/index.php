<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$catLabels = [
    'actualite'    => 'Actualité',
    'evenement'    => 'Événement',
    'formation'    => 'Formation',
    'partenariat'  => 'Partenariat',
    'communique'   => 'Communiqué',
];
?>
<div class="space-y-6">

  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Publications du site</h1>
      <p class="text-sm text-muted-foreground">Les articles publiés sont visibles par tous les visiteurs, sans compte.</p>
    </div>
    <a href="<?= $base ?>/app/publications/creer"
       class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Nouvelle publication
    </a>
  </div>

  <?php $flash = \App\Core\Session::flash('success'); if ($flash): ?>
    <div class="rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success"><?= e($flash) ?></div>
  <?php endif ?>

  <?php if (empty($posts)): ?>
    <div class="rounded-xl border border-border bg-card px-6 py-10 text-center text-sm text-muted-foreground">
      Aucune publication pour l'instant.
    </div>
  <?php else: ?>
    <div class="grid gap-4">
      <?php foreach ($posts as $post): ?>
        <div class="rounded-xl border border-border bg-card">
          <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4">
            <div class="flex min-w-0 gap-3">
              <?php if (!empty($post['cover_url'])): ?>
                <img src="<?= e(\App\Core\asset_url($post['cover_url'])) ?>"
                     alt="" class="size-12 shrink-0 rounded-lg object-cover">
              <?php endif ?>
              <div class="min-w-0">
                <h2 class="font-medium"><?= e($post['title']) ?></h2>
                <p class="mt-0.5 text-xs text-muted-foreground">
                  <?= e($catLabels[$post['category']] ?? $post['category']) ?> ·
                  <?= Format::date($post['published_at'] ?? $post['created_at']) ?> ·
                  /actualites/<?= e($post['slug']) ?>
                </p>
                <?php if ($post['excerpt'] ?? ''): ?>
                  <p class="mt-1.5 text-sm text-muted-foreground line-clamp-2"><?= e($post['excerpt']) ?></p>
                <?php endif ?>
              </div>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $post['status'] === 'publie' ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' ?>">
                <?= $post['status'] === 'publie' ? 'Publié' : 'Brouillon' ?>
              </span>
              <!-- Toggle publish -->
              <form method="POST" action="<?= $base ?>/app/publications/<?= e($post['id']) ?>/toggle">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="rounded-md border border-border px-2.5 py-1 text-xs font-medium hover:bg-muted">
                  <?= $post['status'] === 'publie' ? 'Dépublier' : 'Publier' ?>
                </button>
              </form>
              <a href="<?= $base ?>/app/publications/<?= e($post['id']) ?>/modifier"
                 class="rounded-md border border-border p-1.5 hover:bg-muted" title="Modifier">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
              </a>
              <form method="POST" action="<?= $base ?>/app/publications/<?= e($post['id']) ?>/supprimer"
                    onsubmit="return confirm('Supprimer cette publication ?')">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="rounded-md border border-border p-1.5 text-destructive hover:bg-destructive/5" title="Supprimer">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                </button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

</div>
