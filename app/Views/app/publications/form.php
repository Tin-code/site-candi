<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$p    = $post ?? [];
$old  = $old ?? [];
$errs = $errors ?? [];
$val  = fn(string $k, string $def = '') => e($old[$k] ?? $p[$k] ?? $def);

$isEdit = !empty($p['id']);
$action = $isEdit ? $base . '/app/publications/' . e($p['id']) : $base . '/app/publications';

$categories = [
    'actualite' => 'Actualité',
    'evenement' => 'Événement',
    'formation' => 'Formation',
    'partenariat' => 'Partenariat',
    'communique' => 'Communiqué',
];
?>

<div class="mx-auto max-w-2xl space-y-6">

  <div>
    <a href="<?= $base ?>/app/publications" class="mb-2 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">← Retour aux publications</a>
    <h1 class="font-display text-xl font-bold"><?= $isEdit ? 'Modifier la publication' : 'Nouvelle publication' ?></h1>
  </div>

  <?php if ($errs): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      <?php foreach ($errs as $err): ?><p><?= e($err) ?></p><?php endforeach ?>
    </div>
  <?php endif ?>

  <form method="POST" action="<?= $action ?>" enctype="multipart/form-data" class="space-y-4 rounded-xl border border-border bg-card p-6">
    <?= \App\Core\Csrf::field() ?>

    <div class="grid gap-1.5">
      <label for="title" class="text-sm font-medium">Titre <span class="text-destructive">*</span></label>
      <input id="title" name="title" type="text" required value="<?= $val('title') ?>"
             class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['title']) ? 'border-destructive' : '' ?>">
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="slug" class="text-sm font-medium">Adresse web (slug)</label>
        <input id="slug" name="slug" type="text" value="<?= $val('slug') ?>" placeholder="mon-article"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        <p class="text-xs text-muted-foreground">Laissez vide pour générer automatiquement.</p>
      </div>
      <div class="grid gap-1.5">
        <label for="category" class="text-sm font-medium">Rubrique</label>
        <select id="category" name="category" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <?php foreach ($categories as $k => $lbl): ?>
            <option value="<?= $k ?>" <?= ($val('category', 'actualite') === $k) ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>

    <?php
      $fieldName  = 'cover_image';
      $currentUrl = $p['cover_url'] ?? '';
      $label      = 'Image de couverture';
      $hint       = 'JPG, PNG ou WebP — 2 Mo max.';
      include dirname(__DIR__, 2) . '/partials/image-picker.php';
    ?>

    <div class="grid gap-1.5">
      <label for="video_url" class="text-sm font-medium">URL de vidéo (YouTube, Facebook, direct…)</label>
      <input id="video_url" name="video_url" type="url" value="<?= $val('video_url') ?>"
             placeholder="https://youtube.com/watch?v=…"
             class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
    </div>

    <div class="grid gap-1.5">
      <label for="excerpt" class="text-sm font-medium">Résumé</label>
      <textarea id="excerpt" name="excerpt" rows="2"
                class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"><?= $val('excerpt') ?></textarea>
    </div>

    <div class="grid gap-1.5">
      <label for="content" class="text-sm font-medium">Contenu <span class="text-destructive">*</span></label>
      <textarea id="content" name="content" rows="12" required
                class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary <?= isset($errs['content']) ? 'border-destructive' : '' ?>"><?= $val('content') ?></textarea>
      <p class="text-xs text-muted-foreground">Laissez une ligne vide entre les paragraphes pour les séparer.</p>
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
      <div class="grid gap-1.5">
        <label for="status" class="text-sm font-medium">Statut</label>
        <select id="status" name="status" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
          <option value="brouillon" <?= ($val('status', 'brouillon') === 'brouillon') ? 'selected' : '' ?>>Brouillon</option>
          <option value="publie" <?= ($val('status') === 'publie') ? 'selected' : '' ?>>Publié</option>
        </select>
      </div>
      <div class="grid gap-1.5">
        <label for="published_at" class="text-sm font-medium">Date de publication</label>
        <input id="published_at" name="published_at" type="datetime-local"
               value="<?= $val('published_at') ? date('Y-m-d\TH:i', strtotime($val('published_at'))) : '' ?>"
               class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
    </div>

    <div class="flex gap-3 pt-2">
      <a href="<?= $base ?>/app/publications" class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Annuler</a>
      <button type="submit" class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        <?= $isEdit ? 'Enregistrer' : 'Créer la publication' ?>
      </button>
    </div>
  </form>

</div>
