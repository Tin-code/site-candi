<?php
use function App\Core\e;
use App\Helpers\Format;

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
function statusBadge(string $status, array $labels): string {
    $label = $labels[$status] ?? $status;
    $cls = match(true) {
        in_array($status, ['confirmee','en_cours'], true)             => 'bg-success/15 text-success',
        in_array($status, ['terminee'], true)                         => 'bg-muted text-muted-foreground',
        in_array($status, ['annulee','refusee','abandonnee'], true)   => 'bg-destructive/10 text-destructive',
        default                                                        => 'bg-primary/10 text-primary',
    };
    return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ' . $cls . '">'
         . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
}

$base = rtrim(\App\Core\env('APP_URL', ''), '/');
?>

<?php if ($isImmoOnly ?? false): ?>
<!-- ══════════════════ VUE GESTIONNAIRE IMMOBILIER ══════════════════ -->
<?php
$fmt = fn($n) => number_format((float)$n, 0, ',', ' ');
?>
<div class="space-y-6">
  <div>
    <h1 class="font-display text-2xl font-bold">Tableau de bord</h1>
    <p class="text-sm text-muted-foreground">Vue d'ensemble de votre portefeuille immobilier.</p>
  </div>

  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Appartements</p>
      <p class="mt-1 font-display text-2xl font-bold"><?= (int)($nbAptOccupied ?? 0) ?> / <?= (int)($nbAptTotal ?? 0) ?></p>
      <p class="text-xs text-muted-foreground">occupés / total</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Locataires actifs</p>
      <p class="mt-1 font-display text-2xl font-bold"><?= (int)($nbTenants ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Encaissé ce mois</p>
      <p class="mt-1 font-display text-2xl font-bold text-success"><?= $fmt($encaisse ?? 0) ?> GNF</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Paiements à valider</p>
      <p class="mt-1 font-display text-2xl font-bold <?= ($nbAttente ?? 0) > 0 ? 'text-amber-600' : '' ?>"><?= (int)($nbAttente ?? 0) ?></p>
    </div>
  </div>

  <div class="flex gap-3">
    <a href="<?= $base ?>/app/immobilier"
       class="inline-flex items-center gap-2 rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90">
      Ouvrir Immobilier
    </a>
    <a href="<?= $base ?>/app/paiements-loyers"
       class="inline-flex items-center gap-2 rounded-lg border border-border bg-card px-5 py-2.5 text-sm font-semibold hover:bg-muted">
      Gestion des paiements
    </a>
  </div>
</div>

<?php elseif ($isStaff ?? false): ?>
<!-- ══════════════════ VUE STAFF ══════════════════ -->

<div class="space-y-6">
  <div>
    <h1 class="font-display text-2xl font-bold">Tableau de bord</h1>
    <p class="text-sm text-muted-foreground">Vue d'ensemble en temps réel des données enregistrées.</p>
  </div>

  <!-- KPIs -->
  <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    <?php
      $kpis = [
          ['Demandes à traiter',      $pending ?? 0,           ''],
          ['Inscriptions confirmées', $confirmed ?? 0,         ''],
      ];
      if ($isFinance ?? false) {
          $kpis[] = ['Entrées validées',  Format::money($income ?? 0),  'text-success'];
          $kpis[] = ['Sorties validées',  Format::money($outcome ?? 0), 'text-destructive'];
      } else {
          $kpis[] = ['Sessions à venir',  count($sessions ?? []), ''];
      }
    ?>
    <?php foreach ($kpis as [$label, $value, $cls]): ?>
      <div class="rounded-xl border border-border bg-card p-5">
        <p class="text-sm text-muted-foreground"><?= e($label) ?></p>
        <p class="mt-1 font-display text-2xl font-bold <?= $cls ?>"><?= e((string) $value) ?></p>
      </div>
    <?php endforeach ?>
    <?php if ($isFinance ?? false): ?>
      <div class="rounded-xl border border-border bg-card p-5">
        <p class="text-sm text-muted-foreground">Résultat net</p>
        <p class="mt-1 font-display text-2xl font-bold"><?= Format::money(($income ?? 0) - ($outcome ?? 0)) ?></p>
      </div>
      <div class="rounded-xl border border-border bg-card p-5">
        <p class="text-sm text-muted-foreground">Créances apprenants</p>
        <p class="mt-1 font-display text-2xl font-bold text-destructive"><?= Format::money($receivable ?? 0) ?></p>
      </div>
    <?php endif ?>
  </div>

  <div class="grid gap-6 lg:grid-cols-2">

    <!-- Dernières inscriptions -->
    <div class="rounded-xl border border-border bg-card">
      <div class="flex items-center justify-between border-b border-border px-5 py-4">
        <h2 class="text-sm font-semibold">Dernières demandes d'inscription</h2>
        <a href="<?= $base ?>/app/inscriptions" class="text-xs text-primary hover:underline">Voir tout</a>
      </div>
      <div class="divide-y divide-border">
        <?php if (empty($enrollments)): ?>
          <p class="px-5 py-4 text-sm text-muted-foreground">Aucune inscription enregistrée.</p>
        <?php else: ?>
          <?php foreach (array_slice($enrollments, 0, 8) as $e): ?>
            <a href="<?= $base ?>/app/inscriptions/<?= e($e['id']) ?>"
               class="flex items-center justify-between gap-3 px-5 py-3 hover:bg-muted/50 transition-colors">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium"><?= e($e['program_title'] ?? 'Formation') ?></p>
                <p class="truncate text-xs text-muted-foreground">
                  <?= e($e['enrollment_number'] ?? '') ?>
                  <?php if ($e['first_name'] ?? ''): ?>
                    · <?= e(trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? ''))) ?>
                  <?php endif ?>
                </p>
              </div>
              <?= statusBadge($e['status'], $statusLabels) ?>
            </a>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>

    <!-- Prochaines sessions -->
    <div class="rounded-xl border border-border bg-card">
      <div class="border-b border-border px-5 py-4">
        <h2 class="text-sm font-semibold">Prochaines sessions</h2>
      </div>
      <div class="divide-y divide-border">
        <?php if (empty($sessions)): ?>
          <p class="px-5 py-4 text-sm text-muted-foreground">Aucune session à venir.</p>
        <?php else: ?>
          <?php foreach ($sessions as $s): ?>
            <div class="flex items-center justify-between gap-3 px-5 py-3">
              <div class="min-w-0">
                <p class="truncate text-sm font-medium"><?= e($s['program_title'] ?? $s['id']) ?></p>
                <p class="truncate text-xs text-muted-foreground"><?= Format::date($s['start_date']) ?></p>
              </div>
              <span class="shrink-0 text-xs text-muted-foreground">
                <?= (int) $s['seats_taken'] ?>/<?= (int) $s['capacity'] ?> places
              </span>
            </div>
          <?php endforeach ?>
        <?php endif ?>
      </div>
    </div>

  </div>
