<?php
use function App\Core\e;
use App\Helpers\Format;

$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$statuses = [
    'a_faire'    => 'À faire',
    'en_cours'   => 'En cours',
    'en_attente' => 'En attente',
    'terminee'   => 'Terminée',
    'annulee'    => 'Annulée',
];
$priorities = [
    'basse'   => ['label' => 'Basse',   'cls' => 'border border-border text-muted-foreground'],
    'normale' => ['label' => 'Normale', 'cls' => 'bg-muted text-muted-foreground'],
    'haute'   => ['label' => 'Haute',   'cls' => 'bg-primary/10 text-primary'],
    'urgente' => ['label' => 'Urgente', 'cls' => 'bg-destructive/10 text-destructive'],
];
$modules = ['Formations', 'Inscriptions', 'Finances', 'Clients', 'Administration'];

$tasks      = $tasks      ?? [];
$stats      = $stats      ?? [];
$staffList  = $staffList  ?? [];
$statusFilter = $statusFilter ?? 'ouvertes';
$mineOnly   = $mineOnly   ?? false;
$userId     = $userId     ?? '';
$today      = date('Y-m-d');

$staffMap = [];
foreach ($staffList as $s) $staffMap[$s['id']] = $s['name'];
?>

<div class="space-y-6">

  <?php if (!empty($success)): ?>
    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800"><?= e($success) ?></div>
  <?php endif ?>
  <?php if (!empty($error)): ?>
    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800"><?= e($error) ?></div>
  <?php endif ?>

  <!-- Header -->
  <header class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Tâches internes</h1>
      <p class="text-sm text-muted-foreground">Organisez le travail de l'équipe : priorités, échéances et responsables.</p>
    </div>
    <button onclick="document.getElementById('dlg-task-new').showModal()"
            class="inline-flex items-center gap-2 rounded-lg bg-primary px-3.5 py-2 text-sm font-medium text-primary-foreground hover:bg-primary/90">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
      </svg>
      Nouvelle tâche
    </button>
  </header>

  <!-- KPIs -->
  <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
    <?php foreach ([
      ['label' => 'Tâches ouvertes',          'value' => $stats['ouvertes'] ?? 0],
      ['label' => 'Urgentes',                  'value' => $stats['urgentes'] ?? 0],
      ['label' => 'En retard',                 'value' => $stats['retard']   ?? 0],
      ['label' => 'Qui me sont assignées',     'value' => $stats['miennes']  ?? 0],
    ] as $kpi): ?>
      <div class="rounded-xl border border-border bg-card p-4">
        <p class="text-xs uppercase tracking-wide text-muted-foreground"><?= e($kpi['label']) ?></p>
        <p class="font-display text-2xl font-bold"><?= $kpi['value'] ?></p>
      </div>
    <?php endforeach ?>
  </div>

  <!-- Filters -->
  <form method="GET" action="<?= $base ?>/app/taches" class="flex flex-wrap items-center gap-3">
    <select name="status" onchange="this.form.submit()"
            class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-1 focus:ring-primary">
      <option value="ouvertes" <?= $statusFilter === 'ouvertes' ? 'selected' : '' ?>>Tâches ouvertes</option>
      <option value="toutes"   <?= $statusFilter === 'toutes'   ? 'selected' : '' ?>>Toutes les tâches</option>
      <?php foreach ($statuses as $val => $lbl): ?>
        <option value="<?= e($val) ?>" <?= $statusFilter === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
      <?php endforeach ?>
    </select>
    <a href="<?= $base ?>/app/taches?status=<?= urlencode($statusFilter) ?>&mine=<?= $mineOnly ? 0 : 1 ?>"
       class="rounded-lg border <?= $mineOnly ? 'bg-primary text-primary-foreground border-primary' : 'border-border bg-background' ?> px-3 py-2 text-sm font-medium hover:bg-muted">
      Mes tâches
    </a>
  </form>

  <!-- Task list -->
  <?php if (empty($tasks)): ?>
    <div class="rounded-xl border border-border bg-card px-5 py-8 text-center text-sm text-muted-foreground">
      Aucune tâche ne correspond à ce filtre.
    </div>
  <?php else: ?>
    <div class="grid gap-3">
      <?php foreach ($tasks as $task): ?>
        <?php
        $late   = $task['due_date'] && substr($task['due_date'], 0, 10) < $today && !in_array($task['status'], ['terminee', 'annulee'], true);
        $pr     = $priorities[$task['priority']] ?? $priorities['normale'];
        $stLbl  = $statuses[$task['status']] ?? $task['status'];
        $asName = $task['assigned_to'] ? ($staffMap[$task['assigned_to']] ?? trim($task['assigned_name'] ?? '')) : 'Personne';
        if (!$asName) $asName = 'Membre';
        ?>
        <div class="rounded-xl border border-border bg-card">
          <div class="flex flex-wrap items-start justify-between gap-4 p-4">
            <div class="min-w-0 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <p class="font-medium"><?= e($task['title']) ?></p>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $pr['cls'] ?>"><?= $pr['label'] ?></span>
                <span class="inline-flex items-center rounded-full border border-border px-2 py-0.5 text-xs"><?= e($stLbl) ?></span>
                <?php if ($task['module']): ?>
                  <span class="inline-flex items-center rounded-full bg-muted px-2 py-0.5 text-xs"><?= e($task['module']) ?></span>
                <?php endif ?>
              </div>
              <?php if ($task['description']): ?>
                <p class="max-w-2xl text-sm text-muted-foreground"><?= e($task['description']) ?></p>
              <?php endif ?>
              <p class="text-xs <?= $late ? 'font-medium text-destructive' : 'text-muted-foreground' ?>">
                Échéance : <?= $task['due_date'] ? e(Format::date(substr($task['due_date'],0,10))) : 'non définie' ?><?= $late ? ' · en retard' : '' ?>
                · Assignée à <?= e($asName) ?>
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
              <!-- Quick status change -->
              <form method="POST" action="<?= $base ?>/app/taches/<?= e($task['id']) ?>">
                <?= \App\Core\Csrf::field() ?>
                <select name="status" onchange="this.form.submit()"
                        class="rounded-lg border border-border bg-background px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-primary">
                  <?php foreach ($statuses as $val => $lbl): ?>
                    <option value="<?= e($val) ?>" <?= $task['status'] === $val ? 'selected' : '' ?>><?= e($lbl) ?></option>
                  <?php endforeach ?>
                </select>
              </form>
              <?php if ($task['status'] !== 'terminee'): ?>
                <form method="POST" action="<?= $base ?>/app/taches/<?= e($task['id']) ?>">
                  <?= \App\Core\Csrf::field() ?>
                  <input type="hidden" name="status" value="terminee">
                  <button type="submit"
                          class="inline-flex items-center gap-1.5 rounded-lg border border-border px-2.5 py-1.5 text-xs font-medium hover:bg-muted">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    Terminer
                  </button>
                </form>
              <?php endif ?>
              <button onclick="openEditTask(<?= htmlspecialchars(json_encode($task), ENT_QUOTES) ?>)"
                      class="rounded-lg border border-border px-2.5 py-1.5 text-xs font-medium hover:bg-muted">
                Modifier
              </button>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>
