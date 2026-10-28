<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$fmt = fn($n) => number_format((float)$n, 0, ',', ' ');

$statusLabels = [
    'en_attente' => ['En vérification', 'bg-amber-100 text-amber-800'],
    'validee'    => ['Validé',          'bg-emerald-100 text-emerald-800'],
    'refusee'    => ['Refusé',          'bg-red-100 text-red-800'],
];
$methodLabels = [
    'especes'      => 'Espèces',
    'orange_money' => 'Orange Money',
    'cinetpay'     => 'CinetPay',
    'virement'     => 'Virement bancaire',
    'cheque'       => 'Chèque',
    'autre'        => 'Autre',
    'mobile_money' => 'Mobile Money',
];
$methodOptions = ['especes', 'orange_money', 'cinetpay', 'virement', 'cheque', 'autre'];
?>

<div class="space-y-6">

  <div>
    <h1 class="font-display text-xl font-bold">Mon logement</h1>
    <p class="text-sm text-muted-foreground">Suivez votre loyer et déclarez vos paiements</p>
  </div>

  <?php if (!$lease): ?>
  <!-- No lease: claim form -->
  <div class="mx-auto max-w-md space-y-4">
    <div class="rounded-xl border border-border bg-card p-6 space-y-4">
      <div class="flex items-center gap-3">
        <div class="flex size-10 items-center justify-center rounded-full bg-primary/10 text-primary">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
          </svg>
        </div>
        <div>
          <h2 class="font-semibold">Rattacher mon appartement</h2>
          <p class="text-xs text-muted-foreground">Entrez le code communiqué par la gestion CaNDI</p>
        </div>
      </div>

      <p class="text-sm text-muted-foreground bg-muted rounded-lg px-4 py-3">
        Si votre bail a été créé récemment avec votre numéro de téléphone, votre logement devrait
        apparaître automatiquement. Sinon, saisissez le code d'accès fourni par la gestion.
      </p>

      <form method="POST" action="<?= $base ?>/app/mon-logement/relier" class="space-y-3">
        <?= \App\Core\Csrf::field() ?>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="code">Code de l'appartement</label>
          <input type="text" name="code" id="code" required
                 class="app-input w-full font-mono uppercase" placeholder="LOC-XXXXXX"
                 maxlength="20" autocomplete="off">
          <p class="text-xs text-muted-foreground">
            Format : LOC-XXXXXX · Vous n'avez pas de code ? Contactez la gestion immobilière.
          </p>
        </div>
        <button type="submit" class="app-btn-primary w-full">Valider mon code</button>
      </form>
    </div>
  </div>

  <?php else: ?>
  <!-- Active lease dashboard -->

  <?php
  // Compute months with unpaid rent
  $startDate = new DateTime($lease['start_month']);
  $today = new DateTime();
  $dueMonths = [];
  $pendingMonths = [];
  $paidByMonth = [];

  // Build payment maps
  // validatedByMonth: validated payments only (for dueMonths, like React's paidBy)
  // coveredByMonth: non-refused payments (for debt calculation, like React's coveredBy)
  $validatedByMonth = [];
  $coveredByMonth   = [];
  foreach ($payments as $p) {
    $mk = date('Y-m', strtotime($p['period_month']));
    if ($p['status'] === 'en_attente') {
      $pendingMonths[$mk] = true;
    }
    if ($p['status'] === 'validee') {
      $validatedByMonth[$mk] = ($validatedByMonth[$mk] ?? 0) + (float) $p['amount'];
    }
    if ($p['status'] !== 'refusee') {
      $coveredByMonth[$mk] = ($coveredByMonth[$mk] ?? 0) + (float) $p['amount'];
    }
  }

  $rent    = (float) $lease['monthly_rent'];
  $cursor  = clone $startDate;
  $currentMk = $today->format('Y-m');
  while ($cursor->format('Y-m') <= $currentMk) {
    $mk = $cursor->format('Y-m');
    if (($validatedByMonth[$mk] ?? 0) < $rent) {
      $dueMonths[] = $mk;
    }
    $cursor->modify('+1 month');
  }

  $debt = 0;
  foreach ($dueMonths as $mk) {
    $debt += max($rent - ($coveredByMonth[$mk] ?? 0), 0);
  }

  // Suggested month: first due month with no pending payment
  $suggestedMonth = date('Y-m');
  foreach ($dueMonths as $mk) {
    if (!isset($pendingMonths[$mk])) {
      $suggestedMonth = $mk;
      break;
    }
  }
  ?>

  <!-- Stats -->
  <div class="grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Appartement</p>
      <p class="font-display text-lg font-bold"><?= e($lease['apartment_label'] ?? '—') ?></p>
      <?php if ($lease['property_name']): ?>
        <p class="text-xs text-muted-foreground"><?= e($lease['property_name']) ?><?= $lease['property_city'] ? ' · ' . e($lease['property_city']) : '' ?></p>
      <?php endif ?>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Loyer mensuel</p>
      <p class="font-display text-lg font-bold text-primary"><?= $fmt($lease['monthly_rent']) ?> GNF</p>
      <p class="text-xs text-muted-foreground">Depuis <?= date('M Y', strtotime($lease['start_month'])) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Reste à payer</p>
      <p class="font-display text-lg font-bold <?= $debt > 0 ? 'text-destructive' : 'text-primary' ?>">
        <?= $fmt($debt) ?> GNF
      </p>
      <p class="text-xs text-muted-foreground">
        <?= count($dueMonths) > 0 ? count($dueMonths) . ' mois concerné(s)' : 'Vous êtes à jour ✓' ?>
      </p>
    </div>
  </div>

  <!-- Due months -->
  <?php if (!empty($dueMonths)): ?>
  <div class="rounded-xl border border-border bg-card p-4 space-y-3">
    <h2 class="font-semibold text-sm">Mois avec loyer dû</h2>
    <div class="flex flex-wrap gap-2">
      <?php foreach ($dueMonths as $mk): ?>
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-medium
                     <?= isset($pendingMonths[$mk]) ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-800' ?>">
          <?= date('M Y', strtotime($mk . '-01')) ?>
          <?= isset($pendingMonths[$mk]) ? ' · en cours de vérification' : '' ?>
        </span>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- Declare payment -->
  <div class="rounded-xl border border-border bg-card p-6">
    <h2 class="font-semibold text-sm mb-4">Déclarer un paiement</h2>
    <form method="POST" action="<?= $base ?>/app/mon-logement/paiement" class="space-y-4">
      <?= \App\Core\Csrf::field() ?>

      <div class="grid gap-4 sm:grid-cols-2">
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="period_month">Mois payé <span class="text-destructive">*</span></label>
          <input type="month" name="period_month" id="period_month" required class="app-input w-full"
                 value="<?= e($suggestedMonth) ?>">
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="pay_amount">Montant (GNF) <span class="text-destructive">*</span></label>
          <input type="number" name="amount" id="pay_amount" required min="0" step="500" class="app-input w-full"
                 value="<?= (int) $lease['monthly_rent'] ?>">
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="payment_method">Moyen de paiement</label>
          <select name="payment_method" id="payment_method" class="app-input w-full">
            <?php foreach ($methodOptions as $v): ?>
              <option value="<?= $v ?>" <?= $v === 'orange_money' ? 'selected' : '' ?>><?= $methodLabels[$v] ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="paid_at">Date du paiement</label>
          <input type="date" name="paid_at" id="paid_at" class="app-input w-full" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="payer_phone">Numéro utilisé</label>
          <input type="tel" name="payer_phone" id="payer_phone" class="app-input w-full"
                 placeholder="Votre n° Orange Money…" value="<?= e($lease['tenant_phone'] ?? '') ?>">
        </div>
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="provider_reference">Référence transaction</label>
          <input type="text" name="provider_reference" id="provider_reference" class="app-input w-full"
                 placeholder="Ex : PP260922.1234.A56789">
        </div>
        <div class="space-y-1.5 sm:col-span-2">
          <label class="block text-sm font-medium" for="pay_notes">Notes (optionnel)</label>
          <textarea name="notes" id="pay_notes" rows="2" class="app-input w-full"
                    placeholder="Informations complémentaires sur ce paiement…"></textarea>
        </div>
      </div>

      <p class="text-xs text-muted-foreground bg-muted/50 rounded-lg px-3 py-2">
        Après déclaration, la gestion vérifiera votre paiement et le validera. Vous serez notifié.
      </p>

      <div class="flex justify-end">
        <button type="submit" class="app-btn-primary">Déclarer mon paiement</button>
      </div>
    </form>
  </div>

  <!-- Payment history -->
  <?php if (!empty($payments)): ?>
  <div class="rounded-xl border border-border bg-card overflow-hidden">
    <div class="border-b border-border px-4 py-3">
      <h2 class="font-semibold text-sm">Mes paiements</h2>
    </div>
    <div class="divide-y divide-border">
      <?php foreach ($payments as $p):
        [$statusLabel, $statusCls] = $statusLabels[$p['status']] ?? [$p['status'], 'bg-muted text-muted-foreground'];
      ?>
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
          <div>
            <p class="font-medium"><?= date('F Y', strtotime($p['period_month'])) ?></p>
            <p class="text-xs text-muted-foreground">
              <?= $methodLabels[$p['payment_method']] ?? e($p['payment_method']) ?>
              · <?= date('d/m/Y', strtotime($p['paid_at'])) ?>
              <?= $p['provider_reference'] ? ' · réf. ' . e($p['provider_reference']) : '' ?>
            </p>
          </div>
          <div class="flex items-center gap-3">
            <span class="font-semibold text-primary"><?= $fmt($p['amount']) ?> GNF</span>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $statusCls ?>">
              <?= $statusLabel ?>
            </span>
            <?php if ($p['status'] === 'validee'): ?>
              <a href="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/quittance"
                 class="text-xs text-muted-foreground hover:text-foreground" title="Télécharger la quittance">📄</a>
            <?php endif ?>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
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

<?php if ($lease): ?>
<script>
(function() {
  const rent     = <?= (int) ($lease['monthly_rent'] ?? 0) ?>;
  const validated = <?= json_encode($validatedByMonth ?? []) ?>;
  const form = document.querySelector('form[action*="mon-logement/paiement"]');
  if (!form) return;
  form.addEventListener('submit', function(e) {
    const monthInput = form.querySelector('[name="period_month"]');
    if (!monthInput || !rent) return;
    const mk = monthInput.value; // YYYY-MM
    const already = (validated[mk] || 0);
    if (already >= rent) {
      e.preventDefault();
      if (!confirm('Le loyer de ce mois est déjà couvert (' + already.toLocaleString('fr-FR') + ' GNF validés). Ajouter quand même ce paiement ?')) {
        return;
      }
      form.submit();
    }
  });
})();
</script>
<?php endif ?>
