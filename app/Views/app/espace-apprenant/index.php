<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$fmt     = fn($n) => number_format((float)$n, 0, ',', ' ');
$fmtDate = fn($d) => $d ? date('d/m/Y', strtotime($d)) : '—';

$statusLabels = [
    'en_attente'       => ['En attente',       'bg-amber-100 text-amber-800'],
    'paiement_partiel' => ['Acompte versé',     'bg-blue-100 text-blue-800'],
    'confirmee'        => ['Confirmée',         'bg-emerald-100 text-emerald-800'],
    'en_cours'         => ['En cours',          'bg-primary/10 text-primary'],
    'terminee'         => ['Terminée',          'bg-gray-100 text-gray-700'],
    'annulee'          => ['Annulée',           'bg-red-100 text-red-800'],
    'refusee'          => ['Refusée',           'bg-red-100 text-red-800'],
    'nouvelle_demande' => ['Nouvelle demande',  'bg-amber-100 text-amber-800'],
];

$modalityLabels = [
    'presentiel' => 'Présentiel',
    'en_ligne'   => 'En ligne',
    'hybride'    => 'Hybride',
];
?>

<div class="space-y-6">

  <div>
    <h1 class="font-display text-xl font-bold">Espace apprenant</h1>
    <p class="text-sm text-muted-foreground">Vos sessions, invitations calendrier, supports de cours et attestations.</p>
  </div>

  <!-- ── Section Formateur : dépôt de supports ── -->
  <?php if ($isTrainer): ?>
  <div class="rounded-xl border border-border bg-card overflow-hidden">
    <div class="border-b border-border px-4 py-3 flex items-center justify-between">
      <h2 class="font-semibold text-sm">Déposer un support de cours</h2>
      <button onclick="document.getElementById('trainer-form').classList.toggle('hidden')"
              class="text-xs text-muted-foreground hover:text-foreground">Afficher / Masquer</button>
    </div>
    <div id="trainer-form">
      <form method="POST" action="<?= $base ?>/app/espace-apprenant/materiaux"
            enctype="multipart/form-data" class="p-4 space-y-4">
        <?= \App\Core\Csrf::field() ?>
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="space-y-1.5">
            <label class="block text-sm font-medium">Formation <span class="text-destructive">*</span></label>
            <select name="program_id" id="trainer-program" class="app-input w-full" onchange="filterTrainerSessions(this.value)" required>
              <option value="">— Choisir —</option>
              <?php foreach ($programs as $p): ?>
                <option value="<?= e($p['id']) ?>"><?= e($p['title']) ?></option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium">Session <small class="text-muted-foreground">(optionnel)</small></label>
            <select name="session_id" id="trainer-session" class="app-input w-full">
              <option value="">Toutes les sessions</option>
              <?php foreach ($sessions as $s): ?>
                <option value="<?= e($s['id']) ?>" data-program="<?= e($s['program_id']) ?>">
                  <?= e($s['code']) ?> — <?= $fmtDate($s['start_date']) ?>
                </option>
              <?php endforeach ?>
            </select>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium">Titre du support <span class="text-destructive">*</span></label>
            <input type="text" name="title" class="app-input w-full" placeholder="Ex : Module 1 — Introduction" required>
          </div>
          <div class="space-y-1.5">
            <label class="block text-sm font-medium">Fichier <span class="text-destructive">*</span> <small class="text-muted-foreground">(50 Mo max)</small></label>
            <input type="file" name="material_file" class="app-input w-full" required
                   accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.mp4,.png,.jpg,.jpeg">
          </div>
        </div>
        <div class="flex justify-end">
          <button type="submit" class="app-btn-primary text-sm">↑ Déposer le support</button>
        </div>
      </form>

      <?php if (!empty($allMaterials)): ?>
        <div class="border-t border-border divide-y divide-border">
          <?php foreach ($allMaterials as $m): ?>
            <div class="flex items-center justify-between gap-3 px-4 py-2 text-sm">
              <div class="min-w-0">
                <span class="font-medium truncate"><?= e($m['title']) ?></span>
                <span class="text-xs text-muted-foreground ml-2">· <?= e($m['program_title'] ?? '—') ?><?= $m['session_code'] ? ' · ' . e($m['session_code']) : '' ?></span>
              </div>
              <div class="flex gap-1 shrink-0">
                <a href="<?= e($m['file_path']) ?>" target="_blank" rel="noopener"
                   class="rounded p-1 text-muted-foreground hover:text-foreground hover:bg-muted" title="Télécharger">↓</a>
                <form method="POST" action="<?= $base ?>/app/espace-apprenant/materiaux/<?= e($m['id']) ?>/supprimer"
                      class="inline" onsubmit="return confirm('Supprimer ce support ?')">
                  <?= \App\Core\Csrf::field() ?>
                  <button class="rounded p-1 text-muted-foreground hover:text-red-600 hover:bg-red-50 text-xs" title="Supprimer">🗑</button>
                </form>
              </div>
            </div>
          <?php endforeach ?>
        </div>
      <?php endif ?>
    </div>
  </div>
  <?php endif ?>

  <!-- ── Inscriptions de l'apprenant ── -->
  <?php if (empty($enrollments)): ?>
    <div class="rounded-xl border border-border bg-card p-8 text-center text-sm text-muted-foreground">
      Aucune inscription pour le moment.
      <a href="<?= $base ?>/formations" class="text-primary hover:underline ml-1">Consultez le catalogue des formations.</a>
    </div>
  <?php else: ?>
    <div class="grid gap-4">
      <?php foreach ($enrollments as $e):
        [$statusLabel, $statusCls] = $statusLabels[$e['status']] ?? [$e['status'], 'bg-muted text-muted-foreground'];
        $hasSession = !empty($e['start_date']);
        $hasAccess  = in_array($e['status'], $accessStatuses, true);

        // Materials for this enrollment
        $matKey  = $e['program_id'] . '|' . ($e['session_id'] ?? '');
        $matKeyAll = $e['program_id'] . '|';
        $mats = array_merge($matsByProgram[$matKey] ?? [], $matsByProgram[$matKeyAll] ?? []);
        // Deduplicate
        $matsById = [];
        foreach ($mats as $m) $matsById[$m['id']] = $m;
        $mats = array_values($matsById);
      ?>
        <div class="rounded-xl border border-border bg-card overflow-hidden shadow-sm">
          <div class="flex flex-wrap items-start justify-between gap-2 px-5 py-4 border-b border-border">
            <div>
              <p class="font-semibold"><?= e($e['program_title'] ?? 'Formation') ?></p>
              <p class="text-xs text-muted-foreground">Dossier <?= e($e['enrollment_number'] ?? '—') ?></p>
            </div>
            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $statusCls ?>">
              <?= $statusLabel ?>
            </span>
          </div>

          <div class="px-5 py-4 space-y-4">
            <!-- Session info -->
            <div class="text-sm">
              <?php if ($hasSession): ?>
                <p>
                  Du <?= $fmtDate($e['start_date']) ?> au <?= $fmtDate($e['end_date']) ?>
                  <?php if ($e['start_time']): ?>
                    · <?= substr($e['start_time'], 0, 5) ?>–<?= substr($e['end_time'] ?? '', 0, 5) ?>
                  <?php endif ?>
                  <?php if ($e['location']): ?> · <?= e($e['location']) ?><?php endif ?>
                  <?php if ($e['room']): ?> (<?= e($e['room']) ?>)<?php endif ?>
                  <?php if ($e['modality']): ?>
                    · <span class="text-muted-foreground"><?= $modalityLabels[$e['modality']] ?? e($e['modality']) ?></span>
                  <?php endif ?>
                </p>
                <?php if ($hasAccess && !empty($e['meeting_url'])): ?>
                  <a href="<?= e($e['meeting_url']) ?>" target="_blank" rel="noopener noreferrer"
                     class="text-primary hover:underline text-sm">🔗 Lien de la classe en ligne</a>
                <?php endif ?>
              <?php else: ?>
                <p class="text-muted-foreground">Session à programmer — vous serez prévenu(e) par SMS.</p>
              <?php endif ?>
            </div>

            <!-- Actions -->
            <div class="flex flex-wrap gap-2">
              <?php if ($hasSession): ?>
                <a href="<?= $base ?>/app/espace-apprenant/<?= e($e['id']) ?>/ics"
                   class="app-btn-outline text-xs">📅 Ajouter au calendrier</a>
              <?php endif ?>
              <?php if ($e['status'] === 'terminee'): ?>
                <a href="<?= $base ?>/app/espace-apprenant/<?= e($e['id']) ?>/attestation"
                   target="_blank" class="app-btn-primary text-xs">🏆 Télécharger mon attestation</a>
              <?php endif ?>
            </div>

            <!-- Supports de cours -->
            <div>
              <p class="text-sm font-medium mb-2">Supports de cours</p>
              <?php if (!$hasAccess): ?>
                <p class="text-xs text-muted-foreground">Disponibles dès la confirmation de votre inscription.</p>
              <?php elseif (empty($mats)): ?>
                <p class="text-xs text-muted-foreground">Aucun support déposé pour l'instant.</p>
              <?php else: ?>
                <ul class="space-y-1">
                  <?php foreach ($mats as $m): ?>
                    <li>
                      <a href="<?= e($m['file_path']) ?>" target="_blank" rel="noopener"
                         class="inline-flex items-center gap-2 text-sm text-primary hover:underline">
                        <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                          <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <?= e($m['title']) ?>
                        <?php if ($m['file_name']): ?>
                          <span class="text-xs text-muted-foreground">(<?= e($m['file_name']) ?>)</span>
                        <?php endif ?>
                      </a>
                    </li>
                  <?php endforeach ?>
                </ul>
              <?php endif ?>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

</div>

<style>
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
  text-decoration: none;
}
.app-btn-outline:hover { background: var(--muted); }
</style>

<script>
function filterTrainerSessions(programId) {
  document.querySelectorAll('#trainer-session option').forEach(opt => {
    if (!opt.value) return; // keep "Toutes les sessions"
    opt.style.display = (!programId || opt.dataset.program === programId) ? '' : 'none';
  });
  // Reset selection to "Toutes" when program changes
  document.getElementById('trainer-session').value = '';
}
</script>
