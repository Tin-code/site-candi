<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$levelLabels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
$modalityLabels = ['presentiel' => 'Présentiel', 'en_ligne' => 'En ligne', 'hybride' => 'Hybride'];
$sessionStatusLabels = ['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'];
?>
<div class="space-y-6">

  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Catalogue de formations</h1>
      <p class="mt-1 text-sm text-muted-foreground">Créez les programmes, fixez les tarifs et planifiez les sessions.</p>
    </div>
    <a href="<?= $base ?>/app/catalogue/creer"
       class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
      Nouveau programme
    </a>
  </div>

  <?php
  $flash = \App\Core\Session::flash('success');
  if ($flash): ?>
    <div class="rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success"><?= e($flash) ?></div>
  <?php endif ?>

  <?php if (empty($programs)): ?>
    <div class="rounded-xl border border-border bg-card px-6 py-10 text-center text-sm text-muted-foreground">
      Aucun programme enregistré pour le moment.
    </div>
  <?php else: ?>
    <div class="space-y-4">
      <?php foreach ($programs as $prog): ?>
        <?php $sessions = $sessionsByProgram[$prog['id']] ?? []; ?>
        <div class="rounded-xl border border-border bg-card shadow-sm">

          <!-- En-tête programme -->
          <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-4 border-b border-border">
            <div class="min-w-0">
              <div class="flex flex-wrap items-center gap-2">
                <h2 class="font-semibold"><?= e($prog['title']) ?></h2>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $prog['is_published'] ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' ?>">
                  <?= $prog['is_published'] ? 'Publié' : 'Brouillon' ?>
                </span>
              </div>
              <p class="mt-0.5 text-sm text-muted-foreground">
                <?= e($prog['code']) ?> ·
                <?= e($levelLabels[$prog['level']] ?? $prog['level']) ?> ·
                <strong class="text-primary"><?= Format::money((float) $prog['price']) ?></strong>
                <?php if ($prog['duration_label'] ?? ''): ?>
                  · <?= e($prog['duration_label']) ?>
                <?php endif ?>
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <a href="<?= $base ?>/app/catalogue/<?= e($prog['id']) ?>/modifier"
                 class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                Modifier
              </a>
              <a href="<?= $base ?>/app/catalogue/<?= e($prog['id']) ?>/sessions/creer"
                 class="inline-flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                Ajouter une session
              </a>
              <form method="POST" action="<?= $base ?>/app/catalogue/<?= e($prog['id']) ?>/supprimer"
                    onsubmit="return confirm('Archiver ce programme ?')">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg border border-destructive/30 px-3 py-1.5 text-xs font-medium text-destructive hover:bg-destructive/5">
                  <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                  Archiver
                </button>
              </form>
            </div>
          </div>

          <!-- Sessions -->
          <div class="px-5 py-3">
            <?php if (empty($sessions)): ?>
              <p class="text-sm text-muted-foreground py-2">Aucune session planifiée.</p>
            <?php else: ?>
              <div class="divide-y divide-border">
                <?php foreach ($sessions as $s): ?>
                  <div class="flex flex-wrap items-center justify-between gap-3 py-2.5">
                    <div>
                      <p class="text-sm font-medium">
                        <?= Format::date($s['start_date']) ?> → <?= Format::date($s['end_date']) ?>
                      </p>
                      <p class="text-xs text-muted-foreground">
                        <?= e($s['code']) ?> ·
                        <?= e($s['location'] ?? 'lieu à définir') ?> ·
                        <?= e($modalityLabels[$s['modality']] ?? $s['modality']) ?> ·
                        <?= $s['seats_taken'] ?>/<?= $s['capacity'] ?> places
                      </p>
                    </div>
                    <div class="flex items-center gap-2">
                      <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $s['is_published'] ? 'bg-success/15 text-success' : 'bg-muted text-muted-foreground' ?>">
                        <?= $s['is_published'] ? 'Publiée' : 'Non publiée' ?>
                      </span>
                      <a href="<?= $base ?>/app/catalogue/sessions/<?= e($s['id']) ?>/modifier"
                         class="rounded-md border border-border px-2 py-1 text-xs hover:bg-muted">Modifier</a>
                      <form method="POST" action="<?= $base ?>/app/catalogue/sessions/<?= e($s['id']) ?>/supprimer"
                            onsubmit="return confirm('Supprimer cette session ?')">
                        <?= \App\Core\Csrf::field() ?>
                        <button type="submit" class="rounded-md px-2 py-1 text-xs text-destructive hover:bg-destructive/10">Supprimer</button>
                      </form>
                    </div>
                  </div>
                <?php endforeach ?>
              </div>
            <?php endif ?>
          </div>

        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>
