<?php
use function App\Core\e;
use App\Helpers\Format;

$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$eventLabels = [
    'inscription_confirmee' => 'Inscription confirmée',
    'loyer_valide'          => 'Loyer validé',
    'rappel_j2'             => 'Rappel J-2',
    'rappel_jour_j'         => 'Rappel jour J',
];

$statusConfig = [
    'en_attente' => ['label' => 'En attente', 'cls' => 'bg-amber-100 text-amber-700'],
    'envoye'     => ['label' => 'Envoyé',     'cls' => 'bg-green-100 text-green-700'],
    'echec'      => ['label' => 'Échec',      'cls' => 'bg-red-100 text-red-700'],
    'pending'    => ['label' => 'En attente', 'cls' => 'bg-amber-100 text-amber-700'],
];

$messages = $messages ?? [];
?>

<div class="space-y-6">

  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">SMS automatiques</h1>
      <p class="text-sm text-muted-foreground">Confirmations d'inscription, paiements, loyers validés et rappels J-2 / matin même.</p>
    </div>
    <form method="POST" action="<?= $base ?>/app/messages-sms/envoyer">
      <?= \App\Core\Csrf::field() ?>
      <button type="submit"
              class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/>
        </svg>
        Envoyer maintenant
      </button>
    </form>
  </div>

  <?php if (!empty($info)): ?>
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><?= e($info) ?></div>
  <?php endif ?>
  <?php if (!empty($success)): ?>
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= e($success) ?></div>
  <?php endif ?>

  <div class="rounded-xl border border-border bg-card">
    <div class="border-b border-border px-5 py-3">
      <h2 class="font-display text-base font-semibold">Historique</h2>
    </div>

    <?php if (empty($messages)): ?>
      <div class="px-5 py-12 text-center text-sm text-muted-foreground">Aucun SMS pour le moment.</div>
    <?php else: ?>
      <div class="divide-y divide-border">
        <?php foreach ($messages as $m): ?>
          <?php
          $eventLabel = $eventLabels[$m['event']] ?? (str_starts_with($m['event'], 'paiement') ? 'Paiement reçu' : e($m['event']));
          $sc = $statusConfig[$m['status']] ?? ['label' => e($m['status']), 'cls' => 'bg-muted text-muted-foreground'];
          ?>
          <div class="flex flex-wrap items-start justify-between gap-2 px-5 py-3">
            <div class="min-w-0 flex-1">
              <p class="text-sm"><?= e($m['body']) ?></p>
              <p class="mt-0.5 text-xs text-muted-foreground">
                <?= e($m['to_phone']) ?> · <?= $eventLabel ?> · <?= Format::dateTime($m['created_at']) ?>
                <?php if ($m['error']): ?> · <span class="text-destructive"><?= e($m['error']) ?></span><?php endif ?>
              </p>
            </div>
            <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium <?= $sc['cls'] ?>"><?= $sc['label'] ?></span>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>

</div>
