<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$roleLabels = [
    'super_admin'             => 'Super Admin',
    'admin'                   => 'Admin',
    'comptable'               => 'Comptable',
    'responsable_formation'   => 'Resp. formation',
    'formateur'               => 'Formateur',
    'commercial'              => 'Commercial',
    'gestionnaire_immobilier' => 'Gest. immobilier',
    'client'                  => 'Client',
];
$roleBadgeCls = fn(string $r) => match($r) {
    'super_admin', 'admin' => 'bg-primary/10 text-primary',
    'client'               => 'bg-muted text-muted-foreground',
    default                => 'bg-secondary/15 text-secondary-foreground',
};

$canEdit  = $canEdit ?? false;
$currentId = $currentId ?? '';
$errors   = $errors ?? [];
$old      = $old ?? [];
?>
<div class="space-y-6">

  <header class="flex flex-wrap items-end justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Utilisateurs & rôles</h1>
      <p class="text-sm text-muted-foreground">Gérez les comptes, leurs permissions et l'accès à la plateforme.</p>
    </div>
    <div class="flex items-center gap-3">
      <span class="rounded-full bg-muted px-3 py-1 text-xs font-medium"><?= count($users) ?> compte(s)</span>
      <?php if ($canEdit): ?>
        <button onclick="document.getElementById('create-dialog').classList.remove('hidden')"
                class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
          Nouveau compte
        </button>
      <?php endif ?>
    </div>
  </header>

  <?php $flash = \App\Core\Session::flash('success'); if ($flash): ?>
    <div class="rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success"><?= e($flash) ?></div>
  <?php endif ?>
  <?php $err = \App\Core\Session::flash('error'); if ($err): ?>
    <div class="rounded-lg bg-destructive/10 border border-destructive/20 px-4 py-3 text-sm text-destructive"><?= e($err) ?></div>
  <?php endif ?>

  <?php if ($errors): ?>
    <div class="rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
      <?php foreach ($errors as $e): ?><p><?= e($e) ?></p><?php endforeach ?>
    </div>
  <?php endif ?>

  <!-- Search -->
  <div class="relative max-w-md">
    <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
    <input type="text" id="user-search" placeholder="Rechercher un nom, e-mail…"
           class="w-full rounded-lg border border-border bg-background pl-9 pr-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
           oninput="filterUsers(this.value)">
  </div>

  <?php if (empty($users)): ?>
    <div class="rounded-xl border border-border bg-card px-6 py-10 text-center text-sm text-muted-foreground">Aucun utilisateur trouvé.</div>
  <?php else: ?>
    <div id="users-list" class="grid gap-3">
      <?php foreach ($users as $u):
        $name = trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''));
        if (!$name) $name = $u['email'] ?? 'Sans nom';
        $isSelf   = $u['id'] === $currentId;
        $roles    = $u['roles'] ?? [];
        $isClient = empty($roles) || (count($roles) === 1 && $roles[0] === 'client');
        $canAct   = ($canEdit || $isClient) && !$isSelf;
      ?>
        <div class="user-row rounded-xl border border-border bg-card"
             data-search="<?= e(strtolower($name . ' ' . $u['email'] . ' ' . $u['phone'])) ?>">
          <div class="flex flex-wrap items-center justify-between gap-4 p-4">
            <div class="min-w-0">
              <p class="truncate font-medium">
                <?= e($name) ?>
                <?php if ($u['job_title'] ?? ''): ?><span class="ml-2 text-sm font-normal text-muted-foreground"><?= e($u['job_title']) ?></span><?php endif ?>
                <?php if ($isSelf): ?><span class="ml-2 rounded-full bg-primary/10 px-2 py-0.5 text-xs font-medium text-primary">Vous</span><?php endif ?>
              </p>
              <p class="truncate text-sm text-muted-foreground"><?= e($u['email'] ?? '—') ?> · <?= e($u['phone'] ?? '—') ?></p>
              <p class="text-xs text-muted-foreground">Inscrit le <?= Format::date($u['created_at']) ?></p>
              <div class="mt-2 flex flex-wrap gap-1">
                <?php if (empty($roles)): ?>
                  <span class="rounded-full bg-muted px-2 py-0.5 text-xs">Aucun rôle</span>
                <?php else: ?>
                  <?php foreach ($roles as $role): ?>
                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $roleBadgeCls($role) ?>">
                      <?= e($roleLabels[$role] ?? $role) ?>
                    </span>
                  <?php endforeach ?>
                <?php endif ?>
              </div>
            </div>
            <div class="flex items-center gap-3">
              <!-- Toggle active -->
              <form method="POST" action="<?= $base ?>/app/utilisateurs/<?= e($u['id']) ?>/toggle">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" <?= !$canAct ? 'disabled' : '' ?>
                        class="flex items-center gap-1.5 rounded-lg border border-border px-3 py-1.5 text-xs font-medium <?= $canAct ? 'hover:bg-muted' : 'opacity-40 cursor-not-allowed' ?>">
                  <span class="size-2 rounded-full <?= $u['is_active'] ? 'bg-success' : 'bg-destructive' ?>"></span>
                  <?= $u['is_active'] ? 'Actif' : 'Suspendu' ?>
                </button>
              </form>
              <?php if ($canEdit): ?>
                <button onclick="openEditDialog('<?= e(json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT)) ?>')"
                        class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted">
                  Modifier
                </button>
              <?php endif ?>
              <?php if ($canAct): ?>
                <form method="POST" action="<?= $base ?>/app/utilisateurs/<?= e($u['id']) ?>/supprimer"
                      onsubmit="return confirm('Supprimer définitivement ce compte ?')">
                  <?= \App\Core\Csrf::field() ?>
                  <button type="submit" class="rounded-md p-1.5 text-destructive hover:bg-destructive/10">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                  </button>
                </form>
              <?php endif ?>
            </div>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  <?php endif ?>

