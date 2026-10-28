<?php
use function App\Core\e;
$base   = rtrim(\App\Core\env('APP_URL', ''), '/');
$old    = $old    ?? [];
$errors = $errors ?? [];

$statusLabels = [
    'nouvelle_demande'     => 'Nouvelle demande',
    'a_contacter'          => 'À contacter',
    'contacte'             => 'Contacté',
    'en_attente_documents' => 'En attente de documents',
    'en_attente_paiement'  => 'En attente de paiement',
    'confirmee'            => 'Confirmée',
    'en_cours'             => 'En cours',
];

$paymentModes = [
    'comptant'   => 'Comptant (paiement intégral)',
    'mensualite' => 'Mensualités',
    'tranche'    => 'En tranches',
];

// All sessions encoded for JS filtering
$sessionsJson = json_encode(array_map(fn($s) => [
    'id'         => $s['id'],
    'program_id' => $s['program_id'],
    'label'      => ($s['start_date'] ?? '?') . ' — ' . ($s['location'] ?? ($s['modality'] ?? 'En ligne')),
], $sessions ?? []), JSON_HEX_TAG);
?>

<div class="mx-auto max-w-2xl space-y-6">

  <div class="flex items-center gap-3">
    <a href="<?= $base ?>/app/inscriptions"
       class="rounded-md border border-border p-1.5 hover:bg-muted" title="Retour">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
      </svg>
    </a>
    <div>
      <h1 class="font-display text-xl font-bold">Nouvelle inscription</h1>
      <p class="text-sm text-muted-foreground">Inscrivez un apprenant à une formation</p>
    </div>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      Veuillez corriger les erreurs ci-dessous avant de continuer.
    </div>
  <?php endif ?>

  <form method="POST" action="<?= $base ?>/app/inscriptions" class="space-y-8">
    <?= \App\Core\Csrf::field() ?>

    <!-- ── Informations de l'apprenant ── -->
    <fieldset class="space-y-4 rounded-xl border border-border bg-card p-6">
      <legend class="mb-1 -ml-1 px-1 font-display text-base font-semibold">Informations de l'apprenant</legend>
      <p class="text-xs text-muted-foreground -mt-2">
        Si cet apprenant existe déjà (même numéro de téléphone), son profil sera mis à jour.
      </p>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="first_name">Prénom <span class="text-destructive">*</span></label>
          <input type="text" name="first_name" id="first_name" autocomplete="given-name"
                 value="<?= e($old['first_name'] ?? '') ?>"
                 class="app-input w-full <?= !empty($errors['first_name']) ? 'border-destructive' : '' ?>">
          <?php if (!empty($errors['first_name'])): ?>
            <p class="text-xs text-destructive"><?= e($errors['first_name']) ?></p>
          <?php endif ?>
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="last_name">Nom <span class="text-destructive">*</span></label>
          <input type="text" name="last_name" id="last_name" autocomplete="family-name"
                 value="<?= e($old['last_name'] ?? '') ?>"
                 class="app-input w-full <?= !empty($errors['last_name']) ? 'border-destructive' : '' ?>">
          <?php if (!empty($errors['last_name'])): ?>
            <p class="text-xs text-destructive"><?= e($errors['last_name']) ?></p>
          <?php endif ?>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="phone">
            Téléphone <span class="text-destructive">*</span>
          </label>
          <input type="tel" name="phone" id="phone" autocomplete="tel"
                 placeholder="6XX XXX XXX ou 224 6XX…"
                 value="<?= e($old['phone'] ?? '') ?>"
                 class="app-input w-full <?= !empty($errors['phone']) ? 'border-destructive' : '' ?>">
          <p class="text-xs text-muted-foreground">Sert d'identifiant de connexion.</p>
          <?php if (!empty($errors['phone'])): ?>
            <p class="text-xs text-destructive"><?= e($errors['phone']) ?></p>
          <?php endif ?>
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="email">Email</label>
          <input type="email" name="email" id="email" autocomplete="email"
                 value="<?= e($old['email'] ?? '') ?>"
                 class="app-input w-full">
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="city">Ville</label>
          <input type="text" name="city" id="city"
                 value="<?= e($old['city'] ?? '') ?>"
                 class="app-input w-full">
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="profession">Profession</label>
          <input type="text" name="profession" id="profession"
                 value="<?= e($old['profession'] ?? '') ?>"
                 class="app-input w-full">
        </div>
      </div>
    </fieldset>

    <!-- ── Détails de la formation ── -->
    <fieldset class="space-y-4 rounded-xl border border-border bg-card p-6">
      <legend class="mb-1 -ml-1 px-1 font-display text-base font-semibold">Formation &amp; conditions</legend>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="program_id">Formation <span class="text-destructive">*</span></label>
        <select name="program_id" id="program_id" class="app-input w-full"
                class="<?= !empty($errors['program_id']) ? 'border-destructive' : '' ?>">
          <option value="">— Sélectionner une formation —</option>
          <?php foreach ($programs ?? [] as $p): ?>
            <option value="<?= e($p['id']) ?>"
                    data-price="<?= (float) ($p['price'] ?? 0) ?>"
                    <?= ($old['program_id'] ?? '') === $p['id'] ? 'selected' : '' ?>>
              <?= e($p['title']) ?>
              <?php if (!empty($p['price'])): ?>
                — <?= number_format((float)$p['price'], 0, ',', ' ') ?> GNF
              <?php endif ?>
            </option>
          <?php endforeach ?>
        </select>
        <?php if (!empty($errors['program_id'])): ?>
          <p class="text-xs text-destructive"><?= e($errors['program_id']) ?></p>
        <?php endif ?>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="session_id">Session de formation</label>
        <select name="session_id" id="session_id" class="app-input w-full">
          <option value="">— Sélectionner une session (optionnel) —</option>
        </select>
        <p class="text-xs text-muted-foreground">Les sessions disponibles s'affichent après le choix de la formation.</p>
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="agreed_amount">Montant convenu (GNF) <span class="text-destructive">*</span></label>
          <input type="number" name="agreed_amount" id="agreed_amount" min="0" step="500"
                 value="<?= e($old['agreed_amount'] ?? '0') ?>"
                 class="app-input w-full <?= !empty($errors['agreed_amount']) ? 'border-destructive' : '' ?>">
          <?php if (!empty($errors['agreed_amount'])): ?>
            <p class="text-xs text-destructive"><?= e($errors['agreed_amount']) ?></p>
          <?php endif ?>
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="payment_mode">Mode de paiement</label>
          <select name="payment_mode" id="payment_mode" class="app-input w-full">
            <?php foreach ($paymentModes as $val => $lbl): ?>
              <option value="<?= e($val) ?>" <?= ($old['payment_mode'] ?? 'comptant') === $val ? 'selected' : '' ?>>
                <?= e($lbl) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="status">Statut initial</label>
        <select name="status" id="status" class="app-input w-full">
          <?php foreach ($statusLabels as $val => $lbl): ?>
            <option value="<?= e($val) ?>" <?= ($old['status'] ?? 'nouvelle_demande') === $val ? 'selected' : '' ?>>
              <?= e($lbl) ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="notes">Notes internes</label>
        <textarea name="notes" id="notes" rows="3" placeholder="Remarques, conditions particulières…"
                  class="app-input w-full resize-y"><?= e($old['notes'] ?? '') ?></textarea>
      </div>
    </fieldset>

    <div class="flex items-center justify-end gap-3">
      <a href="<?= $base ?>/app/inscriptions" class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">
        Annuler
      </a>
      <button type="submit" class="app-btn-primary">
        Créer l'inscription
      </button>
    </div>
  </form>

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
</style>

<script>
(function () {
  const sessions   = <?= $sessionsJson ?>;
  const programSel = document.getElementById('program_id');
  const sessionSel = document.getElementById('session_id');
  const amountEl   = document.getElementById('agreed_amount');
  const savedSess  = <?= json_encode($old['session_id'] ?? '') ?>;

  function filterSessions(programId) {
    const opts = sessions.filter(s => s.program_id === programId);
    sessionSel.innerHTML = '<option value="">— Sélectionner une session (optionnel) —</option>';
    opts.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.label || s.id;
      if (s.id === savedSess) opt.selected = true;
      sessionSel.appendChild(opt);
    });
  }

  programSel.addEventListener('change', function () {
    filterSessions(this.value);
    const price = parseFloat(this.options[this.selectedIndex]?.dataset?.price || '0');
    if (price > 0) amountEl.value = price;
  });

  if (programSel.value) filterSessions(programSel.value);
})();
</script>
