<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$payStatusLabels = [
    'initie' => 'En cours',
    'reussi' => 'Réussi',
    'echoue' => 'Échoué',
    'annule' => 'Annulé',
];
$payStatusCls = [
    'initie' => 'bg-primary/10 text-primary',
    'reussi' => 'bg-success/15 text-success',
    'echoue' => 'bg-destructive/10 text-destructive',
    'annule' => 'bg-muted text-muted-foreground',
];
?>

<div class="space-y-6">

  <header>
    <h1 class="font-display text-2xl font-bold flex items-center gap-2">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 0a.75.75 0 01-.75.75h-.75m0 0a.75.75 0 000 1.5h.75m0-1.5h3m0 0v.375m0 0v.375a.75.75 0 01-.75.75H9.75a.75.75 0 01-.75-.75V9"/>
      </svg>
      Payer en ligne
    </h1>
    <p class="text-sm text-muted-foreground">Réglez vos formations CaNDI directement avec CinetPay.</p>
  </header>

  <?php if (!($enabled ?? false)): ?>
    <div class="rounded-xl border border-border bg-card p-6 text-sm text-muted-foreground">
      Le paiement en ligne sera bientôt disponible. En attendant, vous pouvez payer directement
      dans nos agences ou contacter notre équipe.
    </div>

  <?php elseif (empty($pending)): ?>
    <div class="rounded-xl border border-border bg-card p-6 text-sm text-muted-foreground">
      Aucun paiement en attente. Si vous attendez une inscription, contactez notre équipe.
    </div>

  <?php else: ?>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($pending as $enroll): ?>
        <?php
          $due  = (float) $enroll['agreed_amount'] - (float) $enroll['paid_amount'];
          $paid = (float) $enroll['paid_amount'];
        ?>
        <div class="rounded-xl border border-border bg-card p-5 space-y-3">
          <div>
            <h2 class="font-semibold"><?= e($enroll['program_title'] ?? 'Formation') ?></h2>
            <p class="text-sm text-muted-foreground">
              <?= e($enroll['enrollment_number'] ?? '') ?> ·
              Payé : <strong class="text-success"><?= Format::money($paid) ?></strong> /
              Convenu : <?= Format::money((float) $enroll['agreed_amount']) ?>
            </p>
          </div>

          <div class="rounded-lg bg-muted/50 px-4 py-2 flex items-center justify-between">
            <span class="text-sm text-muted-foreground">Solde dû</span>
            <span class="font-bold text-lg text-primary"><?= Format::money($due) ?></span>
          </div>

          <form method="POST"
                action="<?= $base ?>/app/paiements/<?= e($enroll['id']) ?>/payer"
                onsubmit="this.querySelector('button[type=submit]').disabled=true;this.querySelector('button[type=submit]').textContent='Redirection…'">
            <?= \App\Core\Csrf::field() ?>
            <div class="space-y-1.5">
              <label class="block text-xs text-muted-foreground" for="amount_<?= e($enroll['id']) ?>">
                Montant à payer (GNF) — laissez tel quel pour tout régler
              </label>
              <input type="number" name="amount" id="amount_<?= e($enroll['id']) ?>"
                     min="500" max="<?= (int) $due ?>" step="500"
                     value="<?= (int) $due ?>"
                     class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-sm outline-none focus:border-primary">
            </div>
            <button type="submit"
                    class="mt-3 w-full rounded-lg bg-primary px-4 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 transition-opacity">
              Payer avec CinetPay
            </button>
          </form>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

  <!-- Historique -->
  <?php if (!empty($history)): ?>
    <div class="rounded-xl border border-border bg-card">
      <div class="border-b border-border px-5 py-4">
        <h2 class="text-sm font-semibold">Historique des paiements en ligne</h2>
      </div>
      <div class="divide-y divide-border">
        <?php foreach ($history as $h): ?>
          <div class="flex flex-wrap items-center justify-between gap-2 px-5 py-3 text-sm">
            <div>
              <p class="font-medium"><?= e($h['program_title'] ?? 'Formation') ?></p>
              <p class="text-xs text-muted-foreground">
                <?= Format::date($h['created_at']) ?>
                <?php if ($h['txnid'] ?? ''): ?>
                  · réf. <?= e($h['txnid']) ?>
                <?php endif ?>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <strong><?= Format::money((float) $h['amount']) ?></strong>
              <?php $hs = $h['status'] ?? 'initie'; ?>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $payStatusCls[$hs] ?? 'bg-muted text-muted-foreground' ?>">
                <?= e($payStatusLabels[$hs] ?? $hs) ?>
              </span>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  <?php endif ?>

</div>
