<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$fmt = fn($n) => number_format((float)$n, 0, ',', ' ');

$statusLabels = [
    'en_attente' => ['En vérification', 'bg-amber-100 text-amber-800'],
    'validee'    => ['Validé',           'bg-emerald-100 text-emerald-800'],
    'refusee'    => ['Refusé',           'bg-red-100 text-red-800'],
];
$methodLabels = [
    'especes'      => 'Espèces',
    'orange_money' => 'Orange Money',
    'cinetpay'     => 'CinetPay',
    'virement'     => 'Virement',
    'cheque'       => 'Chèque',
    'autre'        => 'Autre',
    'mobile_money' => 'Mobile Money',
];

// WhatsApp helper
function waLinkLoyers(?string $phone, string $message = ''): ?string {
    if (!$phone) return null;
    $d = preg_replace('/[^\d]/', '', $phone);
    if (!$d) return null;
    if (str_starts_with($d, '00')) $d = substr($d, 2);
    if (!str_starts_with($d, '224')) {
        if (strlen($d) === 8 || strlen($d) === 9) $d = '224' . $d;
    }
    if (strlen($d) < 10) return null;
    return 'https://wa.me/' . $d . ($message ? '?text=' . rawurlencode($message) : '');
}

// Check if any filter is active (for reset link)
$hasFilter = !empty($filters['status']) || !empty($filters['month']) || !empty($filters['q'])
          || !empty($filters['owner_id']) || !empty($filters['date_from']) || !empty($filters['date_to']);

// Totaux pour summary row
$totalShown    = 0;
$totalEncaisse = 0.0;
foreach ($payments as $p) {
    $totalShown++;
    if ($p['status'] === 'validee') $totalEncaisse += (float) $p['amount'];
}
?>

