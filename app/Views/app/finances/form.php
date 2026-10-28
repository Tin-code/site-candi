<?php
use function App\Core\e;
$base   = rtrim(\App\Core\env('APP_URL', ''), '/');
$old    = $old    ?? [];
$errors = $errors ?? [];
$tx     = $tx     ?? null;
$isEdit = $tx !== null;
$v      = fn(string $k, $def = '') => $old[$k] ?? ($tx[$k] ?? $def);
?>

<div class="mx-auto max-w-2xl space-y-6">

  <div class="flex items-center gap-3">
    <a href="<?= $base ?>/app/finances"
       class="rounded-md border border-border p-1.5 hover:bg-muted" title="Retour">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
      </svg>
    </a>
    <div>
      <h1 class="font-display text-xl font-bold"><?= $isEdit ? "Modifier l'opération" : 'Nouvelle opération financière' ?></h1>
      <p class="text-sm text-muted-foreground">Enregistrez une entrée ou une sortie d'argent</p>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      Veuillez corriger les erreurs ci-dessous.
    </div>
  <?php endif ?>

  <?php if (empty($accounts)): ?>
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
      Aucun compte financier configuré.
      <a href="<?= $base ?>/app/parametres#finances" class="font-semibold underline">Créer un compte dans Paramètres →</a>
    </div>
  <?php endif ?>

  <form method="POST"
        action="<?= $base ?>/app/finances<?= $isEdit ? '/' . e($tx['id']) : '' ?>"
        class="space-y-5 rounded-xl border border-border bg-card p-6">
    <?= \App\Core\Csrf::field() ?>

    <!-- Sens + Date -->
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Type d'opération <span class="text-destructive">*</span></label>
        <div class="flex gap-4 pt-1">
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="direction" value="entree"
                   <?= ($v('direction', 'entree') === 'entree') ? 'checked' : '' ?> class="accent-primary">
            <span class="text-sm font-medium text-success">↑ Entrée d'argent</span>
          </label>
          <label class="flex items-center gap-2 cursor-pointer">
            <input type="radio" name="direction" value="sortie"
                   <?= ($v('direction', 'entree') === 'sortie') ? 'checked' : '' ?> class="accent-primary">
            <span class="text-sm font-medium text-destructive">↓ Sortie d'argent</span>
          </label>
        </div>
        <p class="text-xs text-muted-foreground">Argent reçu = Entrée · Argent dépensé = Sortie</p>
        <?php if (!empty($errors['direction'])): ?>
          <p class="text-xs text-destructive"><?= e($errors['direction']) ?></p>
        <?php endif ?>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="tx_date">Date de l'opération <span class="text-destructive">*</span></label>
        <input type="date" name="tx_date" id="tx_date"
               value="<?= e($v('tx_date', date('Y-m-d'))) ?>"
               class="app-input w-full <?= !empty($errors['tx_date']) ? 'border-destructive' : '' ?>">
        <p class="text-xs text-muted-foreground">Jour où l'argent a été encaissé ou décaissé</p>
        <?php if (!empty($errors['tx_date'])): ?>
          <p class="text-xs text-destructive"><?= e($errors['tx_date']) ?></p>
        <?php endif ?>
      </div>
    </div>

    <!-- Compte + Montant -->
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="account_id">Compte concerné <span class="text-destructive">*</span></label>
        <select name="account_id" id="account_id"
                class="app-input w-full <?= !empty($errors['account_id']) ? 'border-destructive' : '' ?>">
          <option value="">— Sélectionner —</option>
          <?php foreach ($accounts ?? [] as $a): ?>
            <option value="<?= e($a['id']) ?>" <?= $v('account_id') === $a['id'] ? 'selected' : '' ?>>
              <?= e($a['name']) ?>
            </option>
          <?php endforeach ?>
        </select>
        <p class="text-xs text-muted-foreground">
          Caisse ou compte bancaire concerné ·
          <a href="<?= $base ?>/app/parametres#finances" class="text-primary hover:underline">Gérer les comptes</a>
        </p>
        <?php if (!empty($errors['account_id'])): ?>
          <p class="text-xs text-destructive"><?= e($errors['account_id']) ?></p>
        <?php endif ?>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="amount">Montant (GNF) <span class="text-destructive">*</span></label>
        <input type="number" name="amount" id="amount" min="500" step="500"
               value="<?= e($v('amount', '')) ?>" placeholder="Ex : 500 000"
               class="app-input w-full <?= !empty($errors['amount']) ? 'border-destructive' : '' ?>">
        <p class="text-xs text-muted-foreground">Montant exact en francs guinéens</p>
        <?php if (!empty($errors['amount'])): ?>
          <p class="text-xs text-destructive"><?= e($errors['amount']) ?></p>
        <?php endif ?>
      </div>
    </div>

    <!-- Catégorie + Mode de règlement -->
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="category_id">Catégorie</label>
        <select name="category_id" id="category_id" class="app-input w-full">
          <option value="">— Non classé —</option>
          <?php
          $entrCats = array_filter($categories ?? [], fn($c) => $c['direction'] === 'entree');
          $sortCats = array_filter($categories ?? [], fn($c) => $c['direction'] === 'sortie');
          if ($entrCats): ?><optgroup label="Catégories Entrée"><?php foreach ($entrCats as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= $v('category_id') === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach ?></optgroup><?php endif ?>
          <?php if ($sortCats): ?><optgroup label="Catégories Sortie"><?php foreach ($sortCats as $c): ?>
            <option value="<?= e($c['id']) ?>" <?= $v('category_id') === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach ?></optgroup><?php endif ?>
        </select>
        <p class="text-xs text-muted-foreground">
          Rubrique budgétaire ·
          <a href="<?= $base ?>/app/parametres#finances" class="text-primary hover:underline">Gérer les catégories</a>
        </p>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="payment_method">Mode de paiement</label>
        <select name="payment_method" id="payment_method" class="app-input w-full">
          <?php foreach ([
            'especes'      => 'Espèces (cash)',
            'virement'     => 'Virement bancaire',
            'cheque'       => 'Chèque',
            'mobile_money' => 'Mobile Money',
            'carte'        => 'Carte bancaire',
          ] as $v2 => $l): ?>
            <option value="<?= $v2 ?>" <?= $v('payment_method', 'especes') === $v2 ? 'selected' : '' ?>><?= $l ?></option>
          <?php endforeach ?>
        </select>
        <p class="text-xs text-muted-foreground">Comment l'argent a été remis ou reçu</p>
      </div>
    </div>

    <!-- Libellé -->
    <div class="space-y-1.5">
      <label class="block text-sm font-medium" for="description">Libellé (description courte)</label>
      <input type="text" name="description" id="description"
             value="<?= e($v('description', '')) ?>"
             placeholder="Ex : Paiement inscription formation Excel — M. Kouyaté"
             class="app-input w-full">
      <p class="text-xs text-muted-foreground">Courte phrase qui résume l'objet de l'opération</p>
    </div>

    <!-- Tiers + Référence -->
    <div class="grid grid-cols-2 gap-4">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="third_party">Nom du tiers</label>
        <input type="text" name="third_party" id="third_party"
               value="<?= e($v('third_party', '')) ?>"
               placeholder="Ex : Mamadou Diallo, Fournisseur X"
               class="app-input w-full">
        <p class="text-xs text-muted-foreground">Personne ou société qui a payé ou été payée</p>
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="reference">N° de reçu / référence</label>
        <input type="text" name="reference" id="reference"
               value="<?= e($v('reference', '')) ?>"
               placeholder="Ex : REC-2026-0042"
               class="app-input w-full">
        <p class="text-xs text-muted-foreground">Numéro du reçu papier ou référence bancaire</p>
      </div>
    </div>

    <!-- Notes -->
    <div class="space-y-1.5">
      <label class="block text-sm font-medium" for="notes">Notes internes</label>
      <textarea name="notes" id="notes" rows="2"
                placeholder="Informations complémentaires réservées à l'équipe…"
                class="app-input w-full resize-y"><?= e($v('notes', '')) ?></textarea>
      <p class="text-xs text-muted-foreground">Non visibles par les apprenants</p>
    </div>

    <div class="flex items-center justify-end gap-3 border-t border-border pt-4">
      <a href="<?= $base ?>/app/finances"
         class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">Annuler</a>
      <button type="submit" class="app-btn-primary">
        <?= $isEdit ? 'Mettre à jour' : "Enregistrer l'opération" ?>
      </button>
    </div>
  </form>
</div>

<style>
.app-input{border-radius:.5rem;border:1px solid var(--border);background:var(--background);padding:.4375rem .75rem;font-size:.875rem;color:var(--foreground);outline:none;transition:border-color .15s;}
.app-input:focus{border-color:var(--primary);}
.app-btn-primary{display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;background:var(--primary);color:var(--primary-foreground);padding:.4375rem 1.25rem;font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:opacity .15s;}
.app-btn-primary:hover{opacity:.88;}
</style>
