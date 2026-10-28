<?php
use function App\Core\e;
$e = $enrollment;
$attNum  = 'ATT-' . strtoupper(substr($e['enrollment_number'] ?? $e['id'], 0, 8));
$fullName = trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? '')) ?: ($e['user_email'] ?? 'Apprenant');
$endDate  = !empty($e['session_end']) ? date('d/m/Y', strtotime($e['session_end'])) : date('d/m/Y');
$startDate = !empty($e['session_start']) ? date('d F Y', strtotime($e['session_start'])) : '—';
$endDateLong = !empty($e['session_end']) ? date('d F Y', strtotime($e['session_end'])) : date('d F Y');
$hours    = $e['total_hours'] ?? null;
$code     = $e['program_code'] ?? '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Attestation — <?= e($attNum) ?></title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Georgia', serif; color: #1a1a1a; background: #fff; }
  .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 20mm 20mm 15mm; position: relative; }
  .header { display: flex; align-items: center; justify-content: space-between; border-bottom: 3px solid #0f4c8a; padding-bottom: 12px; margin-bottom: 32px; }
  .logo { font-size: 24px; font-weight: 900; letter-spacing: -1px; color: #0f4c8a; font-family: sans-serif; }
  .att-num { font-size: 11px; color: #666; font-family: monospace; }
  .body { text-align: center; padding: 40px 0; }
  h1 { font-size: 28px; font-weight: 700; letter-spacing: 2px; text-transform: uppercase; color: #0f4c8a; margin-bottom: 8px; }
  .sub { font-size: 13px; color: #666; margin-bottom: 40px; }
  .certifie { font-size: 15px; line-height: 2; }
  .name { font-size: 26px; font-style: italic; color: #0f4c8a; border-bottom: 1px solid #0f4c8a; display: inline-block; padding: 0 20px; margin: 8px 0; }
  .program { font-size: 20px; font-weight: 700; margin: 16px 0; }
  .meta { font-size: 13px; color: #444; margin-top: 24px; }
  .footer { position: absolute; bottom: 20mm; left: 20mm; right: 20mm; text-align: center; font-size: 10px; color: #aaa; border-top: 1px solid #eee; padding-top: 8px; }
  .seal { width: 80px; height: 80px; border: 3px solid #0f4c8a; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-top: 32px; }
  @media print {
    .no-print { display: none; }
    body { background: white; }
    .page { padding: 10mm 15mm; }
  }
</style>
</head>
<body>

<div class="no-print" style="padding:16px;background:#f0f4f8;text-align:center">
  <button onclick="window.print()" style="padding:10px 24px;background:#0f4c8a;color:white;border:none;border-radius:6px;font-size:14px;cursor:pointer">🖨 Imprimer / Enregistrer en PDF</button>
  <a href="javascript:history.back()" style="margin-left:16px;font-size:13px;color:#666">← Retour</a>
</div>

<div class="page">
  <div class="header">
    <span class="logo">CaNDI</span>
    <span class="att-num">N° <?= e($attNum) ?></span>
  </div>

  <div class="body">
    <h1>Attestation de Formation</h1>
    <p class="sub">Centre d'Apprentissage et de Développement Intégré</p>

    <p class="certifie">Nous soussignés, CaNDI, certifions que :</p>

    <p class="name"><?= e($fullName) ?></p>

    <p class="certifie">a suivi et complété avec succès la formation :</p>

    <p class="program"><?= e($e['program_title'] ?? '—') ?></p>

    <?php if ($code): ?>
      <p style="font-size:12px;color:#666;margin-top:-8px;margin-bottom:8px">Référence : <?= e($code) ?></p>
    <?php endif ?>

    <p class="meta">
      <?php if (!empty($e['session_start'])): ?>
        Du <?= e($startDate) ?> au <?= e($endDateLong) ?>
      <?php endif ?>
      <?php if ($hours): ?>
        · Durée : <?= (int)$hours ?> heures
      <?php endif ?>
      <?php if (!empty($e['session_location'])): ?>
        · Lieu : <?= e($e['session_location']) ?>
      <?php endif ?>
    </p>

    <div style="margin-top:40px;display:flex;justify-content:space-between;align-items:flex-end">
      <div style="text-align:left">
        <p style="font-size:12px;color:#666">Délivré le : <?= date('d/m/Y') ?></p>
        <p style="font-size:12px;color:#666;margin-top:4px">Conakry, République de Guinée</p>
      </div>
      <div style="text-align:center">
        <div class="seal" style="font-size:11px;font-weight:700;color:#0f4c8a;text-align:center;line-height:1.2">
          CaNDI<br>✓
        </div>
        <p style="font-size:10px;color:#666;margin-top:6px">Signature &amp; Cachet</p>
      </div>
    </div>
  </div>

  <div class="footer">
    CaNDI — Centre d'Apprentissage et de Développement Intégré · Conakry, Guinée · www.candi.gn
  </div>
</div>

</body>
</html>