</div>

<!-- Dialog Nouvelle tâche -->
<dialog id="dlg-task-new" style="border-radius:1rem;border:1px solid var(--border);background:var(--card);color:var(--foreground);padding:1.5rem;width:min(560px,95vw);box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;">
  <dialog-header class="flex items-center justify-between mb-4">
    <h3 class="font-display text-base font-bold">Nouvelle tâche</h3>
    <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
  </dialog-header>
  <?php include __DIR__ . '/form.php'; ?>
</dialog>

<!-- Dialog Modifier tâche -->
<dialog id="dlg-task-edit" style="border-radius:1rem;border:1px solid var(--border);background:var(--card);color:var(--foreground);padding:1.5rem;width:min(560px,95vw);box-shadow:0 20px 60px rgba(0,0,0,.2);margin:auto;">
  <div class="flex items-center justify-between mb-4">
    <h3 class="font-display text-base font-bold">Modifier la tâche</h3>
    <button type="button" onclick="this.closest('dialog').close()" class="text-muted-foreground hover:text-foreground">✕</button>
  </div>
  <form method="POST" id="form-edit-task" class="space-y-4">
    <?= \App\Core\Csrf::field() ?>
    <div class="grid gap-4 sm:grid-cols-2">
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Titre *</label>
        <input type="text" name="title" id="edit-title" required class="app-input w-full">
      </div>
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Description</label>
        <textarea name="description" id="edit-description" rows="2" class="app-input w-full"></textarea>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Priorité</label>
        <select name="priority" id="edit-priority" class="app-input w-full">
          <?php foreach (['basse'=>'Basse','normale'=>'Normale','haute'=>'Haute','urgente'=>'Urgente'] as $v=>$l): ?>
            <option value="<?= $v ?>"><?= $l ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Statut</label>
        <select name="status" id="edit-status" class="app-input w-full">
          <?php foreach ($statuses as $v=>$l): ?>
            <option value="<?= $v ?>"><?= $l ?></option>
          <?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Échéance</label>
        <input type="date" name="due_date" id="edit-due" class="app-input w-full">
      </div>
      <div class="space-y-1">
        <label class="text-sm font-medium">Module</label>
        <select name="module" id="edit-module" class="app-input w-full">
          <option value="">Non précisé</option>
          <?php foreach ($modules as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach ?>
        </select>
      </div>
      <div class="space-y-1 sm:col-span-2">
        <label class="text-sm font-medium">Assignée à</label>
        <select name="assigned_to" id="edit-assigned" class="app-input w-full">
          <option value="">Personne</option>
          <?php foreach ($staffList as $s): ?>
            <option value="<?= e($s['id']) ?>"><?= e($s['name']) ?></option>
          <?php endforeach ?>
        </select>
      </div>
    </div>
    <div class="flex justify-end gap-2 pt-2">
      <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
      <button type="submit" class="app-btn-primary">Enregistrer</button>
    </div>
  </form>
</dialog>

<script>
function openEditTask(task) {
  const base = <?= json_encode(rtrim(\App\Core\env('APP_URL', ''), '/')) ?>;
  document.getElementById('form-edit-task').action = base + '/app/taches/' + task.id;
  document.getElementById('edit-title').value        = task.title || '';
  document.getElementById('edit-description').value  = task.description || '';
  document.getElementById('edit-priority').value     = task.priority || 'normale';
  document.getElementById('edit-status').value       = task.status || 'a_faire';
  document.getElementById('edit-due').value          = task.due_date ? task.due_date.substring(0,10) : '';
  document.getElementById('edit-module').value       = task.module || '';
  document.getElementById('edit-assigned').value     = task.assigned_to || '';
  document.getElementById('dlg-task-edit').showModal();
}
</script>
