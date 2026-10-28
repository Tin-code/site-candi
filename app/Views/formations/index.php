<?php
use function App\Core\e;
use App\Core\Csrf;
use App\Helpers\Format;

$appUrl   = rtrim(\App\Core\env('APP_URL', ''), '/');
$isStaff  = in_array($user['role'] ?? '', ['super_admin', 'admin', 'responsable_formation', 'comptable'], true);

$modalities  = ['presentiel' => 'Présentiel', 'en_ligne' => 'En ligne', 'hybride' => 'Hybride'];
$levelLabels = ['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'];
?>

<div class="mx-auto w-full max-w-6xl px-4 py-10">
  <h1 class="font-display text-3xl font-bold">Catalogue des formations</h1>

  <?php if ($isStaff): ?>
    <div class="mt-2 flex flex-wrap items-center gap-3">
      <p class="text-muted-foreground">Vous êtes connecté avec un compte de gestion : vous gérez le catalogue.</p>
      <a href="<?= $appUrl ?>/app/catalogue"
         class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow">
        Modifier le catalogue
      </a>
    </div>
  <?php else: ?>
    <p class="mt-2 text-muted-foreground">
      Choisissez une formation, sélectionnez une session et envoyez votre demande d'inscription.
    </p>
  <?php endif; ?>

  <!-- Filtres -->
  <form method="GET" action="<?= $appUrl ?>/formations"
        class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-4 sm:grid-cols-3">
    <div>
      <label for="recherche" class="text-sm font-medium">Rechercher</label>
      <div class="relative mt-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
        <input type="text" id="recherche" name="q"
               value="<?= e($search) ?>"
               placeholder="Titre de la formation"
               class="w-full rounded-md border border-input bg-background py-2 pl-9 pr-3 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
      </div>
    </div>

    <div>
      <label for="categorie" class="text-sm font-medium">Catégorie</label>
      <select id="categorie" name="categorie"
              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
        <option value="all" <?= $category === 'all' ? 'selected' : '' ?>>Toutes les catégories</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat['id']) ?>" <?= $category === (string)$cat['id'] ? 'selected' : '' ?>>
            <?= e($cat['name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div>
      <label for="niveau" class="text-sm font-medium">Niveau</label>
      <select id="niveau" name="niveau"
              class="mt-1 w-full rounded-md border border-input bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-ring">
        <option value="all" <?= $level === 'all' ? 'selected' : '' ?>>Tous les niveaux</option>
        <?php foreach ($levelLabels as $val => $lbl): ?>
          <option value="<?= e($val) ?>" <?= $level === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <?php include __DIR__ . '/../partials/flash.php'; ?>

  <!-- Résultats -->
  <?php if (empty($programs)): ?>
    <p class="mt-10 rounded-lg border border-dashed border-border p-10 text-center text-muted-foreground">
      Aucune formation ne correspond à votre recherche.
    </p>
  <?php else: ?>
    <div class="mt-8 space-y-6">
      <?php foreach ($programs as $program): ?>
        <?php $programSessions = $sessionsByProgram[$program['id']] ?? []; ?>
        <div class="rounded-xl border border-border bg-card p-6 shadow-[var(--shadow-card)]">

          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <h2 class="font-display text-xl font-bold text-foreground"><?= e($program['title']) ?></h2>
              <p class="mt-1 text-sm text-muted-foreground"><?= e($program['summary'] ?? '') ?></p>
            </div>
            <div class="text-right">
              <p class="font-display text-xl font-bold text-primary">
                <?= Format::money((float)($program['promo_price'] ?? $program['price'] ?? 0)) ?>
              </p>
              <p class="text-xs text-muted-foreground"><?= e($program['duration_label'] ?? '') ?></p>
            </div>
          </div>

          <div class="mt-2 flex flex-wrap gap-2">
            <?php if (!empty($program['level'])): ?>
              <span class="inline-flex items-center rounded-full border border-secondary-foreground/20 bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                <?= e($levelLabels[$program['level']] ?? $program['level']) ?>
              </span>
            <?php endif; ?>
            <?php if (!empty($program['total_hours'])): ?>
              <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-medium"><?= e($program['total_hours']) ?> h</span>
            <?php endif; ?>
            <?php if (!empty($program['certificate_available'])): ?>
              <span class="inline-flex items-center rounded-full border border-border px-2.5 py-0.5 text-xs font-medium">Attestation</span>
            <?php endif; ?>
          </div>

          <?php if (!empty($program['description'])): ?>
            <p class="mt-3 text-sm text-muted-foreground"><?= e($program['description']) ?></p>
          <?php endif; ?>

          <div class="mt-4 space-y-2">
            <p class="text-sm font-semibold">Sessions</p>

            <?php if (empty($programSessions)): ?>
              <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3">
                <p class="text-sm text-muted-foreground">Aucune session programmée pour le moment.</p>
                <?php if (!$isStaff): ?>
                  <form method="POST" action="<?= $appUrl ?>/formations/<?= (int)$program['id'] ?>/inscrire">
                    <?= Csrf::field() ?>
                    <button type="submit" class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow">
                      Demander une inscription
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <?php foreach ($programSessions as $session): ?>
                <?php $remaining = max((int)($session['capacity'] ?? 0) - (int)($session['seats_taken'] ?? 0), 0); ?>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3">
                  <div class="text-sm">
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                      <?= Format::date($session['start_date']) ?> → <?= Format::date($session['end_date']) ?>
                    </span>
                    <span class="ml-3 inline-flex items-center gap-1 text-muted-foreground">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                      </svg>
                      <?= e($session['location'] ?? '') ?> • <?= e($modalities[$session['modality'] ?? ''] ?? '') ?>
                    </span>
                  </div>
                  <div class="flex items-center gap-3">
                    <span class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
                      <?= $session['status'] === 'en_cours' ? 'En cours' : 'Programmée' ?>
                    </span>
                    <span class="text-sm text-muted-foreground"><?= $remaining ?> place(s)</span>
                    <?php if (!$isStaff): ?>
                      <form method="POST" action="<?= $appUrl ?>/formations/<?= (int)$program['id'] ?>/inscrire">
                        <?= Csrf::field() ?>
                        <input type="hidden" name="session_id" value="<?= e($session['id']) ?>">
                        <button type="submit" <?= $remaining === 0 ? 'disabled' : '' ?>
                                class="inline-flex h-9 items-center justify-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground shadow disabled:opacity-50 disabled:cursor-not-allowed">
                          <?= $remaining === 0 ? 'Complet' : 'Demander une inscription' ?>
                        </button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <?php if (!$user): ?>
    <p class="mt-8 text-center text-sm text-muted-foreground">
      Vous n'avez pas encore de compte ?
      <a href="<?= $appUrl ?>/auth?mode=signup" class="font-medium text-primary underline">Créez votre compte apprenant</a>
    </p>
  <?php endif; ?>
</div>
