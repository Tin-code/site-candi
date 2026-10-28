<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$e    = $enrollment ?? [];

$statusLabels = [
    'brouillon'            => 'Brouillon',
    'nouvelle_demande'     => 'Nouvelle demande',
    'a_contacter'          => 'À contacter',
    'contacte'             => 'Contacté',
    'en_attente_documents' => 'En att. documents',
    'en_attente_paiement'  => 'En att. paiement',
    'paiement_partiel'     => 'Paiement partiel',
    'confirmee'            => 'Confirmée',
    'en_cours'             => 'En cours',
    'terminee'             => 'Terminée',
    'abandonnee'           => 'Abandonnée',
    'annulee'              => 'Annulée',
    'refusee'              => 'Refusée',
    'remboursee'           => 'Remboursée',
];

$status = $e['status'] ?? '';
$badgeCls = match(true) {
    in_array($status, ['confirmee','en_cours'], true)           => 'bg-success/15 text-success',
    in_array($status, ['terminee'], true)                       => 'bg-muted text-muted-foreground',
    in_array($status, ['annulee','refusee','abandonnee'], true) => 'bg-destructive/10 text-destructive',
    default                                                     => 'bg-primary/10 text-primary',
};
$badgeLabel = $statusLabels[$status] ?? $status;
?>

