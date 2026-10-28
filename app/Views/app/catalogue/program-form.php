<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$p    = $program ?? [];
$old  = $old ?? [];
$errs = $errors ?? [];
$val  = fn(string $k, string $def = '') => e($old[$k] ?? $p[$k] ?? $def);

$isEdit  = !empty($p['id']);
$action  = $isEdit ? $base . '/app/catalogue/' . e($p['id']) : $base . '/app/catalogue';
?>

<div class="mx-auto max-w-2xl space-y-6">

  <div>
    <a href="<?= $base ?>/app/catalogue" class="mb-2 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">← Retour au catalogue</a>
    <h1 class="font-display text-xl font-bold"><?= $isEdit ? 'Modifier le programme' : 'Nouveau programme' ?></h1>
  </div>

  <?php if ($errs): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      <?php foreach ($errs as $e): ?><p><?= e($e) ?></p><?php endforeach ?>
    </div>
  <?php endif ?>

  <form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="space-y-5 rounded-xl border border-border bg-card p-6">
    <?= \App\Core\Csrf::field() ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="code" class="text-sm font-medium">Code <span class="text-destructive">*</span></label>
        <input id="code" name="code" type="text" required value="<?= $val('code') ?>"
               placeholder="BUR-001"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['code']) ? 'border-destructive' : '' ?>">
        <?php if (isset($errs['code'])): ?><p class="text-xs text-destructive"><?= e($errs['code']) ?></p><?php endif ?>
      </div>
      <div class="grid gap-1.5">
        <label for="duration_label" class="text-sm font-medium">Durée</label>
        <input id="duration_label" name="duration_label" type="text" value="<?= $val('duration_label') ?>"
               placeholder="4 semaines"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
    </div>

    <div class="grid gap-1.5">
      <label for="title" class="text-sm font-medium">Titre <span class="text-destructive">*</span></label>
      <input id="title" name="title" type="text" required value="<?= $val('title') ?>"
             class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['title']) ? 'border-destructive' : '' ?>">
      <?php if (isset($errs['title'])): ?><p class="text-xs text-destructive"><?= e($errs['title']) ?></p><?php endif ?>
    </div>

    <div class="grid gap-1.5">
      <label for="summary" class="text-sm font-medium">Résumé</label>
      <textarea id="summary" name="summary" rows="3"
                class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"><?= $val('summary') ?></textarea>
    </div>

    <?php
      $fieldName  = 'program_image';
      $currentUrl = $p['image_url'] ?? '';
      $label      = 'Image du programme';
      $hint       = 'JPG, PNG ou WebP — 2 Mo max.';
      include dirname(__DIR__, 2) . '/partials/image-picker.php';
    ?>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="price" class="text-sm font-medium">Tarif (GNF)</label>
        <input id="price" name="price" type="number" min="0" step="500" value="<?= $val('price', '0') ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1.5">
        <label for="promo_price" class="text-sm font-medium">Tarif promotionnel (optionnel)</label>
        <input id="promo_price" name="promo_price" type="number" min="0" step="500" value="<?= $val('promo_price') ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="level" class="text-sm font-medium">Niveau</label>
        <select id="level" name="level" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <?php foreach (['debutant' => 'Débutant', 'intermediaire' => 'Intermédiaire', 'avance' => 'Avancé'] as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($val('level', 'debutant') === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="grid gap-1.5">
        <label for="category_id" class="text-sm font-medium">Catégorie</label>
        <select id="category_id" name="category_id" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="">— Aucune catégorie —</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= e($cat['id']) ?>" <?= ($val('category_id') === $cat['id']) ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <div class="flex flex-wrap gap-6">
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="is_published" value="1" class="size-4 rounded"
               <?= ($old['is_published'] ?? $p['is_published'] ?? 0) ? 'checked' : '' ?>>
        <span class="text-sm font-medium">Publier sur le site</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="certificate_available" value="1" class="size-4 rounded"
               <?= ($old['certificate_available'] ?? $p['certificate_available'] ?? 0) ? 'checked' : '' ?>>
        <span class="text-sm font-medium">Certificat disponible</span>
      </label>
      <label class="flex items-center gap-2 cursor-pointer">
        <input type="checkbox" name="allow_installments" value="1" class="size-4 rounded"
               <?= ($old['allow_installments'] ?? $p['allow_installments'] ?? 0) ? 'checked' : '' ?>>
        <span class="text-sm font-medium">Paiement en versements</span>
      </label>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="<?= $base ?>/app/catalogue" class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Annuler</a>
      <button type="submit" class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        <?= $isEdit ? 'Enregistrer' : 'Créer le programme' ?>
      </button>
    </div>
  </form>

</div>