<div class="space-y-6" id="loyers-page">

  <!-- ── Header ── -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-xl font-bold">Gestion des paiements</h1>
      <p class="text-sm text-muted-foreground">Loyers encaissés, reliquats et quittances.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <button onclick="window.print()" class="app-btn-outline text-sm no-print">🖨 Imprimer</button>
      <button onclick="document.getElementById('dlg-rec-payment').showModal()"
              class="app-btn-primary text-sm no-print">+ Enregistrer un paiement</button>
    </div>
  </div>

  <!-- ── KPIs ── -->
  <div class="grid grid-cols-3 gap-3 no-print">
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Loyers validés ce mois</p>
      <p class="font-display text-xl font-bold text-primary"><?= $fmt($stats['total_valide'] ?? 0) ?> GNF</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">En attente de validation</p>
      <p class="font-display text-xl font-bold text-amber-600"><?= $fmt($stats['total_attente'] ?? 0) ?> GNF</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Paiements à valider</p>
      <p class="font-display text-xl font-bold <?= ($stats['nb_attente'] ?? 0) > 0 ? 'text-amber-600' : 'text-muted-foreground' ?>">
        <?= (int) ($stats['nb_attente'] ?? 0) ?>
      </p>
    </div>
  </div>

  <!-- ── Paiements en attente ── -->
  <?php if (!empty($pending)): ?>
  <div class="rounded-xl border border-amber-200 bg-amber-50 overflow-hidden no-print">
    <div class="border-b border-amber-200 bg-amber-100/60 px-4 py-3">
      <h2 class="font-semibold text-sm text-amber-900">⏳ En attente de validation (<?= count($pending) ?>)</h2>
    </div>
    <div class="divide-y divide-amber-100">
      <?php foreach ($pending as $p): ?>
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
          <div>
            <p class="font-medium text-sm"><?= e($p['tenant_name'] ?? '—') ?></p>
            <p class="text-xs text-muted-foreground">
              <?= e($p['apartment_label'] ?? '—') ?><?= $p['property_name'] ? ' · ' . e($p['property_name']) : '' ?>
              · <?= date('M Y', strtotime($p['period_month'])) ?>
            </p>
            <?php if ($p['provider_reference']): ?>
              <p class="text-xs font-mono text-muted-foreground">Réf. <?= e($p['provider_reference']) ?></p>
            <?php endif ?>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-semibold text-sm"><?= $fmt($p['amount']) ?> GNF</span>
            <span class="text-xs text-muted-foreground"><?= $methodLabels[$p['payment_method']] ?? e($p['payment_method']) ?></span>
            <form method="POST" action="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/valider" class="inline">
              <?= \App\Core\Csrf::field() ?>
              <button class="rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-emerald-700">✓ Valider</button>
            </form>
            <form method="POST" action="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/refuser" class="inline">
              <?= \App\Core\Csrf::field() ?>
              <button class="rounded-lg border border-red-300 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50">✕ Refuser</button>
            </form>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- ── Filtres ── -->
  <form method="GET" class="grid grid-cols-2 gap-3 sm:flex sm:flex-wrap sm:items-end no-print">
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Recherche</label>
      <input type="text" name="q" class="app-input" placeholder="Nom, appartement, réf…"
             value="<?= e($filters['q'] ?? '') ?>" style="width:180px">
    </div>
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Propriétaire</label>
      <select name="owner_id" class="app-input">
        <option value="">Tous</option>
        <?php foreach ($owners as $o): ?>
          <option value="<?= e($o['id']) ?>" <?= ($filters['owner_id'] ?? '') === $o['id'] ? 'selected' : '' ?>>
            <?= e($o['full_name']) ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Statut</label>
      <select name="status" class="app-input">
        <option value="">Tous (sauf refusés)</option>
        <option value="en_attente" <?= ($filters['status'] ?? '') === 'en_attente' ? 'selected' : '' ?>>En attente</option>
        <option value="validee"    <?= ($filters['status'] ?? '') === 'validee'    ? 'selected' : '' ?>>Validés</option>
        <option value="refusee"    <?= ($filters['status'] ?? '') === 'refusee'    ? 'selected' : '' ?>>Refusés</option>
      </select>
    </div>
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Mois</label>
      <input type="month" name="month" class="app-input" value="<?= e($filters['month'] ?? '') ?>">
    </div>
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Du</label>
      <input type="date" name="date_from" class="app-input" value="<?= e($filters['date_from'] ?? '') ?>">
    </div>
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Au</label>
      <input type="date" name="date_to" class="app-input" value="<?= e($filters['date_to'] ?? '') ?>">
    </div>
    <div class="flex gap-2 items-end">
      <button type="submit" class="app-btn-outline text-sm">Filtrer</button>
      <?php if ($hasFilter): ?>
        <a href="<?= $base ?>/app/paiements-loyers" class="text-sm text-muted-foreground hover:text-foreground pb-1">Réinitialiser</a>
      <?php endif ?>
    </div>
  </form>

  <!-- ── Tableau des paiements ── -->
  <div class="rounded-xl border border-border bg-card overflow-hidden">
    <div class="border-b border-border px-4 py-3 flex items-center justify-between no-print">
      <h2 class="font-semibold text-sm">
        Paiements (<?= $totalShown ?>)
        <?php if ($totalEncaisse > 0): ?>
          · <span class="text-primary">Total encaissé : <?= $fmt($totalEncaisse) ?> GNF</span>
        <?php endif ?>
      </h2>
      <?php if ($total > count($payments)): ?>
        <span class="text-xs text-muted-foreground"><?= count($payments) ?> / <?= $total ?> affichés</span>
      <?php endif ?>
    </div>

    <?php if (empty($payments)): ?>
      <div class="px-4 py-10 text-center text-sm text-muted-foreground">Aucun paiement trouvé.</div>
    <?php else: ?>
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead class="bg-muted/40 text-xs text-muted-foreground">
            <tr>
              <th class="px-4 py-2 text-left">Date</th>
              <th class="px-4 py-2 text-left">Référence</th>
              <th class="px-4 py-2 text-left">Locataire / Appartement</th>
              <th class="px-4 py-2 text-left">Tél.</th>
              <th class="px-4 py-2 text-left">Mois payé</th>
              <th class="px-4 py-2 text-right">Montant / Méthode</th>
              <th class="px-4 py-2 text-right">Total dû</th>
              <th class="px-4 py-2 text-right">Reste</th>
              <th class="px-4 py-2 text-left">Statut</th>
              <th class="px-4 py-2 text-left no-print">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-border">
            <?php
            $grandTotalEncaisse = 0.0;
            foreach ($payments as $p):
              [$statusLabel, $statusCls] = $statusLabels[$p['status']] ?? [$p['status'], 'bg-muted text-muted-foreground'];
              $ref = $p['provider_reference'] ?: 'CANDI-' . strtoupper(substr($p['id'], 0, 8));

              // Total dû (mois) & Reste à payer
              $monthKey    = substr($p['period_month'], 0, 7);
              $leaseRent   = (float) ($p['lease_monthly_rent'] ?? 0);
              $validated   = (float) (($validatedByLease[$p['lease_id']] ?? [])[$monthKey] ?? 0);
              $reste       = max($leaseRent - $validated, 0);

              if ($p['status'] === 'validee') $grandTotalEncaisse += (float) $p['amount'];

              $waMsg  = 'Bonjour ' . ($p['tenant_name'] ?? '') . ', ici le service immobilier de CaNDI. Concernant votre loyer du ' . date('m/Y', strtotime($p['period_month'])) . ' pour « ' . ($p['apartment_label'] ?? '') . ' ».';
              $waLink = waLinkLoyers($p['tenant_phone'] ?? null, $waMsg);
            ?>
              <tr class="hover:bg-muted/20">
                <td class="px-4 py-3 text-muted-foreground text-xs whitespace-nowrap">
                  <?= date('d/m/Y', strtotime($p['paid_at'])) ?>
                </td>
                <td class="px-4 py-3 text-xs font-mono text-muted-foreground whitespace-nowrap">
                  <?= e($ref) ?>
                </td>
                <td class="px-4 py-3">
                  <p class="font-medium"><?= e($p['tenant_name'] ?? '—') ?></p>
                  <p class="text-xs text-muted-foreground"><?= e($p['apartment_label'] ?? '—') ?><?= $p['property_name'] ? ' · ' . e($p['property_name']) : '' ?></p>
                </td>
                <td class="px-4 py-3 text-xs text-muted-foreground"><?= e($p['tenant_phone'] ?? '—') ?></td>
                <td class="px-4 py-3 text-muted-foreground whitespace-nowrap">
                  <?= date('M Y', strtotime($p['period_month'])) ?>
                </td>
                <td class="px-4 py-3 text-right">
                  <p class="font-mono font-semibold <?= $p['status'] === 'validee' ? 'text-primary' : '' ?>"><?= $fmt($p['amount']) ?></p>
                  <p class="text-xs text-muted-foreground"><?= $methodLabels[$p['payment_method']] ?? e($p['payment_method']) ?></p>
                </td>
                <td class="px-4 py-3 text-right text-xs text-muted-foreground font-mono">
                  <?= $leaseRent > 0 ? $fmt($leaseRent) : '—' ?>
                </td>
                <td class="px-4 py-3 text-right text-xs font-mono font-semibold <?= $reste > 0 ? 'text-destructive' : ($leaseRent > 0 ? 'text-emerald-700' : '') ?>">
                  <?= $leaseRent > 0 ? ($reste > 0 ? $fmt($reste) : '✓ 0') : '—' ?>
                </td>
                <td class="px-4 py-3">
                  <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $statusCls ?>">
                    <?= $statusLabel ?>
                  </span>
                </td>
                <td class="px-4 py-3 no-print">
                  <div class="flex items-center gap-1 flex-wrap">
                    <?php if ($p['status'] === 'validee'): ?>
                      <a href="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/quittance"
                         class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted text-sm" title="Quittance">📄</a>
                    <?php endif ?>
                    <button onclick="openEditPayment(<?= htmlspecialchars(json_encode(['id' => $p['id'], 'paid_at' => substr($p['paid_at'], 0, 10), 'provider_reference' => $p['provider_reference'] ?? '', 'notes' => $p['notes'] ?? ''], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                            class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted text-sm" title="Modifier">✏️</button>
                    <?php if ($p['status'] !== 'validee'): ?>
                      <form method="POST" action="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/supprimer"
                            class="inline" onsubmit="return confirm('Supprimer ce paiement ?')">
                        <?= \App\Core\Csrf::field() ?>
                        <button class="rounded p-1 text-muted-foreground hover:text-red-600 hover:bg-red-50 text-sm" title="Supprimer">🗑</button>
                      </form>
                    <?php endif ?>
                    <?php if ($waLink): ?>
                      <a href="<?= e($waLink) ?>" target="_blank" rel="noopener"
                         class="rounded p-1 text-emerald-600 hover:bg-emerald-50 text-sm" title="WhatsApp">
                        <svg class="size-3.5 inline" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.549 4.122 1.513 5.856L0 24l6.335-1.691A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.808 9.808 0 01-5.001-1.366l-.356-.215-3.764 1.004 1.024-3.729-.233-.374A9.818 9.818 0 012.182 12 9.818 9.818 0 0112 2.182 9.818 9.818 0 0121.818 12 9.818 9.818 0 0112 21.818z"/></svg>
                      </a>
                    <?php endif ?>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>
          <tfoot>
            <tr class="bg-muted/30 font-semibold text-sm border-t-2 border-border">
              <td colspan="5" class="px-4 py-2 text-muted-foreground"><?= $totalShown ?> paiement(s)</td>
              <td class="px-4 py-2 text-right font-mono text-primary"><?= $fmt($grandTotalEncaisse) ?></td>
              <td colspan="4" class="px-4 py-2 text-xs text-muted-foreground">GNF validés</td>
            </tr>
          </tfoot>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total > count($payments)): ?>
        <div class="px-4 py-3 border-t border-border flex items-center justify-between text-sm no-print">
          <span class="text-muted-foreground">Page <?= $page ?> · <?= $total ?> total</span>
          <div class="flex gap-2">
            <?php if ($page > 1): ?>
              <a href="?<?= http_build_query(array_merge($filters, ['page' => $page - 1])) ?>"
                 class="app-btn-outline text-xs">← Précédent</a>
            <?php endif ?>
            <?php if ($page * 50 < $total): ?>
              <a href="?<?= http_build_query(array_merge($filters, ['page' => $page + 1])) ?>"
                 class="app-btn-outline text-xs">Suivant →</a>
            <?php endif ?>
          </div>
        </div>
      <?php endif ?>
    <?php endif ?>
  </div>

