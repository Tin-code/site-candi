<?php
use function App\Core\e;
use App\Helpers\Format;

$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$products       = $products       ?? [];
$clients        = $clients        ?? [];
$subs           = $subs           ?? [];
$payments       = $payments       ?? [];
$accounts       = $accounts       ?? [];
$canCatalog     = $canCatalog     ?? false;
$canSubs        = $canSubs        ?? false;
$canPay         = $canPay         ?? false;

// Build lookup maps
$productMap = [];
foreach ($products as $p) $productMap[$p['id']] = $p;
$clientMap = [];
foreach ($clients as $c) $clientMap[$c['id']] = $c;

function monthLabel(string $iso): string {
    $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
    $d = \DateTime::createFromFormat('Y-m-d', substr($iso, 0, 10));
    if (!$d) return $iso;
    return $months[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
}
?>

<div class="space-y-6">

  <?php if (!empty($success)): ?>
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= e($success) ?></div>
  <?php endif ?>
  <?php if (!empty($error)): ?>
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= e($error) ?></div>
  <?php endif ?>

  <!-- Header -->
  <div class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Progiciels CRM</h1>
      <p class="text-sm text-muted-foreground">Catalogue, abonnements clients, paiements mensuels, dettes et avances.</p>
    </div>
  </div>

  <!-- KPIs -->
  <div class="grid gap-3 sm:grid-cols-3">
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs uppercase tracking-wide text-muted-foreground">Clients actifs</p>
      <p class="font-display text-2xl font-bold"><?= (int)($activeClients ?? 0) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs uppercase tracking-wide text-muted-foreground">Total encaissé</p>
      <p class="font-display text-2xl font-bold"><?= e(Format::money($totalCollected ?? 0)) ?></p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs uppercase tracking-wide text-muted-foreground">Dettes totales</p>
      <p class="font-display text-2xl font-bold <?= ($totalDebt ?? 0) > 0 ? 'text-destructive' : '' ?>"><?= e(Format::money($totalDebt ?? 0)) ?></p>
    </div>
  </div>

  <!-- Tabs -->
  <div x-data="{ tab: 'subs' }">

    <div class="flex gap-1 border-b border-border overflow-x-auto" id="crm-tabs">
      <?php foreach ([
        'subs'     => 'Abonnements',
        'payments' => 'Paiements',
        'catalog'  => 'Catalogue',
        'clients'  => 'Clients',
      ] as $key => $label): ?>
        <button onclick="switchTab('<?= $key ?>')" id="tab-<?= $key ?>"
                class="crm-tab shrink-0 px-4 py-2.5 text-sm font-medium border-b-2 -mb-px transition-colors">
          <?= $label ?>
        </button>
      <?php endforeach ?>
    </div>

    <!-- ── ABONNEMENTS ── -->
    <div id="panel-subs" class="crm-panel space-y-3 pt-4">
      <?php if ($canSubs): ?>
        <div class="flex justify-end">
          <button onclick="document.getElementById('dlg-new-sub').showModal()"
                  class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            + Nouvel abonnement
          </button>
        </div>
      <?php endif ?>

      <?php if (empty($subs)): ?>
        <p class="text-sm text-muted-foreground py-8 text-center">Aucun abonnement.</p>
      <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
          <table class="w-full text-sm">
            <thead class="border-b border-border bg-muted/40">
              <tr>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Client</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Progiciel</th>
                <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Mensualité</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Début</th>
                <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Payé</th>
                <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Dette</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Statut</th>
                <th class="px-4 py-2.5 font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <?php foreach ($subs as $sub): ?>
                <tr class="hover:bg-muted/20 transition-colors">
                  <td class="px-4 py-2.5">
                    <p class="font-medium"><?= e($sub['client_name'] ?? '—') ?></p>
                    <?php if ($sub['company_name']): ?><p class="text-xs text-muted-foreground"><?= e($sub['company_name']) ?></p><?php endif ?>
                  </td>
                  <td class="px-4 py-2.5"><?= e($sub['product_name'] ?? '—') ?></td>
                  <td class="px-4 py-2.5 text-right"><?= e(Format::money($sub['monthly_price'])) ?></td>
                  <td class="px-4 py-2.5 text-xs"><?= monthLabel($sub['start_month'] ?? '') ?></td>
                  <td class="px-4 py-2.5 text-right"><?= e(Format::money($sub['paid_amount'])) ?></td>
                  <td class="px-4 py-2.5 text-right <?= $sub['debt'] > 0 ? 'font-medium text-destructive' : 'text-muted-foreground' ?>">
                    <?= e(Format::money($sub['debt'])) ?>
                    <?php if ($sub['debt'] > 0): ?><br><span class="text-xs"><?= $sub['due_count'] ?> mois</span><?php endif ?>
                  </td>
                  <td class="px-4 py-2.5">
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $sub['status'] === 'actif' ? 'bg-green-100 text-green-700' : 'bg-muted text-muted-foreground' ?>">
                      <?= $sub['status'] === 'actif' ? 'Actif' : 'Annulé' ?>
                    </span>
                    <?php if ($sub['advance_count'] > 0): ?>
                      <span class="ml-1 inline-flex items-center rounded-full bg-blue-100 px-2 py-0.5 text-xs text-blue-700">
                        +<?= $sub['advance_count'] ?> mois avance
                      </span>
                    <?php endif ?>
                  </td>
                  <td class="px-4 py-2.5">
                    <div class="flex items-center gap-1">
                      <?php if ($canPay && $sub['status'] === 'actif'): ?>
                        <button onclick="openPayDialog(<?= htmlspecialchars(json_encode($sub), ENT_QUOTES) ?>)"
                                class="rounded border border-border px-2 py-0.5 text-xs hover:bg-muted">Paiement</button>
                      <?php endif ?>
                      <?php if ($canSubs): ?>
                        <?php if ($sub['status'] === 'actif'): ?>
                          <form method="POST" action="<?= $base ?>/app/crm/abonnements/<?= e($sub['id']) ?>/statut" onsubmit="return confirm('Annuler cet abonnement ?')">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="status" value="annule">
                            <button type="submit" class="rounded border border-border px-2 py-0.5 text-xs hover:bg-muted">Annuler</button>
                          </form>
                        <?php else: ?>
                          <form method="POST" action="<?= $base ?>/app/crm/abonnements/<?= e($sub['id']) ?>/statut">
                            <?= \App\Core\Csrf::field() ?>
                            <input type="hidden" name="status" value="actif">
                            <button type="submit" class="rounded border border-border px-2 py-0.5 text-xs hover:bg-muted">Réactiver</button>
                          </form>
                        <?php endif ?>
                        <form method="POST" action="<?= $base ?>/app/crm/abonnements/<?= e($sub['id']) ?>/supprimer" onsubmit="return confirm('Supprimer définitivement ? Impossible si des paiements existent.')">
                          <?= \App\Core\Csrf::field() ?>
                          <button type="submit" class="rounded border border-border px-2 py-0.5 text-xs text-destructive hover:bg-red-50">Suppr.</button>
                        </form>
                      <?php endif ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>

        <!-- Récapitulatif par client -->
        <?php
        $byClient = [];
        foreach ($subs as $s) {
            $cid = $s['client_id'];
            if (!isset($byClient[$cid])) {
                $byClient[$cid] = ['name' => $s['client_name'] ?? '—', 'collected' => 0, 'debt' => 0, 'advance' => 0];
            }
            $byClient[$cid]['debt']     += $s['debt'];
            $byClient[$cid]['advance']  += $s['advance_count'] * (float)$s['monthly_price'];
            $byClient[$cid]['collected'] += $s['paid_amount'];
        }
        usort($byClient, fn($a, $b) => $b['debt'] <=> $a['debt']);
        ?>
        <?php if (!empty($byClient)): ?>
          <div class="rounded-xl border border-border bg-card">
            <div class="border-b border-border px-4 py-3 font-display text-sm font-semibold">Récapitulatif par client</div>
            <div class="overflow-x-auto">
              <table class="w-full text-sm">
                <thead class="border-b border-border bg-muted/40">
                  <tr>
                    <th class="px-4 py-2 text-left font-medium text-muted-foreground">Client</th>
                    <th class="px-4 py-2 text-right font-medium text-muted-foreground">Encaissé</th>
                    <th class="px-4 py-2 text-right font-medium text-muted-foreground">Dette</th>
                    <th class="px-4 py-2 text-right font-medium text-muted-foreground">Avance</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-border">
                  <?php foreach ($byClient as $c): ?>
                    <tr class="hover:bg-muted/20">
                      <td class="px-4 py-2 font-medium"><?= e($c['name']) ?></td>
                      <td class="px-4 py-2 text-right"><?= e(Format::money($c['collected'])) ?></td>
                      <td class="px-4 py-2 text-right <?= $c['debt'] > 0 ? 'text-destructive font-medium' : 'text-muted-foreground' ?>"><?= e(Format::money($c['debt'])) ?></td>
                      <td class="px-4 py-2 text-right <?= $c['advance'] > 0 ? 'text-blue-600' : 'text-muted-foreground' ?>"><?= e(Format::money($c['advance'])) ?></td>
                    </tr>
                  <?php endforeach ?>
                </tbody>
              </table>
            </div>
          </div>
        <?php endif ?>
      <?php endif ?>
    </div>

    <!-- ── PAIEMENTS ── -->
    <div id="panel-payments" class="crm-panel space-y-3 pt-4" style="display:none">
      <?php if (empty($payments)): ?>
        <p class="text-sm text-muted-foreground py-8 text-center">Aucun paiement enregistré.</p>
      <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
          <table class="w-full text-sm">
            <thead class="border-b border-border bg-muted/40">
              <tr>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Client</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Progiciel</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Période</th>
                <th class="px-4 py-2.5 text-right font-medium text-muted-foreground">Montant</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Payé le</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <?php foreach ($payments as $p): ?>
                <tr class="hover:bg-muted/20">
                  <td class="px-4 py-2.5"><?= e($p['client_name'] ?? '—') ?></td>
                  <td class="px-4 py-2.5"><?= e($p['product_name'] ?? '—') ?></td>
                  <td class="px-4 py-2.5 text-xs"><?= monthLabel($p['period_month'] ?? '') ?></td>
                  <td class="px-4 py-2.5 text-right font-medium"><?= e(Format::money($p['amount'])) ?></td>
                  <td class="px-4 py-2.5 text-xs text-muted-foreground"><?= e(Format::date($p['paid_at'])) ?></td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>

    <!-- ── CATALOGUE ── -->
    <div id="panel-catalog" class="crm-panel space-y-3 pt-4" style="display:none">
      <?php if ($canCatalog): ?>
        <div class="flex justify-end">
          <button onclick="document.getElementById('dlg-new-product').showModal()"
                  class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            + Nouveau progiciel
          </button>
        </div>
      <?php endif ?>

      <?php if (empty($products)): ?>
        <p class="text-sm text-muted-foreground py-8 text-center">Aucun progiciel dans le catalogue.</p>
      <?php else: ?>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <?php foreach ($products as $prod): ?>
            <div class="rounded-xl border border-border bg-card p-4 space-y-2">
              <div class="flex items-start justify-between gap-2">
                <div>
                  <p class="font-medium"><?= e($prod['name']) ?></p>
                  <p class="text-xs text-muted-foreground"><?= e($prod['code']) ?></p>
                </div>
                <span class="shrink-0 rounded-full px-2 py-0.5 text-xs font-medium <?= $prod['is_active'] ? 'bg-green-100 text-green-700' : 'bg-muted text-muted-foreground' ?>">
                  <?= $prod['is_active'] ? 'Actif' : 'Inactif' ?>
                </span>
              </div>
              <?php if ($prod['description']): ?><p class="text-xs text-muted-foreground"><?= e($prod['description']) ?></p><?php endif ?>
              <p class="font-display text-lg font-bold"><?= e(Format::money($prod['monthly_price'])) ?><span class="text-xs font-normal text-muted-foreground"> / mois</span></p>
              <?php if ($canCatalog): ?>
                <button onclick="openEditProduct(<?= htmlspecialchars(json_encode($prod), ENT_QUOTES) ?>)"
                        class="text-xs text-muted-foreground hover:text-foreground hover:underline">Modifier</button>
              <?php endif ?>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>

    <!-- ── CLIENTS ── -->
    <div id="panel-clients" class="crm-panel space-y-3 pt-4" style="display:none">
      <?php if ($canSubs): ?>
        <div class="flex justify-end">
          <button onclick="document.getElementById('dlg-new-client').showModal()"
                  class="inline-flex items-center gap-2 rounded-lg bg-primary px-3 py-1.5 text-sm font-medium text-primary-foreground hover:bg-primary/90">
            + Nouveau client
          </button>
        </div>
      <?php endif ?>

      <?php if (empty($clients)): ?>
        <p class="text-sm text-muted-foreground py-8 text-center">Aucun client CRM.</p>
      <?php else: ?>
        <div class="overflow-x-auto rounded-xl border border-border bg-card">
          <table class="w-full text-sm">
            <thead class="border-b border-border bg-muted/40">
              <tr>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Nom / Entreprise</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Contact</th>
                <th class="px-4 py-2.5 text-left font-medium text-muted-foreground">Type</th>
                <th class="px-4 py-2.5 font-medium text-muted-foreground">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-border">
              <?php foreach ($clients as $cli): ?>
                <tr class="hover:bg-muted/20">
                  <td class="px-4 py-2.5">
                    <p class="font-medium"><?= e($cli['full_name']) ?></p>
                    <?php if ($cli['company_name']): ?><p class="text-xs text-muted-foreground"><?= e($cli['company_name']) ?></p><?php endif ?>
                  </td>
                  <td class="px-4 py-2.5 text-xs">
                    <?= e($cli['phone'] ?? '') ?><?= $cli['phone'] && $cli['email'] ? ' · ' : '' ?><?= e($cli['email'] ?? '') ?>
                  </td>
                  <td class="px-4 py-2.5 text-xs capitalize"><?= e($cli['client_type']) ?></td>
                  <td class="px-4 py-2.5">
                    <div class="flex gap-1">
                      <?php if ($canSubs): ?>
                        <button onclick="openEditClient(<?= htmlspecialchars(json_encode($cli), ENT_QUOTES) ?>)"
                                class="rounded border border-border px-2 py-0.5 text-xs hover:bg-muted">Modifier</button>
                      <?php endif ?>
                      <?php if ($canCatalog): ?>
                        <form method="POST" action="<?= $base ?>/app/crm/clients/<?= e($cli['id']) ?>/supprimer" onsubmit="return confirm('Supprimer ce client CRM ?')">
                          <?= \App\Core\Csrf::field() ?>
                          <button type="submit" class="rounded border border-border px-2 py-0.5 text-xs text-destructive hover:bg-red-50">Suppr.</button>
                        </form>
                      <?php endif ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach ?>
            </tbody>
          </table>
        </div>
      <?php endif ?>
    </div>

  </div><!-- end tabs -->
</div>

<!-- ── DIALOGS ───────────────────────────────────────────────────────────── -->

<?php $dlgStyle = 'border-radius:1rem;border:1px solid var(--border);background:var(--card);color:var(--foreground);padding:1.5rem;width:min(520px,95vw);box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;'; ?>

<!-- Nouveau progiciel -->
<?php if ($canCatalog): ?>
<dialog id="dlg-new-product" style="<?= $dlgStyle ?>">
  <form method="POST" action="<?= $base ?>/app/crm/produits" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Nouveau progiciel</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1"><label class="text-sm font-medium">Code *</label><input type="text" name="code" required class="app-input w-full" placeholder="EX: ERP-01"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Nom *</label><input type="text" name="name" required class="app-input w-full" placeholder="Nom du progiciel"></div>
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Description</label><textarea name="description" rows="2" class="app-input w-full"></textarea></div>
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Prix mensuel (GNF) *</label><input type="number" name="monthly_price" required min="1" step="1" class="app-input w-full"></div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Créer</button>
    </div>
  </form>
</dialog>

<dialog id="dlg-edit-product" style="<?= $dlgStyle ?>">
  <form method="POST" id="form-edit-product" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Modifier le progiciel</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1"><label class="text-sm font-medium">Code</label><input type="text" name="code" id="ep-code" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Nom</label><input type="text" name="name" id="ep-name" class="app-input w-full"></div>
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Description</label><textarea name="description" id="ep-desc" rows="2" class="app-input w-full"></textarea></div>
      <div class="space-y-1"><label class="text-sm font-medium">Prix mensuel (GNF)</label><input type="number" name="monthly_price" id="ep-price" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Statut</label>
        <select name="is_active" id="ep-active" class="app-input w-full">
          <option value="1">Actif</option>
          <option value="0">Inactif</option>
        </select>
      </div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>
<?php endif ?>

<!-- Nouveau client -->
<?php if ($canSubs): ?>
<dialog id="dlg-new-client" style="<?= $dlgStyle ?>">
  <form method="POST" action="<?= $base ?>/app/crm/clients" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Nouveau client CRM</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Nom complet *</label><input type="text" name="full_name" required class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Entreprise</label><input type="text" name="company_name" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Type</label>
        <select name="client_type" class="app-input w-full">
          <option value="particulier">Particulier</option>
          <option value="entreprise">Entreprise</option>
          <option value="institution">Institution</option>
        </select>
      </div>
      <div class="space-y-1"><label class="text-sm font-medium">Téléphone</label><input type="text" name="phone" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Email</label><input type="email" name="email" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Ville</label><input type="text" name="city" class="app-input w-full"></div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Créer</button>
    </div>
  </form>
</dialog>

<dialog id="dlg-edit-client" style="<?= $dlgStyle ?>">
  <form method="POST" id="form-edit-client" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Modifier le client</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Nom complet</label><input type="text" name="full_name" id="ec-name" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Entreprise</label><input type="text" name="company_name" id="ec-company" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Type</label>
        <select name="client_type" id="ec-type" class="app-input w-full">
          <option value="particulier">Particulier</option>
          <option value="entreprise">Entreprise</option>
          <option value="institution">Institution</option>
        </select>
      </div>
      <div class="space-y-1"><label class="text-sm font-medium">Téléphone</label><input type="text" name="phone" id="ec-phone" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Email</label><input type="email" name="email" id="ec-email" class="app-input w-full"></div>
      <div class="space-y-1"><label class="text-sm font-medium">Ville</label><input type="text" name="city" id="ec-city" class="app-input w-full"></div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<!-- Nouvel abonnement -->
<dialog id="dlg-new-sub" style="<?= $dlgStyle ?>">
  <form method="POST" action="<?= $base ?>/app/crm/abonnements" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Nouvel abonnement</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Client *</label>
        <select name="client_id" required class="app-input w-full">
          <option value="">— Sélectionner —</option>
          <?php foreach ($clients as $c): ?><option value="<?= e($c['id']) ?>"><?= e($c['full_name']) ?><?= $c['company_name'] ? ' (' . e($c['company_name']) . ')' : '' ?></option><?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Progiciel *</label>
        <select name="crm_id" id="ns-crm" required class="app-input w-full" onchange="fillSubPrice()">
          <option value="">— Sélectionner —</option>
          <?php foreach ($products as $p): ?><option value="<?= e($p['id']) ?>" data-price="<?= e($p['monthly_price']) ?>"><?= e($p['name']) ?> (<?= e(Format::money($p['monthly_price'])) ?>/mois)</option><?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Mensualité (GNF) *</label>
        <input type="number" name="monthly_price" id="ns-price" required min="1" step="1" class="app-input w-full">
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Mois de démarrage *</label>
        <input type="month" name="start_month" required class="app-input w-full" value="<?= date('Y-m') ?>">
      </div>
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="app-input w-full"></textarea></div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Créer l'abonnement</button>
    </div>
  </form>
</dialog>
<?php endif ?>

<!-- Enregistrer paiement -->
<?php if ($canPay): ?>
<dialog id="dlg-payment" style="<?= $dlgStyle ?>">
  <form method="POST" id="form-payment" action="<?= $base ?>/app/crm/abonnements/PLACEHOLDER/paiement" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <input type="hidden" id="pay-sub-id" value="">
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Enregistrer un paiement</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div id="pay-sub-info" class="rounded-lg bg-muted/40 px-3 py-2 text-sm text-muted-foreground"></div>
    <div class="grid gap-3 sm:grid-cols-2">
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Mois payés (séparés par virgule, format AAAA-MM-JJ)</label>
        <input type="text" name="months" id="pay-months" class="app-input w-full" placeholder="auto (prochain mois dû)">
        <p class="text-xs text-muted-foreground">Laissez vide pour payer le prochain mois automatiquement.</p>
      </div>
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Montant total (GNF) *</label>
        <input type="number" name="amount" id="pay-amount" required min="1" step="1" class="app-input w-full">
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Mode de paiement</label>
        <select name="payment_method" class="app-input w-full">
          <option value="especes">Espèces</option>
          <option value="orange_money">Orange Money</option>
          <option value="mtn_momo">MTN MoMo</option>
          <option value="virement">Virement</option>
          <option value="cheque">Chèque</option>
          <option value="cinetpay">CinetPay</option>
        </select>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Date de paiement</label>
        <input type="date" name="paid_at" class="app-input w-full" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Compte de réception</label>
        <select name="account_id" class="app-input w-full">
          <option value="">— Sans comptabilité —</option>
          <?php foreach ($accounts as $a): ?><option value="<?= e($a['id']) ?>"><?= e($a['name']) ?></option><?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1 sm:col-span-2"><label class="text-sm font-medium">Notes</label><textarea name="notes" rows="2" class="app-input w-full"></textarea></div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>
<?php endif ?>

<style>
.crm-tab { color: var(--muted-foreground); border-color: transparent; }
.crm-tab.active { color: var(--foreground); border-color: var(--primary); }
</style>
<script>
function switchTab(name) {
  document.querySelectorAll('.crm-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.crm-tab').forEach(t => t.classList.remove('active'));
  document.getElementById('panel-' + name).style.display = '';
  document.getElementById('tab-' + name).classList.add('active');
}
switchTab('subs');

function openPayDialog(sub) {
  const b = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/')) ?>;
  document.getElementById('form-payment').action = b + '/app/crm/abonnements/' + sub.id + '/paiement';
  document.getElementById('pay-sub-id').value = sub.id;
  document.getElementById('pay-amount').value = sub.monthly_price || '';
  document.getElementById('pay-sub-info').textContent =
    (sub.client_name || '') + ' — ' + (sub.product_name || '') +
    (sub.debt > 0 ? ' · Dette : ' + sub.due_count + ' mois' : '');
  document.getElementById('dlg-payment').showModal();
}

function openEditProduct(p) {
  const b = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/')) ?>;
  document.getElementById('form-edit-product').action = b + '/app/crm/produits/' + p.id;
  document.getElementById('ep-code').value  = p.code || '';
  document.getElementById('ep-name').value  = p.name || '';
  document.getElementById('ep-desc').value  = p.description || '';
  document.getElementById('ep-price').value = p.monthly_price || '';
  document.getElementById('ep-active').value = p.is_active ? '1' : '0';
  document.getElementById('dlg-edit-product').showModal();
}

function openEditClient(c) {
  const b = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/')) ?>;
  document.getElementById('form-edit-client').action = b + '/app/crm/clients/' + c.id;
  document.getElementById('ec-name').value    = c.full_name || '';
  document.getElementById('ec-company').value = c.company_name || '';
  document.getElementById('ec-type').value    = c.client_type || 'particulier';
  document.getElementById('ec-phone').value   = c.phone || '';
  document.getElementById('ec-email').value   = c.email || '';
  document.getElementById('ec-city').value    = c.city || '';
  document.getElementById('dlg-edit-client').showModal();
}

function fillSubPrice() {
  const sel = document.getElementById('ns-crm');
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.dataset.price) {
    document.getElementById('ns-price').value = opt.dataset.price;
  }
}
</script>
