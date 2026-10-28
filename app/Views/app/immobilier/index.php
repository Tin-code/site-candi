<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$role = \App\Core\Auth::role() ?? 'client';
$canTransfer = $role === 'gestionnaire_immobilier';

$fmt = fn($n) => number_format((float)$n, 0, ',', ' ');

$statusLabels = [
    'libre'        => ['Libre',       'bg-emerald-100 text-emerald-800'],
    'occupe'       => ['Occupé',       'bg-blue-100 text-blue-800'],
    'maintenance'  => ['Maintenance',  'bg-amber-100 text-amber-800'],
    'indisponible' => ['Indisponible', 'bg-red-100 text-red-800'],
];
$leaseStatusLabels = [
    'actif'    => ['Actif',    'bg-blue-100 text-blue-800'],
    'suspendu' => ['Suspendu', 'bg-amber-100 text-amber-800'],
    'termine'  => ['Terminé',  'bg-gray-100 text-gray-600'],
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

// Build maps
$aptMap = [];
foreach ($apartments as $a) { $aptMap[$a['id']] = $a; }
$propMap = [];
foreach ($properties as $p) { $propMap[$p['id']] = $p; }

// Compute ownerOfApt: apartment_id → owner_id
$aptOwnerMap = [];
foreach ($apartments as $a) {
    $oid = $a['owner_id'];
    if (!$oid && $a['property_id']) {
        $oid = $propMap[$a['property_id']]['owner_id'] ?? null;
    }
    $aptOwnerMap[$a['id']] = $oid;
}

// Helper: WhatsApp link
function waLinkImmo(?string $phone, string $message = ''): ?string {
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
$waIconSvg = '<svg class="size-3.5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/><path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.549 4.122 1.513 5.856L0 24l6.335-1.691A11.942 11.942 0 0012 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.818a9.808 9.808 0 01-5.001-1.366l-.356-.215-3.764 1.004 1.024-3.729-.233-.374A9.818 9.818 0 012.182 12 9.818 9.818 0 0112 2.182 9.818 9.818 0 0121.818 12 9.818 9.818 0 0112 21.818z"/></svg>';
?>

<div class="space-y-6" id="immo-page">

  <!-- ── Header ── -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-xl font-bold">Immobilier — appartements & loyers</h1>
      <p class="text-sm text-muted-foreground">Gérez vos immeubles, vos locataires et le recouvrement des loyers.</p>
    </div>
    <div class="flex flex-wrap gap-2">
      <?php if ($canTransfer): ?>
        <button onclick="document.getElementById('dlg-transfer').showModal()"
                class="app-btn-outline text-sm">⇄ Transférer la caisse</button>
      <?php endif ?>
      <button onclick="document.getElementById('dlg-owner').showModal()"
              class="app-btn-outline text-sm">+ Propriétaire</button>
      <button onclick="document.getElementById('dlg-property').showModal()"
              class="app-btn-outline text-sm">+ Propriété</button>
      <button onclick="document.getElementById('dlg-apartment').showModal()"
              class="app-btn-outline text-sm">+ Appartement</button>
      <button onclick="document.getElementById('dlg-lease').showModal()"
              class="app-btn-primary text-sm">+ Bail</button>
    </div>
  </div>

  <!-- ── KPIs ── -->
  <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Appartements</p>
      <p class="font-display text-2xl font-bold text-foreground">
        <?= $stats['apartments_occupied'] ?><span class="text-base font-normal text-muted-foreground"> / <?= $stats['apartments_total'] ?></span>
      </p>
      <p class="text-xs text-muted-foreground">occupés / total</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Encaissé <?= date('M Y') ?></p>
      <p class="font-display text-2xl font-bold text-primary"><?= $fmt($stats['encaisse_this_month']) ?></p>
      <p class="text-xs text-muted-foreground">GNF validés ce mois</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Loyers impayés</p>
      <p class="font-display text-2xl font-bold <?= $stats['total_debt'] > 0 ? 'text-destructive' : 'text-primary' ?>">
        <?= $fmt($stats['total_debt']) ?>
      </p>
      <p class="text-xs text-muted-foreground">GNF de dette totale</p>
    </div>
    <div class="rounded-xl border border-border bg-card p-4">
      <p class="text-xs text-muted-foreground">Paiements à valider</p>
      <p class="font-display text-2xl font-bold <?= $stats['nb_attente'] > 0 ? 'text-amber-600' : 'text-muted-foreground' ?>">
        <?= $stats['nb_attente'] ?>
      </p>
      <p class="text-xs text-muted-foreground">en attente de vérification</p>
    </div>
  </div>

  <!-- ── Filtre propriétaire + Rapports ── -->
  <div class="flex flex-wrap items-end gap-4 rounded-xl border border-border bg-card p-4">
    <div class="space-y-1">
      <label class="block text-xs font-medium text-muted-foreground">Filtrer par propriétaire</label>
      <select id="owner-filter" onchange="filterByOwner(this.value)" class="app-input">
        <option value="">Tous les propriétaires</option>
        <?php foreach ($owners as $o): ?>
          <option value="<?= e($o['id']) ?>"><?= e($o['full_name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="flex flex-wrap gap-2 ml-auto items-center">
      <span class="text-xs text-muted-foreground font-medium">Exporter CSV :</span>
      <?php foreach (['synthese' => 'Synthèse', 'loyers' => 'Loyers', 'dettes' => 'Dettes', 'biens' => 'Biens'] as $type => $label): ?>
        <a id="export-<?= $type ?>"
           href="<?= $base ?>/app/immobilier/rapports/export?type=<?= $type ?>"
           class="app-btn-outline text-xs">↓ <?= $label ?></a>
      <?php endforeach ?>
    </div>
  </div>

  <!-- ── Propriétaires ── -->
  <?php if (!empty($ownersWithStats)): ?>
  <div class="space-y-3">
    <h2 class="font-semibold">Propriétaires (<?= count($ownersWithStats) ?>)</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($ownersWithStats as $o): ?>
        <div class="owner-card rounded-xl border border-border bg-card p-4 space-y-2"
             data-owner-id="<?= e($o['id']) ?>">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-semibold"><?= e($o['full_name']) ?></p>
              <p class="text-xs text-muted-foreground"><?= ucfirst($o['owner_type'] ?? 'particulier') ?><?= $o['city'] ? ' · ' . e($o['city']) : '' ?></p>
            </div>
            <div class="flex gap-1 shrink-0">
              <button onclick="openEditOwner(<?= htmlspecialchars(json_encode($o, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                      class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted text-sm" title="Modifier">✏️</button>
              <form method="POST" action="<?= $base ?>/app/immobilier/proprietaires/<?= e($o['id']) ?>/supprimer" class="inline"
                    onsubmit="return confirm('Archiver ce propriétaire ?')">
                <?= \App\Core\Csrf::field() ?>
                <button class="rounded p-1 text-muted-foreground hover:text-red-600 hover:bg-red-50 text-sm" title="Archiver">🗑</button>
              </form>
            </div>
          </div>
          <div class="grid grid-cols-2 gap-2 text-xs">
            <div><span class="text-muted-foreground">Appts</span><br><strong><?= $o['apt_count'] ?></strong></div>
            <div><span class="text-muted-foreground">Baux actifs</span><br><strong><?= $o['lease_count'] ?></strong></div>
            <div><span class="text-muted-foreground">Loyers/mois</span><br><strong class="text-primary"><?= $fmt($o['monthly_revenue']) ?> GNF</strong></div>
            <div><span class="text-muted-foreground">Commission <?= $o['commission_rate'] ?? 0 ?>%</span><br><strong><?= $fmt($o['commission']) ?> GNF</strong></div>
          </div>
          <?php if (($o['owner_debt'] ?? 0) > 0): ?>
            <p class="text-xs text-destructive font-medium">⚠ Dette : <?= $fmt($o['owner_debt']) ?> GNF</p>
          <?php endif ?>
          <?php $waO = waLinkImmo($o['phone'] ?? null, 'Bonjour ' . $o['full_name'] . ', ici le service immobilier de CaNDI.'); ?>
          <?php if ($waO): ?>
            <a href="<?= e($waO) ?>" target="_blank" rel="noopener"
               class="inline-flex items-center gap-1 text-xs text-emerald-700 hover:underline">
              <?= $waIconSvg ?> WhatsApp
            </a>
          <?php endif ?>
        </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- ── Paiements à valider ── -->
  <?php if (!empty($pending)): ?>
  <div class="rounded-xl border border-amber-200 bg-amber-50 overflow-hidden">
    <div class="border-b border-amber-200 bg-amber-100/60 px-4 py-3 flex items-center justify-between">
      <h2 class="font-semibold text-sm text-amber-900">⏳ Paiements à valider (<?= count($pending) ?>)</h2>
      <a href="<?= $base ?>/app/paiements-loyers" class="text-xs text-amber-700 hover:underline">Voir tout →</a>
    </div>
    <div class="divide-y divide-amber-100">
      <?php foreach ($pending as $p):
        $pendingOwnerId = $aptOwnerMap[$p['apartment_id']] ?? '';
      ?>
        <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3"
             data-owner-id="<?= e($pendingOwnerId) ?>">
          <div>
            <p class="font-medium text-sm"><?= e($p['tenant_name'] ?? '—') ?></p>
            <p class="text-xs text-muted-foreground">
              <?= e($p['apartment_label'] ?? '—') ?><?= $p['property_name'] ? ' · ' . e($p['property_name']) : '' ?>
              · <?= date('M Y', strtotime($p['period_month'])) ?>
              <?php if ($p['provider_reference']): ?> · <span class="font-mono"><?= e($p['provider_reference']) ?></span><?php endif ?>
            </p>
          </div>
          <div class="flex items-center gap-2 flex-wrap">
            <span class="font-semibold text-sm"><?= $fmt($p['amount']) ?> GNF</span>
            <form method="POST" action="<?= $base ?>/app/paiements-loyers/<?= e($p['id']) ?>/valider" class="inline flex gap-1 items-center">
              <?= \App\Core\Csrf::field() ?>
              <select name="account_id" class="app-input text-xs">
                <option value="">— Caisse —</option>
                <?php foreach ($accounts as $acc): ?>
                  <option value="<?= e($acc['id']) ?>"><?= e($acc['name']) ?></option>
                <?php endforeach ?>
              </select>
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

  <!-- ── Locataires & situation ── -->
  <div class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <h2 class="font-semibold">Locataires & situation des loyers (<?= count($leases) ?>)</h2>
      <div class="flex flex-wrap gap-2">
        <input type="search" id="tenant-search" placeholder="Nom, téléphone, code…"
               class="app-input text-sm" oninput="filterTenants()" style="width:200px">
        <select id="tenant-situation" class="app-input text-sm" onchange="filterTenants()">
          <option value="">Toutes situations</option>
          <option value="debt">Impayés</option>
          <option value="ok">À jour</option>
          <option value="advance">En avance</option>
        </select>
        <select id="tenant-status" class="app-input text-sm" onchange="filterTenants()">
          <option value="">Tous statuts</option>
          <option value="actif">Actifs</option>
          <option value="suspendu">Suspendus</option>
          <option value="termine">Terminés</option>
        </select>
      </div>
    </div>
    <p id="tenant-count" class="text-xs text-muted-foreground"></p>

    <?php if (empty($leases)): ?>
      <p class="text-sm text-muted-foreground py-6 text-center">Aucun bail actif.</p>
    <?php else: ?>
      <div id="tenant-grid" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($leases as $l):
          $sit     = $situations[$l['id']] ?? ['due_months' => [], 'debt' => 0, 'advance' => 0];
          $debt    = (float) $sit['debt'];
          $dueNb   = count($sit['due_months']);
          $advance = (int) $sit['advance'];
          [$lsLabel, $lsCls] = $leaseStatusLabels[$l['status']] ?? [$l['status'], 'bg-muted text-muted-foreground'];
          $waMsg  = 'Bonjour ' . $l['tenant_name'] . ', ici le service immobilier de CaNDI. Concernant le logement « ' . ($l['apartment_code'] ?? '') . ($l['apartment_code'] ? ' · ' : '') . $l['apartment_label'] . ' ».';
          $waLink = waLinkImmo($l['tenant_phone'] ?? null, $waMsg);
          $tenantOwnerId = $aptOwnerMap[$l['apartment_id']] ?? '';
        ?>
          <div class="tenant-card rounded-xl border border-border bg-card p-4 space-y-3"
               data-name="<?= strtolower(e($l['tenant_name'])) ?>"
               data-phone="<?= e($l['tenant_phone'] ?? '') ?>"
               data-code="<?= strtolower(e($l['apartment_code'] ?? '')) ?>"
               data-situation="<?= $debt > 0 ? 'debt' : ($advance > 0 ? 'advance' : 'ok') ?>"
               data-status="<?= e($l['status']) ?>"
               data-owner-id="<?= e($tenantOwnerId) ?>">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-semibold"><?= e($l['tenant_name']) ?></p>
                <p class="text-xs text-muted-foreground">
                  <?= e($l['apartment_label']) ?><?= $l['apartment_code'] ? ' · ' . e($l['apartment_code']) : '' ?>
                  <?= $l['property_name'] ? ' — ' . e($l['property_name']) : '' ?>
                </p>
                <?php if ($l['tenant_phone']): ?>
                  <p class="text-xs text-muted-foreground"><?= e($l['tenant_phone']) ?></p>
                <?php endif ?>
              </div>
              <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium shrink-0 <?= $lsCls ?>"><?= $lsLabel ?></span>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-xs">
              <?php if ($debt > 0): ?>
                <span class="inline-flex items-center rounded-full bg-red-100 text-red-800 px-2 py-0.5 font-medium">
                  <?= $dueNb ?> mois dus · <?= $fmt($debt) ?> GNF
                </span>
              <?php elseif ($advance > 0): ?>
                <span class="inline-flex items-center rounded-full bg-blue-100 text-blue-800 px-2 py-0.5 font-medium">
                  <?= $advance ?> mois en avance
                </span>
              <?php else: ?>
                <span class="inline-flex items-center rounded-full bg-emerald-100 text-emerald-800 px-2 py-0.5 font-medium">✓ À jour</span>
              <?php endif ?>
              <span class="text-muted-foreground"><?= $fmt($l['monthly_rent']) ?> GNF/mois · depuis <?= date('m/Y', strtotime($l['start_month'])) ?></span>
            </div>

            <!-- AccessCodeChip -->
            <div class="flex items-center gap-2 text-xs">
              <code class="font-mono bg-muted px-1.5 py-0.5 rounded text-xs"><?= e($l['access_code']) ?></code>
              <button onclick="copyCode('<?= e($l['access_code']) ?>', this)"
                      class="text-muted-foreground hover:text-foreground" title="Copier">📋</button>
              <span class="<?= !empty($l['tenant_user_id']) ? 'text-emerald-700' : 'text-muted-foreground' ?>">
                <?= !empty($l['tenant_user_id']) ? '· compte rattaché' : '· non utilisé' ?>
              </span>
            </div>

            <div class="flex flex-wrap gap-1.5">
              <?php if ($waLink): ?>
                <a href="<?= e($waLink) ?>" target="_blank" rel="noopener"
                   class="inline-flex items-center gap-1 rounded-lg bg-emerald-100 px-2 py-1 text-xs font-medium text-emerald-800 hover:bg-emerald-200">
                  <?= $waIconSvg ?> WhatsApp
                </a>
              <?php endif ?>
              <button onclick="openRecordPayment(<?= htmlspecialchars(json_encode(['id' => $l['id'], 'tenant_name' => $l['tenant_name'], 'monthly_rent' => $l['monthly_rent'], 'tenant_phone' => $l['tenant_phone'] ?? ''], JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                      class="rounded-lg bg-primary px-2 py-1 text-xs font-medium text-primary-foreground hover:opacity-90">
                + Encaisser
              </button>
              <button onclick="openEditLease(<?= htmlspecialchars(json_encode($l, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                      class="rounded-lg border border-border px-2 py-1 text-xs text-muted-foreground hover:text-foreground hover:bg-muted">✏️</button>
              <form method="POST" action="<?= $base ?>/app/immobilier/baux/<?= e($l['id']) ?>/supprimer"
                    class="inline" onsubmit="return confirm('Supprimer ce bail ? Cette action est irréversible.')">
                <?= \App\Core\Csrf::field() ?>
                <button class="rounded-lg border border-border px-2 py-1 text-xs text-muted-foreground hover:text-red-600 hover:bg-red-50">🗑</button>
              </form>
            </div>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>

  <!-- ── Appartements ── -->
  <div class="space-y-3">
    <h2 class="font-semibold">Appartements (<?= count($apartments) ?>)</h2>
    <?php if (empty($apartments)): ?>
      <p class="text-sm text-muted-foreground py-6 text-center">Aucun appartement. Créez d'abord une propriété.</p>
    <?php else: ?>
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <?php foreach ($apartments as $a):
          [$statusLabel, $statusCls] = $statusLabels[$a['status']] ?? ['—', 'bg-muted text-muted-foreground'];
          $aptOwnerId = $aptOwnerMap[$a['id']] ?? '';
        ?>
          <div class="apt-card rounded-xl border border-border bg-card p-4 space-y-2"
               data-owner-id="<?= e($aptOwnerId) ?>">
            <div class="flex items-start justify-between gap-2">
              <div>
                <p class="font-semibold"><?= e($a['label']) ?><?= $a['rooms'] ? ' <span class="text-xs font-normal text-muted-foreground">(' . (int)$a['rooms'] . 'p.)</span>' : '' ?></p>
                <?php if ($a['code']): ?><code class="text-xs font-mono text-muted-foreground"><?= e($a['code']) ?></code><?php endif ?>
              </div>
              <div class="flex items-center gap-1 shrink-0">
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $statusCls ?>"><?= $statusLabel ?></span>
                <button onclick="openEditApartment(<?= htmlspecialchars(json_encode($a, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                        class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted text-sm">✏️</button>
                <form method="POST" action="<?= $base ?>/app/immobilier/appartements/<?= e($a['id']) ?>/supprimer" class="inline"
                      onsubmit="return confirm('Supprimer cet appartement ?')">
                  <?= \App\Core\Csrf::field() ?>
                  <button class="rounded p-1 text-muted-foreground hover:text-red-600 hover:bg-red-50 text-sm">🗑</button>
                </form>
              </div>
            </div>
            <p class="text-xs text-muted-foreground"><?= e($a['property_name'] ?? '—') ?><?= $a['property_city'] ? ' · ' . e($a['property_city']) : '' ?></p>
            <p class="text-sm font-semibold text-primary"><?= $fmt($a['lease_rent'] ?? $a['monthly_rent']) ?> GNF/mois</p>
            <?php if ($a['tenant_name']): ?>
              <p class="text-xs text-muted-foreground">👤 <?= e($a['tenant_name']) ?><?= $a['tenant_phone'] ? ' · ' . e($a['tenant_phone']) : '' ?></p>
            <?php endif ?>
          </div>
        <?php endforeach ?>
      </div>
    <?php endif ?>
  </div>

  <!-- ── Immeubles ── -->
  <?php if (!empty($properties)): ?>
  <div class="space-y-3">
    <h2 class="font-semibold">Immeubles (<?= count($properties) ?>)</h2>
    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <?php foreach ($properties as $p): ?>
        <div class="prop-card rounded-xl border border-border bg-card p-4 space-y-2"
             data-owner-id="<?= e($p['owner_id'] ?? '') ?>">
          <div class="flex items-start justify-between gap-2">
            <div>
              <p class="font-semibold"><?= e($p['name']) ?></p>
              <p class="text-xs text-muted-foreground"><?= e($p['city'] ?? '') ?><?= ($p['city'] && ($p['address'] ?? '')) ? ' · ' : '' ?><?= e($p['address'] ?? '') ?></p>
            </div>
            <div class="flex gap-1 shrink-0">
              <button onclick="openEditProperty(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES) ?>)"
                      class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted text-sm">✏️</button>
              <form method="POST" action="<?= $base ?>/app/immobilier/proprietes/<?= e($p['id']) ?>/supprimer" class="inline"
                    onsubmit="return confirm('Archiver cet immeuble ?')">
                <?= \App\Core\Csrf::field() ?>
                <button class="rounded p-1 text-muted-foreground hover:text-red-600 hover:bg-red-50 text-sm">🗑</button>
              </form>
            </div>
          </div>
          <p class="text-xs text-muted-foreground">Propriétaire : <?= e($p['owner_name'] ?? '—') ?></p>
          <p class="text-xs text-muted-foreground"><?= $p['apartment_count'] ?> appartement(s)</p>
        </div>
      <?php endforeach ?>
    </div>
  </div>
  <?php endif ?>

  <!-- ── Historique des loyers ── -->
  <?php if (!empty($lastPayments)): ?>
  <div class="rounded-xl border border-border bg-card overflow-hidden">
    <div class="border-b border-border px-4 py-3 flex items-center justify-between">
      <h2 class="font-semibold text-sm">Historique des loyers (<?= count($lastPayments) ?> derniers)</h2>
      <a href="<?= $base ?>/app/paiements-loyers" class="text-xs text-muted-foreground hover:text-foreground">Voir tout →</a>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full text-sm">
        <thead class="bg-muted/40 text-xs text-muted-foreground">
          <tr>
            <th class="px-4 py-2 text-left">Date</th>
            <th class="px-4 py-2 text-left">Locataire</th>
            <th class="px-4 py-2 text-left">Appartement</th>
            <th class="px-4 py-2 text-left">Mois</th>
            <th class="px-4 py-2 text-right">Montant</th>
            <th class="px-4 py-2 text-left">Méthode</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-border">
          <?php foreach ($lastPayments as $p): ?>
            <tr class="hover:bg-muted/20">
              <td class="px-4 py-2 text-muted-foreground text-xs"><?= date('d/m/Y', strtotime($p['paid_at'])) ?></td>
              <td class="px-4 py-2 font-medium"><?= e($p['tenant_name'] ?? '—') ?></td>
              <td class="px-4 py-2 text-muted-foreground text-xs"><?= e($p['apartment_label'] ?? '—') ?></td>
              <td class="px-4 py-2 text-muted-foreground"><?= date('M Y', strtotime($p['period_month'])) ?></td>
              <td class="px-4 py-2 text-right font-mono font-semibold text-primary"><?= $fmt($p['amount']) ?></td>
              <td class="px-4 py-2 text-muted-foreground text-xs"><?= $methodLabels[$p['payment_method']] ?? e($p['payment_method']) ?></td>
            </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif ?>

</div>

<!-- ═══════════════ DIALOGS ═══════════════ -->

<!-- Nouveau / Modifier propriétaire -->
<dialog id="dlg-owner" class="immo-dialog">
  <form method="POST" id="form-owner"
        action="<?= $base ?>/app/immobilier/proprietaires"
        data-create-url="<?= $base ?>/app/immobilier/proprietaires"
        class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold" id="dlg-owner-title">Nouveau propriétaire</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5 col-span-2">
        <label class="block text-sm font-medium">Nom complet <span class="text-destructive">*</span></label>
        <input type="text" name="full_name" id="f-owner-name" required class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Type</label>
        <select name="owner_type" id="f-owner-type" class="app-input w-full">
          <option value="particulier">Particulier</option>
          <option value="societe">Société</option>
        </select>
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Commission (%)</label>
        <input type="number" name="commission_rate" id="f-owner-commission" min="0" max="100" step="0.5" class="app-input w-full" value="0">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Téléphone</label>
        <input type="tel" name="phone" id="f-owner-phone" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Email</label>
        <input type="email" name="email" id="f-owner-email" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Ville</label>
        <input type="text" name="city" id="f-owner-city" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Adresse</label>
        <input type="text" name="address" id="f-owner-address" class="app-input w-full">
      </div>
      <div class="space-y-1.5 col-span-2">
        <label class="block text-sm font-medium">Notes</label>
        <textarea name="notes" id="f-owner-notes" rows="2" class="app-input w-full"></textarea>
      </div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<!-- Nouvelle / Modifier propriété -->
<dialog id="dlg-property" class="immo-dialog">
  <form method="POST" id="form-property"
        action="<?= $base ?>/app/immobilier/proprietes"
        data-create-url="<?= $base ?>/app/immobilier/proprietes"
        class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold" id="dlg-prop-title">Nouvelle propriété</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Nom du bâtiment <span class="text-destructive">*</span></label>
      <input type="text" name="name" id="f-prop-name" required class="app-input w-full">
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Ville</label>
        <input type="text" name="city" id="f-prop-city" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Adresse</label>
        <input type="text" name="address" id="f-prop-address" class="app-input w-full">
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Propriétaire</label>
      <select name="owner_id" id="f-prop-owner" class="app-input w-full">
        <option value="">— Aucun —</option>
        <?php foreach ($owners as $o): ?>
          <option value="<?= e($o['id']) ?>"><?= e($o['full_name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<!-- Nouvel / Modifier appartement -->
<dialog id="dlg-apartment" class="immo-dialog">
  <form method="POST" id="form-apartment"
        action="<?= $base ?>/app/immobilier/appartements"
        data-create-url="<?= $base ?>/app/immobilier/appartements"
        class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold" id="dlg-apt-title">Nouvel appartement</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5 col-span-2">
        <label class="block text-sm font-medium">Désignation <span class="text-destructive">*</span></label>
        <input type="text" name="label" id="f-apt-label" required class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Code <small class="text-muted-foreground">(auto si vide)</small></label>
        <input type="text" name="code" id="f-apt-code" class="app-input w-full font-mono">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Pièces</label>
        <input type="number" name="rooms" id="f-apt-rooms" min="1" max="20" class="app-input w-full">
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Propriété</label>
      <select name="property_id" id="f-apt-property" class="app-input w-full">
        <option value="">— Aucune —</option>
        <?php foreach ($propertySelect as $p): ?>
          <option value="<?= e($p['id']) ?>"><?= e($p['name']) ?><?= $p['city'] ? ' (' . e($p['city']) . ')' : '' ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Loyer mensuel (GNF) <span class="text-destructive">*</span></label>
        <input type="number" name="monthly_rent" id="f-apt-rent" required min="0" step="5000" class="app-input w-full">
      </div>
      <div class="space-y-1.5" id="f-apt-status-wrap" style="display:none">
        <label class="block text-sm font-medium">Statut</label>
        <select name="status" id="f-apt-status" class="app-input w-full">
          <option value="libre">Libre</option>
          <option value="occupe">Occupé</option>
          <option value="maintenance">Maintenance</option>
        </select>
      </div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<!-- Nouveau / Modifier bail -->
<dialog id="dlg-lease" class="immo-dialog" style="width:min(560px,95vw)">
  <form method="POST" id="form-lease"
        action="<?= $base ?>/app/immobilier/baux"
        data-create-url="<?= $base ?>/app/immobilier/baux"
        class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold" id="dlg-lease-title">Nouveau bail</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="space-y-1.5" id="f-lease-apt-wrap">
      <label class="block text-sm font-medium">Appartement <span class="text-destructive">*</span></label>
      <!-- Création : select filtré sur les logements libres -->
      <select name="apartment_id" id="f-lease-apt" required class="app-input w-full"
              onchange="fillLeaseRent(this)">
        <option value="">— Sélectionner un logement libre —</option>
        <?php foreach ($freeApartmentSelect as $a): ?>
          <option value="<?= e($a['id']) ?>"
                  data-rent="<?= (int) $a['monthly_rent'] ?>">
            <?= e($a['label']) ?><?= $a['code'] ? ' (' . e($a['code']) . ')' : '' ?>
            <?= $a['property_name'] ? ' — ' . e($a['property_name']) : '' ?>
          </option>
        <?php endforeach ?>
      </select>
      <!-- Édition : affichage lecture seule -->
      <div id="f-lease-apt-readonly" class="hidden">
        <span id="f-lease-apt-label" class="block rounded-md border border-border bg-muted px-3 py-2 text-sm text-muted-foreground"></span>
        <input type="hidden" name="" id="f-lease-apt-hidden">
      </div>
    </div>
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Nom du locataire <span class="text-destructive">*</span></label>
        <input type="text" name="tenant_name" id="f-lease-tenant" required class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Téléphone</label>
        <input type="tel" name="tenant_phone" id="f-lease-phone" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Loyer mensuel (GNF) <span class="text-destructive">*</span></label>
        <input type="number" name="monthly_rent" id="f-lease-rent" required min="0" step="5000" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Caution (GNF)</label>
        <input type="number" name="deposit" id="f-lease-deposit" min="0" step="5000" class="app-input w-full" value="0">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Début bail <span class="text-destructive">*</span></label>
        <input type="month" name="start_month" id="f-lease-start" required class="app-input w-full" value="<?= date('Y-m') ?>">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Statut</label>
        <select name="status" id="f-lease-status" class="app-input w-full">
          <option value="actif">Actif</option>
          <option value="suspendu">Suspendu</option>
          <option value="termine">Terminé</option>
        </select>
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Code d'accès locataire</label>
      <div class="flex gap-2">
        <input type="text" name="access_code" id="f-lease-code" class="app-input w-full font-mono" placeholder="LOC-XXXXXX (auto si vide)">
        <button type="button" onclick="regenerateLeaseCode()" class="app-btn-outline text-xs shrink-0">↺ Régénérer</button>
      </div>
      <p class="text-xs text-muted-foreground">Communiquez ce code au locataire pour rattacher son compte.</p>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer le bail</button>
    </div>
  </form>
</dialog>

<!-- Encaisser un loyer -->
<dialog id="dlg-record-payment" class="immo-dialog" style="width:min(520px,95vw)">
  <form method="POST" action="<?= $base ?>/app/immobilier/paiements" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Encaisser un loyer</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <p id="rec-tenant-info" class="text-sm font-medium text-primary"></p>
    <input type="hidden" name="lease_id" id="rec-lease-id">
    <div class="grid grid-cols-2 gap-3">
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Mois concerné <span class="text-destructive">*</span></label>
        <input type="month" name="period_month" id="rec-month" required class="app-input w-full" value="<?= date('Y-m') ?>">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Montant (GNF) <span class="text-destructive">*</span></label>
        <input type="number" name="amount" id="rec-amount" required min="0" step="500" class="app-input w-full">
      </div>
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
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Numéro payeur</label>
        <input type="tel" name="payer_phone" id="rec-phone" class="app-input w-full">
      </div>
      <div class="space-y-1.5">
        <label class="block text-sm font-medium">Référence</label>
        <input type="text" name="provider_reference" class="app-input w-full" placeholder="PP260922.XXXX">
      </div>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Destination (caisse)</label>
      <select name="account_id" class="app-input w-full">
        <option value="">— Aucun compte —</option>
        <?php foreach ($accounts as $acc): ?>
          <option value="<?= e($acc['id']) ?>"><?= e($acc['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="space-y-1.5">
      <label class="block text-sm font-medium">Notes</label>
      <textarea name="notes" rows="2" class="app-input w-full"></textarea>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Encaisser</button>
    </div>
  </form>
</dialog>

<?php if ($canTransfer): ?>
<dialog id="dlg-transfer" class="immo-dialog">
  <form method="POST" action="<?= $base ?>/app/finances/transferts" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="flex items-center justify-between">
      <h3 class="font-display text-base font-bold">Transférer la caisse loyers</h3>
      <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Compte source</label>
      <select name="from_account_id" required class="app-input w-full">
        <option value="">— Sélectionner —</option>
        <?php foreach ($accounts as $acc): ?>
          <option value="<?= e($acc['id']) ?>"
            <?= (stripos($acc['name'],'loyer')!==false || stripos($acc['name'],'immobilier')!==false) ? 'selected' : '' ?>>
            <?= e($acc['name']) ?> (<?= e($acc['type']) ?>)
          </option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Compte destinataire</label>
      <select name="to_account_id" required class="app-input w-full">
        <option value="">— Sélectionner —</option>
        <?php foreach ($accounts as $acc): ?>
          <option value="<?= e($acc['id']) ?>"><?= e($acc['name']) ?> (<?= e($acc['type']) ?>)</option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Montant (GNF)</label>
      <input type="number" name="amount" min="1" step="1" required class="app-input w-full" placeholder="Ex : 500 000">
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Description</label>
      <input type="text" name="description" class="app-input w-full" placeholder="Transfert caisse loyers vers comptabilité">
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Demander le transfert</button>
    </div>
  </form>
</dialog>
<?php endif ?>

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
</style>

<script>
// ── Dialog reset on close ─────────────────────────────────────────────────
['dlg-owner', 'dlg-property', 'dlg-apartment', 'dlg-lease'].forEach(id => {
  const dialog = document.getElementById(id);
  if (!dialog) return;
  dialog.addEventListener('close', () => {
    const form = dialog.querySelector('form[data-create-url]');
    if (form) {
      form.action = form.dataset.createUrl;
      form.reset();
    }
    const title = dialog.querySelector('[id$="-title"]');
    if (title) {
      if (id === 'dlg-owner')     title.textContent = 'Nouveau propriétaire';
      if (id === 'dlg-property')  title.textContent = 'Nouvelle propriété';
      if (id === 'dlg-apartment') title.textContent = 'Nouvel appartement';
      if (id === 'dlg-lease')     title.textContent = 'Nouveau bail';
    }
    const statusWrap = document.getElementById('f-apt-status-wrap');
    if (statusWrap && id === 'dlg-apartment') statusWrap.style.display = 'none';
    // Restore create mode for lease dialog
    if (id === 'dlg-lease') restoreLeaseAptSelect();
  });
});

// ── Appartement select / auto-remplissage loyer ───────────────────────────
function fillLeaseRent(sel) {
  const opt = sel.options[sel.selectedIndex];
  const rent = opt ? (opt.dataset.rent || '') : '';
  if (rent) document.getElementById('f-lease-rent').value = rent;
}

function restoreLeaseAptSelect() {
  document.getElementById('f-lease-apt').required = true;
  document.getElementById('f-lease-apt').name     = 'apartment_id';
  document.getElementById('f-lease-apt-wrap').querySelector('select').style.display = '';
  document.getElementById('f-lease-apt-readonly').classList.add('hidden');
  document.getElementById('f-lease-apt-hidden').name = '';
}

// ── Owner filter ──────────────────────────────────────────────────────────
function filterByOwner(ownerId) {
  document.querySelectorAll('.owner-card, .tenant-card, .apt-card, .prop-card').forEach(el => {
    el.style.display = (!ownerId || el.dataset.ownerId === ownerId) ? '' : 'none';
  });
  document.querySelectorAll('[id^="export-"]').forEach(a => {
    try {
      const url = new URL(a.href);
      ownerId ? url.searchParams.set('owner_id', ownerId) : url.searchParams.delete('owner_id');
      a.href = url.toString();
    } catch(e) {}
  });
}

// ── Tenant search + filters ───────────────────────────────────────────────
function filterTenants() {
  const q         = document.getElementById('tenant-search').value.toLowerCase().trim();
  const situation = document.getElementById('tenant-situation').value;
  const status    = document.getElementById('tenant-status').value;
  let visible = 0, total = 0;
  document.querySelectorAll('.tenant-card').forEach(el => {
    total++;
    const match = (!q || el.dataset.name.includes(q) || el.dataset.phone.includes(q) || el.dataset.code.includes(q))
      && (!situation || el.dataset.situation === situation)
      && (!status    || el.dataset.status === status);
    el.style.display = match ? '' : 'none';
    if (match) visible++;
  });
  const countEl = document.getElementById('tenant-count');
  if (countEl) countEl.textContent = `${visible} locataire(s) affiché(s) sur ${total}`;
}

// ── Copy access code ──────────────────────────────────────────────────────
function copyCode(code, btn) {
  navigator.clipboard.writeText(code).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓';
    setTimeout(() => btn.textContent = orig, 1500);
  }).catch(() => {
    prompt('Copiez ce code :', code);
  });
}

// ── Record payment dialog ─────────────────────────────────────────────────
function openRecordPayment(lease) {
  document.getElementById('rec-lease-id').value  = lease.id;
  document.getElementById('rec-amount').value    = lease.monthly_rent;
  document.getElementById('rec-phone').value     = lease.tenant_phone || '';
  document.getElementById('rec-tenant-info').textContent = lease.tenant_name;
  document.getElementById('dlg-record-payment').showModal();
}

// ── Edit Owner dialog ─────────────────────────────────────────────────────
function openEditOwner(owner) {
  const f = document.getElementById('form-owner');
  f.action = f.dataset.createUrl.replace(/\/?$/, '/') + owner.id;
  document.getElementById('dlg-owner-title').textContent  = 'Modifier le propriétaire';
  document.getElementById('f-owner-name').value           = owner.full_name || '';
  document.getElementById('f-owner-type').value           = owner.owner_type || 'particulier';
  document.getElementById('f-owner-commission').value     = owner.commission_rate || 0;
  document.getElementById('f-owner-phone').value          = owner.phone || '';
  document.getElementById('f-owner-email').value          = owner.email || '';
  document.getElementById('f-owner-city').value           = owner.city || '';
  document.getElementById('f-owner-address').value        = owner.address || '';
  document.getElementById('f-owner-notes').value          = owner.notes || '';
  document.getElementById('dlg-owner').showModal();
}

// ── Edit Property dialog ──────────────────────────────────────────────────
function openEditProperty(prop) {
  const f = document.getElementById('form-property');
  f.action = f.dataset.createUrl.replace(/\/?$/, '/') + prop.id;
  document.getElementById('dlg-prop-title').textContent = 'Modifier la propriété';
  document.getElementById('f-prop-name').value    = prop.name || '';
  document.getElementById('f-prop-city').value    = prop.city || '';
  document.getElementById('f-prop-address').value = prop.address || '';
  document.getElementById('f-prop-owner').value   = prop.owner_id || '';
  document.getElementById('dlg-property').showModal();
}

// ── Edit Apartment dialog ─────────────────────────────────────────────────
function openEditApartment(apt) {
  const f = document.getElementById('form-apartment');
  f.action = f.dataset.createUrl.replace(/\/?$/, '/') + apt.id;
  document.getElementById('dlg-apt-title').textContent    = 'Modifier l\'appartement';
  document.getElementById('f-apt-label').value    = apt.label || '';
  document.getElementById('f-apt-code').value     = apt.code || '';
  document.getElementById('f-apt-rooms').value    = apt.rooms || '';
  document.getElementById('f-apt-property').value = apt.property_id || '';
  document.getElementById('f-apt-rent').value     = apt.monthly_rent || '';
  document.getElementById('f-apt-status-wrap').style.display = '';
  document.getElementById('f-apt-status').value   = apt.status || 'libre';
  document.getElementById('dlg-apartment').showModal();
}

// ── Edit Lease dialog ─────────────────────────────────────────────────────
function openEditLease(lease) {
  const f = document.getElementById('form-lease');
  f.action = f.dataset.createUrl.replace(/\/?$/, '/') + lease.id;
  document.getElementById('dlg-lease-title').textContent = 'Modifier le bail';
  document.getElementById('f-lease-tenant').value  = lease.tenant_name || '';
  document.getElementById('f-lease-phone').value   = lease.tenant_phone || '';
  document.getElementById('f-lease-rent').value    = lease.monthly_rent || '';
  document.getElementById('f-lease-deposit').value = lease.deposit || 0;
  document.getElementById('f-lease-status').value  = lease.status || 'actif';
  document.getElementById('f-lease-code').value    = lease.access_code || '';
  if (lease.start_month) {
    document.getElementById('f-lease-start').value = lease.start_month.substring(0, 7);
  }
  // En édition : appartement en lecture seule (ne peut pas être changé)
  const aptLabel = [lease.apartment_label, lease.apartment_code ? '(' + lease.apartment_code + ')' : ''].filter(Boolean).join(' ');
  document.getElementById('f-lease-apt-label').textContent = aptLabel || lease.apartment_id || '—';
  document.getElementById('f-lease-apt-hidden').value = lease.apartment_id || '';
  document.getElementById('f-lease-apt-hidden').name  = 'apartment_id';
  document.getElementById('f-lease-apt').required = false;
  document.getElementById('f-lease-apt').name     = '';
  document.getElementById('f-lease-apt-wrap').querySelector('select').style.display = 'none';
  document.getElementById('f-lease-apt-readonly').classList.remove('hidden');
  document.getElementById('dlg-lease').showModal();
}

// ── Regenerate lease code ─────────────────────────────────────────────────
function regenerateLeaseCode() {
  const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
  let code = 'LOC-';
  for (let i = 0; i < 6; i++) code += chars[Math.floor(Math.random() * chars.length)];
  document.getElementById('f-lease-code').value = code;
}

// Init
document.addEventListener('DOMContentLoaded', () => filterTenants());
</script>
