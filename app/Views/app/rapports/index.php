<?php
use function App\Core\e;
use App\Helpers\Format;

$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$statusLabels = [
    'brouillon'            => 'Brouillon',
    'nouvelle_demande'     => 'Nouvelle demande',
    'a_contacter'          => 'À contacter',
    'contacte'             => 'Contacté',
    'en_attente_documents' => 'En attente documents',
    'en_attente_paiement'  => 'En attente paiement',
    'paiement_partiel'     => 'Paiement partiel',
    'confirmee'            => 'Confirmée',
    'en_cours'             => 'En cours',
    'terminee'             => 'Terminée',
    'abandonnee'           => 'Abandonnée',
    'annulee'              => 'Annulée',
    'refusee'              => 'Refusée',
    'remboursee'           => 'Remboursée',
];

$kpis = [
    ['label' => 'Inscriptions actives',  'value' => number_format($enrollCount ?? 0)],
    ['label' => 'Encaissements validés', 'value' => Format::money($income ?? 0)],
    ['label' => 'Dépenses validées',     'value' => Format::money($expense ?? 0)],
    ['label' => 'Résultat net',          'value' => Format::money(($income ?? 0) - ($expense ?? 0))],
    ['label' => 'Reste à encaisser',     'value' => Format::money($outstanding ?? 0)],
];
?>

<div class="space-y-6">

  <header class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Rapports & exports</h1>
      <p class="text-sm text-muted-foreground">Synthèse consolidée et export CSV de vos données opérationnelles.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <a href="<?= $base ?>/app/rapports/export?type=inscriptions"
         class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Inscriptions
      </a>
      <a href="<?= $base ?>/app/rapports/export?type=transactions"
         class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Transactions
      </a>
      <a href="<?= $base ?>/app/rapports/export?type=clients"
         class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
          <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
        </svg>
        Clients
      </a>
    </div>
  </header>

  <!-- KPIs -->
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
    <?php foreach ($kpis as $kpi): ?>
      <div class="rounded-xl border border-border bg-card p-4">
        <p class="text-xs text-muted-foreground"><?= e($kpi['label']) ?></p>
        <p class="mt-1 font-display text-lg font-bold"><?= e($kpi['value']) ?></p>
      </div>
    <?php endforeach ?>
  </div>

  <!-- Inscriptions par statut -->
  <div class="rounded-xl border border-border bg-card">
    <div class="border-b border-border px-5 py-3">
      <h2 class="font-display text-base font-semibold">Inscriptions par statut</h2>
    </div>
    <div class="p-4">
      <?php if (empty($byStatus)): ?>
        <p class="text-sm text-muted-foreground">Aucune inscription enregistrée.</p>
      <?php else: ?>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead>
              <tr class="border-b border-border text-left text-xs font-medium uppercase tracking-wide text-muted-foreground">
                <th class="pb-2 pr-4">Statut</th>
                <th class="pb-2 px-4 text-right">Nombre</th>
                <th class="pb-2 pl-4 text-right">Montant convenu</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <?php foreach ($byStatus as $status => $stat): ?>
                <tr>
                  <td class="py-2 pr-4"><?= e($statusLabels[$status] ?? $status) ?></td>
                  <td class="py-2 px-4 text-right font-medium"><?= number_format($stat['count']) ?></td>
                  <td class="py-2 pl-4 text-right"><?= Format::money($stat['amount']) ?></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>
  </div>

</div>