</div>

<?php if ($canEdit): ?>
<!-- Create dialog -->
<div id="create-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
  <div class="relative mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-border bg-card p-6 shadow-xl">
    <button onclick="document.getElementById('create-dialog').classList.add('hidden')"
            class="absolute right-4 top-4 rounded-md p-1 hover:bg-muted">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <h2 class="font-display text-lg font-bold mb-4">Nouveau compte utilisateur</h2>
    <form method="POST" action="<?= $base ?>/app/utilisateurs" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="grid gap-1">
          <label class="text-xs font-medium">Prénom</label>
          <input name="first_name" type="text" value="<?= e($old['firstName'] ?? '') ?>"
                 class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <div class="grid gap-1">
          <label class="text-xs font-medium">Nom</label>
          <input name="last_name" type="text" value="<?= e($old['lastName'] ?? '') ?>"
                 class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Titre / fonction</label>
        <input name="job_title" type="text" value="<?= e($old['jobTitle'] ?? '') ?>"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">E-mail <span class="text-destructive">*</span></label>
        <input name="email" type="email" required value="<?= e($old['email'] ?? '') ?>"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Téléphone</label>
        <input name="phone" type="text" value="<?= e($old['phone'] ?? '') ?>" placeholder="+224 6xx xx xx xx"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Mot de passe provisoire <span class="text-destructive">*</span></label>
        <input name="password" type="text"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <p class="mb-2 text-xs font-medium">Rôles <span class="text-destructive">*</span></p>
        <div class="grid gap-1.5 max-h-48 overflow-y-auto">
          <?php foreach ($allRoles as $role): ?>
            <label class="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2 cursor-pointer hover:bg-muted">
              <span class="text-sm"><?= e($roleLabels[$role] ?? $role) ?></span>
              <input type="checkbox" name="roles[]" value="<?= e($role) ?>"
                     <?= in_array($role, $old['roles'] ?? ['client'], true) ? 'checked' : '' ?>
                     class="size-4 rounded">
            </label>
          <?php endforeach ?>
        </div>
      </div>
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('create-dialog').classList.add('hidden')"
                class="flex-1 rounded-lg border border-border py-2 text-sm font-medium hover:bg-muted">Annuler</button>
        <button type="submit" class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">Créer</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit dialog -->