<div class="mx-auto max-w-2xl space-y-6">

  <!-- En-tête -->
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <a href="<?= $base ?>/app/inscriptions"
         class="mb-2 inline-flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground">
        ← Retour aux inscriptions
      </a>
      <h1 class="font-display text-xl font-bold"><?= e($e['program_title'] ?? 'Formation') ?></h1>
      <p class="text-sm text-muted-foreground"><?= e($e['enrollment_number'] ?? '—') ?></p>
    </div>
    <span class="inline-flex items-center rounded-full px-3 py-1 text-sm font-medium <?= $badgeCls ?>">
      <?= e($badgeLabel) ?>
    </span>
  </div>

  <!-- Détails -->
  <div class="rounded-xl border border-border bg-card divide-y divide-border">

    <div class="grid grid-cols-2 gap-4 px-5 py-4">
      <div>
        <p class="text-xs text-muted-foreground">Formation</p>
        <p class="text-sm font-medium"><?= e($e['program_title'] ?? '—') ?></p>
      </div>
      <div>
        <p class="text-xs text-muted-foreground">Date de début</p>
        <p class="text-sm font-medium"><?= $e['session_start'] ? Format::date($e['session_start']) : '—' ?></p>
      </div>
      <div>
        <p class="text-xs text-muted-foreground">Lieu / Modalité</p>
        <p class="text-sm font-medium">
          <?= e(trim(($e['session_location'] ?? '') . ' ' . ($e['session_modality'] ?? ''))) ?: '—' ?>
        </p>
      </div>
      <div>
        <p class="text-xs text-muted-foreground">Demande créée le</p>
        <p class="text-sm font-medium"><?= Format::date($e['created_at'] ?? '') ?></p>
      </div>
    </div>

    <!-- Finances -->
    <div class="px-5 py-4">
      <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Finances</p>
      <div class="grid grid-cols-3 gap-4">
        <div>
          <p class="text-xs text-muted-foreground">Montant convenu</p>
          <p class="font-medium"><?= Format::money((float) ($e['agreed_amount'] ?? 0)) ?></p>
        </div>
        <div>
          <p class="text-xs text-muted-foreground">Déjà payé</p>
          <p class="font-medium text-success"><?= Format::money((float) ($e['paid_amount'] ?? 0)) ?></p>
        </div>
        <div>
          <p class="text-xs text-muted-foreground">Reste à payer</p>
          <p class="font-medium text-destructive">
            <?= Format::money(max(0, (float) ($e['agreed_amount'] ?? 0) - (float) ($e['paid_amount'] ?? 0))) ?>
          </p>
        </div>
      </div>
    </div>

    <?php if ($isStaff ?? false): ?>
      <!-- Apprenant (staff only) -->
      <div class="px-5 py-4">
        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground">Apprenant</p>
        <p class="text-sm font-medium">
          <?= e(trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? ''))) ?: '—' ?>
        </p>
        <p class="text-xs text-muted-foreground"><?= e($e['user_email'] ?? '') ?></p>
        <?php if ($e['client_phone'] ?? ''): ?>
          <p class="text-xs text-muted-foreground"><?= e($e['client_phone']) ?></p>
        <?php endif ?>
      </div>
    <?php endif ?>

  </div>

  <?php if ($isStaff ?? false): ?>
    <!-- Panneau d'édition staff (tous statuts) -->
    <div class="rounded-xl border border-border bg-card p-5 space-y-4">
      <h2 class="text-sm font-semibold text-muted-foreground uppercase tracking-wider">Modifier le dossier</h2>
      <form method="POST" action="<?= $base ?>/app/inscriptions/<?= e($e['id']) ?>/modifier" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>

        <div class="grid gap-4 sm:grid-cols-2">
          <!-- Statut -->
          <div class="space-y-1.5 sm:col-span-2">
            <label class="block text-sm font-medium" for="edit_status">Statut</label>
            <select name="status" id="edit_status" class="app-input w-full">
              <?php foreach (($statuses ?? []) as $s): ?>
                <option value="<?= e($s) ?>" <?= $s === $status ? 'selected' : '' ?>>
                  <?= e($statusLabels[$s] ?? $s) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>

          <!-- Montant convenu -->
          <div class="space-y-1.5">
            <label class="block text-sm font-medium" for="edit_amount">Montant convenu (GNF)</label>
            <input type="number" name="agreed_amount" id="edit_amount" min="0" step="500"
                   class="app-input w-full" value="<?= (int) ($e['agreed_amount'] ?? 0) ?>">
          </div>

          <!-- Remise -->
          <div class="space-y-1.5">
            <label class="block text-sm font-medium" for="edit_discount">Remise (GNF)</label>
            <input type="number" name="discount" id="edit_discount" min="0" step="500"
                   class="app-input w-full" value="<?= (int) ($e['discount'] ?? 0) ?>">
          </div>

          <!-- Prochain suivi -->
          <div class="space-y-1.5">
            <label class="block text-sm font-medium" for="edit_followup">Prochain suivi</label>
            <input type="date" name="next_followup_at" id="edit_followup" class="app-input w-full"
                   value="<?= e($e['next_followup_at'] ? date('Y-m-d', strtotime($e['next_followup_at'])) : '') ?>">
          </div>
        </div>

        <!-- Notes de contact -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="edit_contact_notes">Notes de contact</label>
          <textarea name="contact_notes" id="edit_contact_notes" rows="2" class="app-input w-full"
                    placeholder="Notes sur le dernier contact téléphonique, email, etc."><?= e($e['contact_notes'] ?? '') ?></textarea>
          <p class="text-xs text-muted-foreground">Remplir cette case met à jour la date du dernier contact.</p>
        </div>

        <!-- Notes internes -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="edit_notes">Notes internes</label>
          <textarea name="notes" id="edit_notes" rows="2" class="app-input w-full"><?= e($e['notes'] ?? '') ?></textarea>
        </div>

        <div class="flex justify-end">
          <button type="submit" class="app-btn-primary">Enregistrer les modifications</button>
        </div>
      </form>
    </div>

  <?php else: ?>
    <!-- Bouton paiement client -->
    <?php
      $due = (float) ($e['agreed_amount'] ?? 0) - (float) ($e['paid_amount'] ?? 0);
      $canPay = $due > 0 && in_array($status, ['en_attente_paiement','paiement_partiel','confirmee'], true);
    ?>
    <?php if ($canPay): ?>
      <a href="<?= $base ?>/app/paiements"
         class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        Payer <?= Format::money($due) ?>
      </a>
    <?php endif ?>
  <?php endif ?>

</div>

<style>
.app-input {
  border-radius: .5rem; border: 1px solid var(--border);
  background: var(--background);
  padding: .4375rem .75rem; font-size: .875rem; color: var(--foreground);
  outline: none; transition: border-color .15s; width: 100%;
}
.app-input:focus { border-color: var(--primary); }
.app-btn-primary {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; background: var(--primary); color: var(--primary-foreground);
  padding: .4375rem 1.25rem; font-size: .875rem; font-weight: 600;
  cursor: pointer; border: none; transition: opacity .15s;
}
.app-btn-primary:hover { opacity: .88; }
.w-full { width: 100%; }
</style>
