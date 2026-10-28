<?php
use function App\Core\e;
use App\Core\Auth;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$dirLabels = ['entree' => 'Entrée', 'sortie' => 'Sortie'];
$statusLabels = [
    'brouillon' => 'Brouillon',
    'soumise'   => 'Soumise',
    'validee'   => 'Validée',
    'annulee'   => 'Annulée',
];
$payLabels = [
    'especes'      => 'Espèces',
    'virement'     => 'Virement',
    'cheque'       => 'Chèque',
    'mobile_money' => 'Mobile Money',
    'carte'        => 'Carte',
];
$accountTypeLabels = ['caisse' => 'Caisse', 'banque' => 'Banque', 'mobile' => 'Mobile Money'];

$stats = $stats ?? ['total_entrees' => 0, 'total_sorties' => 0];
$solde = $stats['total_entrees'] - $stats['total_sorties'];
$f = $filters ?? [];
$pag = $result ?? ['data' => [], 'total' => 0, 'page' => 1, 'last_page' => 1];
?>

<div class="space-y-6">

  <!-- En-tête -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Finances</h1>
      <p class="text-sm text-muted-foreground">Journal des opérations financières de CaNDI</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <button onclick="document.getElementById('cashierDialog').showModal()"
              class="inline-flex items-center gap-2 rounded-lg border border-primary/40 bg-primary/10 px-4 py-2 text-sm font-semibold text-primary hover:bg-primary/20">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/></svg>
        Encaisser une inscription
      </button>
      <a href="<?= $base ?>/app/finances/creer"
         class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouvelle opération
      </a>
      <button onclick="document.getElementById('transferDialog').showModal()"
              class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21 3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
        Virement entre comptes
      </button>
      <a href="<?= $base ?>/app/finances/exporter?<?= http_build_query(array_filter($f)) ?>"
         class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-muted">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
        Exporter journal
      </a>
    </div>
  </div>

  <!-- Dialog : Encaisser une inscription -->
  <dialog id="cashierDialog" class="rounded-2xl border border-border bg-card shadow-2xl w-full max-w-lg p-0 backdrop:bg-black/40" style="margin:auto">
    <form method="POST" action="<?= $base ?>/app/finances/encaisser-inscription" class="space-y-4 p-6">
      <?= \App\Core\Csrf::field() ?>
      <div class="flex items-center justify-between">
        <h2 class="font-semibold text-base">Encaisser un paiement d'inscription</h2>
        <button type="button" onclick="document.getElementById('cashierDialog').close()" class="text-muted-foreground hover:text-foreground">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>

      <!-- Recherche dossier -->
      <div class="space-y-1.5">
        <label class="block text-sm font-medium" for="cashier_search">Dossier d'inscription</label>
        <input type="text" id="cashier_search" placeholder="Rechercher par nom, numéro…"
               class="app-input w-full" oninput="filterCashierEnrollments(this.value)">
        <select name="enrollment_id" id="cashier_enrollment" required class="app-input w-full mt-1">
          <option value="">— Sélectionner un dossier —</option>
          <?php foreach ($openEnrollments ?? [] as $oe):
            $oLabel = trim(($oe['first_name'] ?? '') . ' ' . ($oe['last_name'] ?? ''));
            $oRest  = max(0, (float)$oe['agreed_amount'] - (float)$oe['paid_amount'] - (float)$oe['discount']);
          ?>
            <option value="<?= e($oe['id']) ?>"
                    data-search="<?= e(strtolower($oLabel . ' ' . ($oe['enrollment_number'] ?? '') . ' ' . ($oe['program_title'] ?? '') . ' ' . ($oe['phone'] ?? ''))) ?>"
                    data-rest="<?= (int) $oRest ?>">
              <?= e(($oe['enrollment_number'] ?? '—') . ' · ' . ($oe['program_title'] ?? '') . ($oLabel ? ' · ' . $oLabel : '') . ' (reste ' . number_format($oRest, 0, ',', ' ') . ' GNF)') ?>
            </option>
          <?php endforeach ?>
        </select>
      </div>

      <div class="grid gap-4 sm:grid-cols-2">
        <!-- Montant -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_amount">Montant encaissé (GNF) *</label>
          <input type="number" name="amount" id="cashier_amount" required min="1" step="500"
                 class="app-input w-full">
        </div>
        <!-- Remise -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_discount">Remise (GNF)</label>
          <input type="number" name="discount" id="cashier_discount" min="0" step="500" value="0"
                 class="app-input w-full">
        </div>
        <!-- Compte -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_account">Compte / Caisse *</label>
          <select name="account_id" id="cashier_account" required class="app-input w-full">
            <option value="">— Sélectionner —</option>
            <?php foreach ($accounts ?? [] as $acc): ?>
              <option value="<?= e($acc['id']) ?>"><?= e($acc['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <!-- Méthode -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_method">Moyen de paiement</label>
          <select name="payment_method" id="cashier_method" class="app-input w-full">
            <?php foreach (['especes' => 'Espèces', 'mobile_money' => 'Mobile Money', 'virement' => 'Virement', 'cheque' => 'Chèque', 'carte' => 'Carte'] as $v => $lbl): ?>
              <option value="<?= $v ?>"><?= $lbl ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <!-- Date -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_date">Date paiement</label>
          <input type="date" name="tx_date" id="cashier_date" class="app-input w-full" value="<?= date('Y-m-d') ?>">
        </div>
        <!-- Référence -->
        <div class="space-y-1.5">
          <label class="block text-sm font-medium" for="cashier_ref">Référence</label>
          <input type="text" name="reference" id="cashier_ref" class="app-input w-full" placeholder="N° reçu, transaction…">
        </div>
      </div>

      <div class="flex justify-end gap-3 pt-2">
        <button type="button" onclick="document.getElementById('cashierDialog').close()"
                class="rounded-lg border border-border px-4 py-2 text-sm font-semibold hover:bg-muted">
          Annuler
        </button>
        <button type="submit" class="app-btn-primary">Enregistrer l'encaissement</button>
      </div>
    </form>
  </dialog>

  <script>
  function filterCashierEnrollments(q) {
    q = q.toLowerCase().trim();
    const sel = document.getElementById('cashier_enrollment');
    for (const opt of sel.options) {
      if (!opt.value) continue;
      opt.hidden = q && !opt.dataset.search.includes(q);
    }
  }
  </script>

  <!-- Flash -->
  <?php if (!empty($success)): ?>
    <div class="rounded-lg border border-success/20 bg-success/10 px-4 py-3 text-sm text-success"><?= e($success) ?></div>
  <?php endif ?>
  <?php if (!empty($error)): ?>
    <div class="rounded-lg border border-destructive/20 bg-destructive/10 px-4 py-3 text-sm text-destructive"><?= e($error) ?></div>
  <?php endif ?>

  <!-- Statistiques globales -->
  <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Entrées (validées)</p>
      <p class="mt-1 font-display text-2xl font-bold text-success"><?= Format::money($stats['total_entrees']) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Sorties (validées)</p>
      <p class="mt-1 font-display text-2xl font-bold text-destructive"><?= Format::money($stats['total_sorties']) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-5">
      <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Solde net</p>
      <p class="mt-1 font-display text-2xl font-bold <?= $solde >= 0 ? 'text-foreground' : 'text-destructive' ?>"><?= Format::money($solde) ?></p>
    </div>
  </div>

  <!-- Soldes par compte -->
  <?php if (!empty($accounts)): ?>
    <div>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Soldes par compte</h2>
      <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($accounts as $acc): ?>
          <div class="flex items-center justify-between rounded-xl border border-border bg-card px-5 py-4">
            <div>
              <p class="font-medium"><?= e($acc['name']) ?></p>
              <p class="text-xs text-muted-foreground"><?= e($accountTypeLabels[$acc['type']] ?? $acc['type']) ?> · <?= e($acc['currency']) ?></p>
            </div>
            <p class="font-display text-lg font-bold <?= (float)$acc['current_balance'] >= 0 ? 'text-foreground' : 'text-destructive' ?>">
              <?= Format::money($acc['current_balance']) ?>
            </p>
          </div>
        <?php endforeach ?>
      </div>
    </div>
  <?php else: ?>
    <!-- Aucun compte — formulaire de création rapide -->
    <div class="rounded-xl border border-dashed border-border bg-card p-6 text-center">
      <p class="font-medium">Aucun compte financier configuré</p>
      <p class="mt-1 text-sm text-muted-foreground">Créez au moins un compte (caisse, banque…) pour commencer à enregistrer des opérations.</p>
      <button onclick="document.getElementById('new-account-form').classList.toggle('hidden')"
              class="mt-4 inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted">
        + Créer un compte
      </button>
      <form id="new-account-form" method="POST" action="<?= $base ?>/app/finances/compte"
            class="hidden mt-4 mx-auto max-w-sm text-left space-y-3">
        <?= \App\Core\Csrf::field() ?>
        <div>
          <label class="block text-sm font-medium mb-1">Nom du compte</label>
          <input type="text" name="name" class="app-input w-full" placeholder="Ex: Caisse principale" required>
        </div>
        <div class="grid grid-cols-2 gap-3">
          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select name="type" class="app-input w-full">
              <option value="caisse">Caisse</option>
              <option value="banque">Banque</option>
              <option value="mobile">Mobile Money</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium mb-1">Solde initial (GNF)</label>
            <input type="number" name="opening_balance" value="0" min="0" class="app-input w-full">
          </div>
        </div>
        <button type="submit" class="app-btn-primary w-full">Créer le compte</button>
      </form>
    </div>
  <?php endif ?>

  <!-- Soldes par client -->
  <?php if (!empty($clientBalances)): ?>
  <div>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Soldes par client</h2>
      <input type="search" id="clientSearch" placeholder="Rechercher un client…"
             oninput="filterClients(this.value)"
             class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary sm:w-56">
    </div>
    <div id="clientList" class="rounded-xl border border-border bg-card divide-y divide-border">
      <?php foreach ($clientBalances as $cb): ?>
        <div class="client-row flex flex-wrap items-center justify-between gap-3 px-5 py-3"
             data-name="<?= e(strtolower($cb['learner_name'])) ?>">
          <div class="min-w-0">
            <p class="truncate text-sm font-medium"><?= e($cb['learner_name'] ?: 'Apprenant') ?></p>
            <p class="text-xs text-muted-foreground">
              <?= (int)$cb['enrollment_count'] ?> inscription<?= $cb['enrollment_count'] > 1 ? 's' : '' ?>
              · dû <?= \App\Helpers\Format::money($cb['total_due']) ?>
            </p>
          </div>
          <div class="flex items-center gap-6 text-right">
            <div>
              <p class="text-xs text-muted-foreground">Payé</p>
              <p class="font-display text-sm font-bold text-success"><?= \App\Helpers\Format::money($cb['total_paid']) ?></p>
            </div>
            <div>
              <p class="text-xs text-muted-foreground">Reste</p>
              <p class="font-display text-sm font-bold text-destructive"><?= \App\Helpers\Format::money($cb['total_remaining']) ?></p>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- Transferts entre comptes -->
  <div>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-muted-foreground">Transferts de caisse</h2>
    <div class="rounded-xl border border-border bg-card">
      <?php if (empty($transfers)): ?>
        <p class="px-5 py-6 text-sm text-muted-foreground">Aucun transfert enregistré.</p>
      <?php else: ?>
        <div class="divide-y divide-border">
          <?php foreach ($transfers as $t): ?>
            <?php
              $ts = $t['status'];
              [$tLabel, $tCls] = match($ts) {
                  'valide' => ['Validé',  'bg-success/15 text-success'],
                  'refuse' => ['Refusé',  'bg-destructive/10 text-destructive'],
                  default  => ['En attente', 'bg-primary/10 text-primary'],
              };
            ?>
            <div class="flex flex-wrap items-start justify-between gap-3 px-5 py-3">
              <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                  <span class="font-mono text-xs text-muted-foreground"><?= e($t['reference'] ?? '—') ?></span>
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $tCls ?>"><?= $tLabel ?></span>
                </div>
                <p class="mt-0.5 text-sm font-medium">
                  <?= \App\Helpers\Format::money((float)$t['amount']) ?>
                  <span class="text-muted-foreground font-normal">de</span>
                  <?= e($t['from_account_name'] ?? '?') ?>
                  <span class="text-muted-foreground font-normal">vers</span>
                  <?= e($t['to_account_name'] ?? '?') ?>
                </p>
                <p class="text-xs text-muted-foreground">
                  Demandé <?= \App\Helpers\Format::date($t['created_at']) ?>
                  <?php if (!empty($t['requested_by_name']) && trim($t['requested_by_name'])): ?>
                    par <?= e(trim($t['requested_by_name'])) ?>
                  <?php endif ?>
                  <?php if ($t['note']): ?> · <em><?= e($t['note']) ?></em><?php endif ?>
                  <?php if ($ts !== 'en_attente' && $t['validated_at']): ?>
                    · <?= $ts === 'valide' ? 'Validé' : 'Refusé' ?> le <?= \App\Helpers\Format::date($t['validated_at']) ?>
                  <?php endif ?>
                </p>
              </div>
              <?php if (($canValidateTransfer ?? false) && $ts === 'en_attente'): ?>
                <div class="flex items-center gap-2 flex-shrink-0">
                  <form method="POST" action="<?= $base ?>/app/finances/transferts/<?= e($t['id']) ?>/valider"
                        onsubmit="return confirm('Valider ce transfert de <?= \App\Helpers\Format::money((float)$t['amount']) ?> ? Cela générera les écritures comptables.')">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit"
                            class="inline-flex items-center gap-1 rounded-md border border-success/30 bg-success/10 px-3 py-1.5 text-xs font-medium text-success hover:bg-success/20">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                      Valider
                    </button>
                  </form>
                  <form method="POST" action="<?= $base ?>/app/finances/transferts/<?= e($t['id']) ?>/refuser"
                        onsubmit="return confirm('Refuser ce transfert ?')">
                    <?= \App\Core\Csrf::field() ?>
                    <button type="submit"
                            class="inline-flex items-center gap-1 rounded-md border border-border px-3 py-1.5 text-xs text-destructive hover:bg-destructive/10">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                      Refuser
                    </button>
                  </form>
                </div>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </div>

  <!-- Filtres + Journal -->
  <div>
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
      <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Journal des opérations</h2>
      <span class="text-xs text-muted-foreground"><?= $pag['total'] ?> opération(s)</span>
    </div>

    <form method="GET" action="<?= $base ?>/app/finances" class="mb-4 flex flex-wrap gap-2">
      <input type="text" name="q" value="<?= e($f['search'] ?? '') ?>"
             placeholder="Rechercher…" class="app-input max-w-xs">
      <select name="direction" class="app-input w-auto">
        <option value="">Toutes directions</option>
        <option value="entree" <?= ($f['direction'] ?? '') === 'entree' ? 'selected' : '' ?>>Entrées</option>
        <option value="sortie" <?= ($f['direction'] ?? '') === 'sortie' ? 'selected' : '' ?>>Sorties</option>
      </select>
      <select name="account" class="app-input w-auto">
        <option value="">Tous les comptes</option>
        <?php foreach ($accounts ?? [] as $a): ?>
          <option value="<?= e($a['id']) ?>" <?= ($f['account_id'] ?? '') === $a['id'] ? 'selected' : '' ?>><?= e($a['name']) ?></option>
        <?php endforeach ?>
      </select>
      <select name="categorie" class="app-input w-auto">
        <option value="">Toutes catégories</option>
        <?php foreach ($categories ?? [] as $c): ?>
          <option value="<?= e($c['id']) ?>" <?= ($f['category_id'] ?? '') === $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach ?>
      </select>
      <select name="status" class="app-input w-auto">
        <option value="">Tous statuts</option>
        <?php foreach ($statusLabels as $v => $l): ?>
          <option value="<?= e($v) ?>" <?= ($f['status'] ?? '') === $v ? 'selected' : '' ?>><?= e($l) ?></option>
        <?php endforeach ?>
      </select>
      <input type="month" name="mois" value="<?= e($f['month'] ?? '') ?>" class="app-input w-auto">
      <button type="submit" class="app-btn-primary">Filtrer</button>
      <?php if (array_filter($f)): ?>
        <a href="<?= $base ?>/app/finances" class="rounded-lg border border-border px-3 py-1.5 text-sm hover:bg-muted">Réinitialiser</a>
      <?php endif ?>
    </form>

    <div class="overflow-x-auto rounded-xl border border-border bg-card">
      <table class="w-full text-sm">
        <thead class="border-b border-border bg-muted/50">
          <tr>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Libellé / Tiers</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Compte</th>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Catégorie</th>
            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Sens</th>
            <th class="px-4 py-3 text-right font-medium text-muted-foreground">Montant</th>
            <th class="px-4 py-3 text-center font-medium text-muted-foreground">Statut</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <?php if (empty($pag['data'])): ?>
            <tr>
              <td colspan="8" class="px-4 py-10 text-center text-muted-foreground">
                Aucune opération enregistrée.
              </td>
            </tr>
          <?php else: ?>
            <?php foreach ($pag['data'] as $tx): ?>
              <tr class="hover:bg-muted/30 transition-colors">
                <td class="px-4 py-3 text-xs text-muted-foreground whitespace-nowrap"><?= e($tx['tx_date']) ?></td>
                <td class="px-4 py-3">
                  <p class="font-medium"><?= e($tx['description'] ?: '—') ?></p>
                  <?php if ($tx['third_party']): ?>
                    <p class="text-xs text-muted-foreground"><?= e($tx['third_party']) ?></p>
                  <?php endif ?>
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground"><?= e($tx['account_name'] ?? '—') ?></td>
                <td class="px-4 py-3 text-xs text-muted-foreground"><?= e($tx['category_name'] ?? '—') ?></td>
                <td class="px-4 py-3 text-center">
                  <?php if ($tx['direction'] === 'entree'): ?>
                    <span class="inline-flex items-center gap-1 rounded-full bg-success/15 px-2 py-0.5 text-xs font-medium text-success">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5m-7 7 7-7 7 7"/></svg>
                      Entrée
                    </span>
                  <?php else: ?>
                    <span class="inline-flex items-center gap-1 rounded-full bg-destructive/10 px-2 py-0.5 text-xs font-medium text-destructive">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14m7-7-7 7-7-7"/></svg>
                      Sortie
                    </span>
                  <?php endif ?>
                </td>
                <td class="px-4 py-3 text-right font-mono font-medium <?= $tx['direction'] === 'entree' ? 'text-success' : 'text-destructive' ?>">
                  <?= $tx['direction'] === 'entree' ? '+' : '-' ?><?= Format::money((float)$tx['amount']) ?>
                </td>
                <td class="px-4 py-3 text-center">
                  <?php
                  $sc = match($tx['status']) {
                      'validee'   => 'bg-success/15 text-success',
                      'annulee'   => 'bg-destructive/10 text-destructive',
                      'brouillon' => 'bg-muted text-muted-foreground',
                      default     => 'bg-primary/10 text-primary',
                  };
                  ?>
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $sc ?>">
                    <?= e($statusLabels[$tx['status']] ?? $tx['status']) ?>
                  </span>
                </td>
                <td class="px-4 py-3">
                  <div class="flex items-center justify-end gap-1">
                    <?php if ($tx['status'] === 'soumise'): ?>
                      <form method="POST" action="<?= $base ?>/app/finances/<?= e($tx['id']) ?>/valider">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="rounded-md border border-success/30 bg-success/10 px-2 py-1 text-xs font-medium text-success hover:bg-success/20" title="Valider">
                          Valider
                        </button>
                      </form>
                    <?php endif ?>
                    <?php if ($tx['status'] !== 'validee' && $tx['status'] !== 'annulee'): ?>
                      <a href="<?= $base ?>/app/finances/<?= e($tx['id']) ?>/modifier"
                         class="rounded-md border border-border p-1.5 hover:bg-muted" title="Modifier">
                        <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/></svg>
                      </a>
                      <form method="POST" action="<?= $base ?>/app/finances/<?= e($tx['id']) ?>/supprimer"
                            onsubmit="return confirm('Supprimer cette opération ?')">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="rounded-md border border-border p-1.5 text-destructive hover:bg-destructive/5" title="Supprimer">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                        </button>
                      </form>
                    <?php endif ?>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          <?php endif ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($pag['last_page'] > 1): ?>
      <div class="mt-4 flex items-center justify-between text-sm">
        <span class="text-muted-foreground"><?= $pag['total'] ?> résultats · page <?= $pag['page'] ?>/<?= $pag['last_page'] ?></span>
        <div class="flex gap-2">
          <?php
          $qs = http_build_query(array_filter(array_merge($f, ['search' => $f['search'] ?? null])));
          if ($pag['page'] > 1): ?>
            <a href="?<?= $qs ?>&page=<?= $pag['page'] - 1 ?>" class="rounded-md border border-border px-3 py-1 hover:bg-muted">← Précédent</a>
          <?php endif ?>
          <?php if ($pag['page'] < $pag['last_page']): ?>
            <a href="?<?= $qs ?>&page=<?= $pag['page'] + 1 ?>" class="rounded-md border border-border px-3 py-1 hover:bg-muted">Suivant →</a>
          <?php endif ?>
        </div>
      </div>
    <?php endif ?>
  </div>

  <!-- Section : Ajouter catégorie -->
  <details class="rounded-xl border border-border bg-card">
    <summary class="cursor-pointer px-5 py-4 text-sm font-medium hover:bg-muted/40">Gérer les catégories</summary>
    <div class="border-t border-border px-5 py-4 space-y-4">
      <?php
      $cats = $categories ?? [];
      $entrees = array_filter($cats, fn($c) => $c['direction'] === 'entree');
      $sorties = array_filter($cats, fn($c) => $c['direction'] === 'sortie');
      ?>
      <div class="grid sm:grid-cols-2 gap-4 text-sm">
        <div>
          <p class="font-medium text-success mb-1">Catégories Entrée</p>
          <?php if ($entrees): ?>
            <ul class="space-y-0.5">
              <?php foreach ($entrees as $c): ?><li class="text-muted-foreground"><?= e($c['name']) ?></li><?php endforeach ?>
            </ul>
          <?php else: ?><p class="text-muted-foreground text-xs">Aucune</p><?php endif ?>
        </div>
        <div>
          <p class="font-medium text-destructive mb-1">Catégories Sortie</p>
          <?php if ($sorties): ?>
            <ul class="space-y-0.5">
              <?php foreach ($sorties as $c): ?><li class="text-muted-foreground"><?= e($c['name']) ?></li><?php endforeach ?>
            </ul>
          <?php else: ?><p class="text-muted-foreground text-xs">Aucune</p><?php endif ?>
        </div>
      </div>
      <form method="POST" action="<?= $base ?>/app/finances/categorie" class="flex flex-wrap gap-3 items-end pt-2 border-t border-border">
        <?= \App\Core\Csrf::field() ?>
        <div>
          <label class="block text-xs font-medium mb-1">Nom</label>
          <input type="text" name="cat_name" class="app-input" placeholder="Ex: Frais bancaires" required>
        </div>
        <div>
          <label class="block text-xs font-medium mb-1">Sens</label>
          <select name="cat_direction" class="app-input">
            <option value="entree">Entrée</option>
            <option value="sortie">Sortie</option>
          </select>
        </div>
        <button type="submit" class="app-btn-primary">Ajouter</button>
      </form>
    </div>
  </details>

</div>

<!-- Dialog : Virement entre comptes -->
<dialog id="transferDialog" class="w-full max-w-md rounded-xl border border-border bg-card p-6 shadow-xl backdrop:bg-black/40" style="margin:auto">
  <div class="mb-1 flex items-center justify-between">
    <h2 class="font-semibold text-base">Transfert de caisse</h2>
    <button type="button" onclick="document.getElementById('transferDialog').close()" class="text-muted-foreground hover:text-foreground">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
  </div>
  <p class="mb-4 text-xs text-muted-foreground">
    Le montant quitte la caisse source et est crédité sur le compte destinataire une fois le transfert confirmé par le super administrateur.
  </p>
  <form method="POST" action="<?= $base ?>/app/finances/transferts" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <?php
      $caisses = array_filter($accounts ?? [], fn($a) => $a['type'] !== 'banque');
      $banques  = array_filter($accounts ?? [], fn($a) => $a['type'] === 'banque');
    ?>
    <div class="grid gap-1.5">
      <label class="text-sm font-medium" for="tr_from">Caisse de départ <span class="text-destructive">*</span></label>
      <select name="from_account_id" id="tr_from" required class="app-input w-full" onchange="trCheckSame()">
        <option value="">— Sélectionner —</option>
        <?php if ($caisses): ?>
          <optgroup label="Caisses">
            <?php foreach ($caisses as $a): ?>
              <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option>
            <?php endforeach ?>
          </optgroup>
        <?php endif ?>
        <?php if ($banques): ?>
          <optgroup label="Comptes bancaires">
            <?php foreach ($banques as $a): ?>
              <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option>
            <?php endforeach ?>
          </optgroup>
        <?php endif ?>
      </select>
    </div>
    <div class="grid gap-1.5">
      <label class="text-sm font-medium" for="tr_to">Compte destinataire <span class="text-destructive">*</span></label>
      <select name="to_account_id" id="tr_to" required class="app-input w-full" onchange="trCheckSame()">
        <option value="">— Sélectionner —</option>
        <?php if ($banques): ?>
          <optgroup label="Comptes bancaires">
            <?php foreach ($banques as $a): ?>
              <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option>
            <?php endforeach ?>
          </optgroup>
        <?php endif ?>
        <?php if ($caisses): ?>
          <optgroup label="Caisses">
            <?php foreach ($caisses as $a): ?>
              <option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option>
            <?php endforeach ?>
          </optgroup>
        <?php endif ?>
      </select>
    </div>
    <div id="tr_same_error" class="hidden rounded-md bg-destructive/10 px-3 py-2 text-xs text-destructive">
      Les deux comptes doivent être différents.
    </div>
    <div class="grid gap-1.5">
      <label class="text-sm font-medium" for="tr_amount">Montant (GNF) <span class="text-destructive">*</span></label>
      <input type="number" name="amount" id="tr_amount" min="1" step="500" required class="app-input w-full" placeholder="0">
    </div>
    <div class="grid gap-1.5">
      <label class="text-sm font-medium" for="tr_note">Note (facultatif)</label>
      <input type="text" name="note" id="tr_note" class="app-input w-full" placeholder="Ex: Versement mensuel">
    </div>
    <div class="flex gap-3 pt-2">
      <button type="button" onclick="document.getElementById('transferDialog').close()"
              class="flex-1 rounded-lg border border-border px-4 py-2 text-sm hover:bg-muted">Annuler</button>
      <button type="submit" id="tr_submit" class="flex-1 app-btn-primary">Soumettre le transfert</button>
    </div>
  </form>
</dialog>
<script>
function trCheckSame() {
  const f = document.getElementById('tr_from').value;
  const t = document.getElementById('tr_to').value;
  const err = document.getElementById('tr_same_error');
  const btn = document.getElementById('tr_submit');
  const same = f && t && f === t;
  err.classList.toggle('hidden', !same);
  btn.disabled = same;
}
</script>

<script>
function filterClients(q) {
  const rows = document.querySelectorAll('.client-row');
  const term = q.toLowerCase().trim();
  rows.forEach(r => r.style.display = (!term || r.dataset.name.includes(term)) ? '' : 'none');
}
</script>

<style>
.app-input {
  border-radius:.5rem;border:1px solid var(--border);background:var(--background);
  padding:.4375rem .75rem;font-size:.875rem;color:var(--foreground);outline:none;transition:border-color .15s;
}
.app-input:focus{border-color:var(--primary);}
.app-btn-primary {
  display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;
  background:var(--primary);color:var(--primary-foreground);padding:.4375rem 1rem;
  font-size:.875rem;font-weight:600;cursor:pointer;border:none;transition:opacity .15s;
}
.app-btn-primary:hover{opacity:.88;}
</style>