<div id="edit-dialog" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50">
  <div class="relative mx-4 max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl border border-border bg-card p-6 shadow-xl">
    <button onclick="document.getElementById('edit-dialog').classList.add('hidden')"
            class="absolute right-4 top-4 rounded-md p-1 hover:bg-muted">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
    </button>
    <h2 class="font-display text-lg font-bold mb-1">Modifier le compte</h2>
    <p id="edit-subtitle" class="text-sm text-muted-foreground mb-4"></p>
    <form id="edit-form" method="POST" class="space-y-3">
      <?= \App\Core\Csrf::field() ?>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="grid gap-1">
          <label class="text-xs font-medium">Prénom</label>
          <input id="edit-first-name" name="first_name" type="text"
                 class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
        <div class="grid gap-1">
          <label class="text-xs font-medium">Nom</label>
          <input id="edit-last-name" name="last_name" type="text"
                 class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
        </div>
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Titre / fonction</label>
        <input id="edit-job-title" name="job_title" type="text"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Téléphone</label>
        <input id="edit-phone" name="phone" type="text"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">E-mail (identifiant de connexion)</label>
        <input id="edit-email" name="email" type="email"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div class="grid gap-1">
        <label class="text-xs font-medium">Nouveau mot de passe (laisser vide = inchangé)</label>
        <input name="password" type="text"
               class="rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
      </div>
      <div>
        <p class="mb-2 text-xs font-medium">Rôles</p>
        <div id="edit-roles" class="grid gap-1.5 max-h-48 overflow-y-auto">
          <?php foreach ($allRoles as $role): ?>
            <label class="flex items-center justify-between gap-3 rounded-lg border border-border px-3 py-2 cursor-pointer hover:bg-muted">
              <span class="text-sm"><?= e($roleLabels[$role] ?? $role) ?></span>
              <input type="checkbox" name="roles[]" value="<?= e($role) ?>"
                     data-role="<?= e($role) ?>" class="size-4 rounded edit-role-check">
            </label>
          <?php endforeach ?>
        </div>
      </div>
      <div class="flex gap-2 pt-2">
        <button type="button" onclick="document.getElementById('edit-dialog').classList.add('hidden')"
                class="flex-1 rounded-lg border border-border py-2 text-sm font-medium hover:bg-muted">Annuler</button>
        <button type="submit" class="flex-1 rounded-lg bg-primary py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">Enregistrer</button>
      </div>
    </form>
  </div>
</div>
<?php endif ?>

<script>
function filterUsers(term) {
  const t = term.toLowerCase();
  document.querySelectorAll('.user-row').forEach(row => {
    row.style.display = row.dataset.search.includes(t) ? '' : 'none';
  });
}

function openEditDialog(jsonStr) {
  const u = JSON.parse(jsonStr);
  const base = '<?= $base ?>';
  document.getElementById('edit-form').action = base + '/app/utilisateurs/' + u.id + '/role';
  document.getElementById('edit-subtitle').textContent = u.email || '';
  document.getElementById('edit-first-name').value = u.first_name || '';
  document.getElementById('edit-last-name').value = u.last_name || '';
  document.getElementById('edit-job-title').value = u.job_title || '';
  document.getElementById('edit-phone').value = u.phone || '';
  document.getElementById('edit-email').value = u.email || '';
  const roles = u.roles || [];
  document.querySelectorAll('.edit-role-check').forEach(cb => {
    cb.checked = roles.includes(cb.dataset.role);
  });
  document.getElementById('edit-dialog').classList.remove('hidden');
}
</script>
