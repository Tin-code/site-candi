<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$s    = $session ?? [];
$prog = $program ?? [];
$old  = $old ?? [];
$errs = $errors ?? [];
$val  = fn(string $k, string $def = '') => e($old[$k] ?? $s[$k] ?? $def);

$isEdit = !empty($s['id']);
$action = $isEdit
    ? $base . '/app/catalogue/sessions/' . e($s['id'])
    : $base . '/app/catalogue/' . e($prog['id'] ?? '') . '/sessions';

$today = date('Y-m-d');
?>

<div class="mx-auto max-w-xl space-y-6">

  <div>
    <a href="<?= $base ?>/app/catalogue" class="mb-2 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">← Retour au catalogue</a>
    <h1 class="font-display text-xl font-bold"><?= $isEdit ? 'Modifier la session' : 'Nouvelle session' ?></h1>
    <?php if ($prog['title'] ?? ''): ?>
      <p class="text-sm text-muted-foreground"><?= e($prog['title']) ?></p>
    <?php endif ?>
  </div>

  <?php if ($errs): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      <?php foreach ($errs as $err): ?><p><?= e($err) ?></p><?php endforeach ?>
    </div>
  <?php endif ?>

  <form method="POST" action="<?= $action ?>" class="space-y-4 rounded-xl border border-border bg-card p-6">
    <?= \App\Core\Csrf::field() ?>

    <div class="grid gap-1.5">
      <label for="code" class="text-sm font-medium">Code de la session <span class="text-destructive">*</span></label>
      <input id="code" name="code" type="text" required value="<?= $val('code') ?>" placeholder="BUR-001-S1"
             class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="start_date" class="text-sm font-medium">Date de début <span class="text-destructive">*</span></label>
        <input id="start_date" name="start_date" type="date" required value="<?= $val('start_date', $today) ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['start_date']) ? 'border-destructive' : '' ?>">
      </div>
      <div class="grid gap-1.5">
        <label for="end_date" class="text-sm font-medium">Date de fin <span class="text-destructive">*</span></label>
        <input id="end_date" name="end_date" type="date" required value="<?= $val('end_date', $today) ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['end_date']) ? 'border-destructive' : '' ?>">
        <?php if (isset($errs['end_date'])): ?><p class="text-xs text-destructive"><?= e($errs['end_date']) ?></p><?php endif ?>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="capacity" class="text-sm font-medium">Nombre de places</label>
        <input id="capacity" name="capacity" type="number" min="1" value="<?= $val('capacity', '20') ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1.5">
        <label for="modality" class="text-sm font-medium">Modalité</label>
        <select id="modality" name="modality" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <?php foreach (['presentiel' => 'Présentiel', 'en_ligne' => 'En ligne', 'hybride' => 'Hybride'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($val('modality', 'presentiel') === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="branch_id" class="text-sm font-medium">Agence</label>
        <select id="branch_id" name="branch_id" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="">— Aucune —</option>
          <?php foreach ($branches as $br): ?>
            <option value="<?= e($br['id']) ?>" <?= ($val('branch_id') === $br['id']) ? 'selected' : '' ?>>
              <?= e($br['name']) ?><?= $br['city'] ? ' — ' . e($br['city']) : '' ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="grid gap-1.5">
        <label for="status" class="text-sm font-medium">Statut</label>
        <select id="status" name="status" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <?php foreach (['planifiee' => 'Planifiée', 'en_cours' => 'En cours', 'terminee' => 'Terminée', 'annulee' => 'Annulée'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($val('status', 'planifiee') === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="grid gap-1.5">
      <label for="location" class="text-sm font-medium">Lieu</label>
      <input id="location" name="location" type="text" value="<?= $val('location') ?>" placeholder="Conakry, Centre CaNDI"
             class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <label class="flex items-center gap-2 cursor-pointer">
      <input type="checkbox" name="is_published" value="1" class="size-4 rounded"
             <?= ($old['is_published'] ?? $s['is_published'] ?? 0) ? 'checked' : '' ?>>
      <span class="text-sm font-medium">Ouvrir aux inscriptions (visible sur le site)</span>
    </label>

    <div class="flex gap-3 pt-2">
      <a href="<?= $base ?>/app/catalogue" class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Annuler</a>
      <button type="submit" class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        <?= $isEdit ? 'Enregistrer' : 'Planifier la session' ?>
      </button>
    </div>
  </form>

</div>
