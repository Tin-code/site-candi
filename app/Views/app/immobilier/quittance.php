<?php
use function App\Core\e;
use App\Helpers\Format;

$p = $payment ?? [];

$periodRaw = $p['period_month'] ?? '';
$periodLabel = '';
if ($periodRaw) {
    $d = \DateTime::createFromFormat('Y-m-d', substr($periodRaw, 0, 10));
    if ($d) {
        $months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
        $periodLabel = $months[(int)$d->format('n') - 1] . ' ' . $d->format('Y');
    }
}

$aptLabel = trim(($p['apartment_code'] ? $p['apartment_code'] . ' · ' : '') . ($p['apartment_label'] ?? ''));
$qlNum    = 'QL-' . str_replace('-', '', substr($periodRaw, 0, 7)) . '-' . strtoupper(substr($p['id'] ?? '', 0, 6));

$methodLabels = [
    'especes'       => 'Espèces',
    'orange_money'  => 'Orange Money',
    'mtn_momo'      => 'MTN MoMo',
    'wave'          => 'Wave',
    'virement'      => 'Virement bancaire',
    'cheque'        => 'Chèque',
    'cinetpay'      => 'CinetPay',
];
$method = $methodLabels[$p['payment_method'] ?? ''] ?? ($p['payment_method'] ?? '—');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Quittance de loyer <?= e($qlNum) ?></title>
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body { font-family: 'Helvetica Neue', Arial, sans-serif; font-size: 12px; color: #111; background: #f5f5f5; }
    .wrapper { max-width: 680px; margin: 2rem auto; background: #fff; border: 1px solid #ddd; padding: 2.5rem; }
    .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 1rem; margin-bottom: 1.5rem; }
    .company-name { font-size: 20px; font-weight: 700; letter-spacing: -.5px; }
    .company-info { font-size: 10px; color: #555; line-height: 1.5; margin-top: 3px; }
    .doc-type { text-align: right; }
    .doc-type h1 { font-size: 16px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }
    .doc-number { font-size: 11px; color: #555; margin-top: 3px; }
    .doc-date { font-size: 11px; color: #555; }
    table.info { width: 100%; border-collapse: collapse; margin: 1.5rem 0; }
    table.info td { padding: 5px 8px; vertical-align: top; }
    table.info tr:nth-child(even) { background: #f8f8f8; }
    table.info .label { font-weight: 600; color: #555; width: 160px; }
    .body-text { border: 1px solid #ddd; border-radius: 4px; padding: 1rem; margin: 1.5rem 0; font-size: 11px; line-height: 1.6; color: #333; background: #fafafa; }
    .signature { margin-top: 2.5rem; text-align: right; font-size: 11px; color: #555; }
    .footer { margin-top: 2rem; border-top: 1px solid #ddd; padding-top: .75rem; font-size: 9px; color: #999; text-align: center; }
    .no-print { margin: 1.5rem auto; max-width: 680px; display: flex; gap: .75rem; }
    .btn-print { padding: .5rem 1.25rem; background: #111; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-size: 13px; }
    .btn-back { padding: .5rem 1.25rem; background: #fff; color: #111; border: 1px solid #ccc; border-radius: 6px; cursor: pointer; font-size: 13px; text-decoration: none; }
    @media print {
      body { background: #fff; }
      .wrapper { border: none; margin: 0; padding: 1.5cm; max-width: 100%; }
      .no-print { display: none !important; }
    }
  </style>
</head>
<body>

<div class="no-print">
  <a href="javascript:history.back()" class="btn-back">← Retour</a>
  <button class="btn-print" onclick="window.print()">Imprimer / Télécharger PDF</button>
</div>

<div class="wrapper">
  <div class="header">
    <div>
      <div class="company-name">CaNDI</div>
      <div class="company-info">
        Conakry, Kaloum / Kankan, Sogbè<br>
        Tél : 626 01 17 17 / 625 29 54 56<br>
        RCCM/GN.TCC.2022.A.11573
      </div>
    </div>
    <div class="doc-type">
      <h1>Quittance de loyer</h1>
      <div class="doc-number">N° <?= e($qlNum) ?></div>
      <div class="doc-date">Émise le <?= e(Format::date($p['paid_at'] ?? null)) ?></div>
    </div>
  </div>

  <table class="info">
    <tr>
      <td class="label">Locataire</td>
      <td><?= e($p['tenant_name'] ?? '—') ?><?= !empty($p['tenant_phone']) ? ' · ' . e($p['tenant_phone']) : '' ?></td>
    </tr>
    <tr>
      <td class="label">Logement</td>
      <td><?= e($aptLabel ?: '—') ?></td>
    </tr>
    <?php if (!empty($p['owner_name'])): ?>
    <tr>
      <td class="label">Propriétaire</td>
      <td><?= e($p['owner_name']) ?></td>
    </tr>
    <?php endif ?>
    <tr>
      <td class="label">Période</td>
      <td><?= e($periodLabel ?: $periodRaw) ?></td>
    </tr>
    <tr>
      <td class="label">Montant reçu</td>
      <td><strong><?= e(Format::money($p['amount'] ?? 0)) ?></strong></td>
    </tr>
    <tr>
      <td class="label">Mode de paiement</td>
      <td><?= e($method) ?></td>
    </tr>
    <?php if (!empty($p['provider_reference'])): ?>
    <tr>
      <td class="label">Référence</td>
      <td><?= e($p['provider_reference']) ?></td>
    </tr>
    <?php endif ?>
  </table>

  <div class="body-text">
    Je soussigné, CaNDI, agissant en qualité de gestionnaire<?= !empty($p['owner_name']) ? ' pour le compte de ' . e($p['owner_name']) : '' ?>,
    déclare avoir reçu de <strong><?= e($p['tenant_name'] ?? '—') ?></strong>
    la somme de <strong><?= e(Format::money($p['amount'] ?? 0)) ?></strong>
    au titre du loyer de <strong><?= e($periodLabel ?: $periodRaw) ?></strong>
    pour le logement <strong><?= e($aptLabel ?: '—') ?></strong>.<br><br>
    La présente quittance est délivrée sous réserve de tous droits et vaut reçu pour la période indiquée.
  </div>

  <div class="signature">
    <p>Pour CaNDI, le gestionnaire</p>
    <br><br><br>
    <p>____________________________</p>
    <p style="margin-top:4px">Signature &amp; cachet</p>
  </div>

  <div class="footer">
    CaNDI — RCCM/GN.TCC.2022.A.11573 — Conakry, Kaloum / Kankan, Sogbè — Tél : 626 01 17 17
  </div>
</div>

</body>
</html>
<?php exit; // bypass layout ?>