</div>

<!-- ═══════════ DIALOGS ═══════════ -->

<!-- Dialog : Modifier un paiement -->
<dialog id="dlg-edit-payment" class="immo-dialog no-print">
  <form method="POST" id="form-edit-payment" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Modifier le paiement</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <p class="text-xs bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 text-amber-800">
      Le montant et le mois ne sont pas modifiables ici pour préserver la caisse.
    </p>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Date du paiement</label>
        <input type="date" name="paid_at" id="ep-paid-at" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Référence</label>
        <input type="text" name="provider_reference" id="ep-ref" class="app-input w-full font-mono" placeholder="PP260922.XXXX">
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Notes</label>
      <textarea name="notes" id="ep-notes" rows="2" class="app-input w-full"></textarea>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<!-- Dialog : Enregistrer un paiement -->
<dialog id="dlg-rec-payment" class="immo-dialog no-print" style="width:min(560px,95vw)">
  <form method="POST" action="<?= $base ?>/app/immobilier/paiements" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Enregistrer un paiement</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>

    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Locataire / Bail <span class="text-destructive">*</span></label>
      <input type="text" id="lease-search" placeholder="Rechercher un locataire…"
             class="app-input w-full" oninput="filterLeaseOptions(this.value)" autocomplete="off">
      <select name="lease_id" id="lease-select" required class="app-input w-full" size="4"
              style="height:auto;padding:0" onchange="onLeaseChange(this)">
        <?php foreach ($leases as $l): ?>
          <option value="<?= e($l['id']) ?>"
                  data-rent="<?= (int)$l['monthly_rent'] ?>"
                  data-phone="<?= e($l['tenant_phone'] ?? '') ?>"
                  data-name="<?= strtolower(e($l['tenant_name'])) ?>"
                  data-label="<?= strtolower(e($l['apartment_label'] ?? '')) ?>">
            <?= e($l['tenant_name']) ?> — <?= e($l['apartment_label'] ?? $l['apartment_id']) ?><?= $l['property_name'] ? ' (' . e($l['property_name']) . ')' : '' ?>
          </option>
        <?php endforeach ?>
      </select>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Mois payé <span class="text-destructive">*</span></label>
        <input type="month" name="period_month" required class="app-input w-full" value="<?= date('Y-m') ?>">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Montant (GNF) <span class="text-destructive">*</span></label>
        <input type="number" name="amount" id="rec-amount" required min="0" step="500" class="app-input w-full">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Moyen de paiement</label>
        <select name="payment_method" class="app-input w-full">
          <option value="especes">Espèces</option>
          <option value="orange_money">Orange Money</option>
          <option value="cinetpay">CinetPay</option>
          <option value="virement">Virement</option>
          <option value="cheque">Chèque</option>
          <option value="autre">Autre</option>
        </select>
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Date du paiement</label>
        <input type="date" name="paid_at" class="app-input w-full" value="<?= date('Y-m-d') ?>">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Numéro payeur</label>
        <input type="tel" name="payer_phone" id="rec-payer-phone" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Référence</label>
        <input type="text" name="provider_reference" class="app-input w-full" placeholder="PP260922.XXXX">
      </div>
    </div>

    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Compte financier</label>
        <select name="account_id" class="app-input w-full">
          <option value="">— Aucun —</option>
          <?php foreach ($accounts as $acc): ?>
            <option value="<?= e($acc['id']) ?>"><?= e($acc['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Notes</label>
        <textarea name="notes" rows="1" class="app-input w-full"></textarea>
      </div>
    </div>

    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<style>
.immo-dialog {
  border-radius: 1rem; border: 1px solid var(--border);
  background: var(--card); color: var(--foreground);
  padding: 1.5rem; width: min(480px, 95vw);
  box-shadow: 0 20px 60px rgba(0,0,0,.2);
  margin: auto;
}
.immo-dialog::backdrop { background: rgba(0,0,0,.45); }
.app-input {
  border-radius: .5rem; border: 1px solid var(--border);
  background: var(--background);
  padding: .4375rem .75rem; font-size: .875rem; color: var(--foreground);
  outline: none; transition: border-color .15s;
}
.app-input:focus { border-color: var(--primary); }
.app-btn-primary {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; background: var(--primary); color: var(--primary-foreground);
  padding: .4375rem 1.25rem; font-size: .875rem; font-weight: 600;
  cursor: pointer; border: none; transition: opacity .15s;
}
.app-btn-primary:hover { opacity: .88; }
.app-btn-outline {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; border: 1px solid var(--border); background: transparent;
  color: var(--foreground); padding: .4375rem 1rem; font-size: .875rem; font-weight: 500;
  cursor: pointer; transition: background .15s;
}
.app-btn-outline:hover { background: var(--muted); }
select[size] > option { padding: .375rem .75rem; }
select[size] > option:checked { background: var(--primary); color: var(--primary-foreground); }
@media print {
  .no-print { display: none !important; }
  #loyers-page { font-size: 11px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { border: 1px solid #ccc; padding: 4px 6px; }
}
</style>

<script>
function openEditPayment(payment) {
  const f = document.getElementById('form-edit-payment');
  f.action = '<?= $base ?>/app/paiements-loyers/' + payment.id + '/modifier';
  document.getElementById('ep-paid-at').value = payment.paid_at || '';
  document.getElementById('ep-ref').value     = payment.provider_reference || '';
  document.getElementById('ep-notes').value   = payment.notes || '';
  document.getElementById('dlg-edit-payment').showModal();
}

function filterLeaseOptions(q) {
  q = q.toLowerCase().trim();
  document.querySelectorAll('#lease-select option').forEach(opt => {
    const match = !q || opt.dataset.name.includes(q) || opt.dataset.label.includes(q);
    opt.style.display = match ? '' : 'none';
  });
}

function onLeaseChange(sel) {
  const opt = sel.options[sel.selectedIndex];
  if (opt) {
    const rent  = opt.dataset.rent;
    const phone = opt.dataset.phone;
    if (rent)  document.getElementById('rec-amount').value      = rent;
    if (phone) document.getElementById('rec-payer-phone').value = phone;
  }
}
</script>