</div>

<?php else: ?>
<!-- ══════════════════ VUE CLIENT ══════════════════ -->

<div class="space-y-6">
  <div>
    <h1 class="font-display text-2xl font-bold">Mon espace</h1>
    <p class="text-sm text-muted-foreground">Suivez vos inscriptions, vos paiements et vos sessions.</p>
  </div>

  <!-- KPIs -->
  <div class="grid gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Mes inscriptions</p>
      <p class="mt-1 font-display text-2xl font-bold"><?= count($enrollments ?? []) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Montant total</p>
      <p class="mt-1 font-display text-2xl font-bold"><?= Format::money($totalDue ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-sm text-muted-foreground">Solde restant</p>
      <p class="mt-1 font-display text-2xl font-bold text-destructive"><?= Format::money(($totalDue ?? 0) - ($totalPaid ?? 0)) ?></p>
    </div>
  </div>

  <!-- Liste inscriptions -->
  <div class="rounded-xl border border-border bg-card">
    <div class="flex items-center justify-between border-b border-border px-5 py-4">
      <h2 class="text-sm font-semibold">Mes demandes d'inscription</h2>
      <a href="<?= $base ?>/app/inscriptions" class="text-xs text-primary hover:underline">Voir tout</a>
    </div>
    <div class="divide-y divide-border">
      <?php if (empty($enrollments)): ?>
        <div class="px-5 py-8 text-center">
          <p class="text-sm text-muted-foreground">Vous n'avez encore aucune inscription.</p>
          <a href="<?= $base ?>/formations"
             class="mt-4 inline-block rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
            Voir le catalogue
          </a>
        </div>
      <?php else: ?>
        <?php foreach ($enrollments as $e): ?>
          <a href="<?= $base ?>/app/inscriptions/<?= e($e['id']) ?>"
             class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 hover:bg-muted/50 transition-colors">
            <div>
              <p class="text-sm font-medium"><?= e($e['program_title'] ?? 'Formation') ?></p>
              <p class="text-xs text-muted-foreground">
                <?= e($e['enrollment_number'] ?? '') ?>
                <?php if ($e['session_start'] ?? ''): ?>
                  · début <?= Format::date($e['session_start']) ?>
                <?php endif ?>
              </p>
            </div>
            <div class="flex items-center gap-3">
              <span class="text-sm text-muted-foreground">
                <?= Format::money((float) $e['paid_amount']) ?> / <?= Format::money((float) $e['agreed_amount']) ?>
              </span>
              <?= statusBadge($e['status'], $statusLabels) ?>
            </div>
          </a>
        <?php endforeach ?>
      <?php endif ?>
    </div>
  </div>
</div>

<?php endif ?>
