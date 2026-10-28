<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$user = $user ?? [];
$roleLabels = [
    'super_admin'             => 'Super Admin',
    'admin'                   => 'Administrateur',
    'comptable'               => 'Comptable',
    'responsable_formation'   => 'Responsable formation',
    'formateur'               => 'Formateur',
    'commercial'              => 'Commercial',
    'client'                  => 'Apprenant',
    'gestionnaire_immobilier' => 'Gestionnaire immobilier',
];
$role        = $user['role'] ?? 'client';
$roleLabel   = $roleLabels[$role] ?? ucfirst($role);
$displayName = trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
if ($displayName === '') $displayName = $user['email'] ?? 'Mon compte';
?>
<div class="mx-auto max-w-2xl space-y-6">

  <!-- En-tête -->
  <header class="flex items-center gap-4">
    <span class="flex size-14 items-center justify-center rounded-full bg-primary/15 text-xl font-bold text-primary">
      <?= strtoupper(mb_substr($displayName, 0, 1)) ?>
    </span>
    <div>
      <h1 class="font-display text-2xl font-bold"><?= e($displayName) ?></h1>
      <p class="text-sm text-muted-foreground">
        <?php if ($user['job_title'] ?? ''): ?>
          <?= e($user['job_title']) ?> ·
        <?php endif ?>
        <?= e($roleLabel) ?>
      </p>
    </div>
  </header>

  <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
    <?= e($roleLabel) ?>
  </span>

  <!-- Informations personnelles -->
  <div class="rounded-xl border border-border bg-card">
    <div class="border-b border-border px-5 py-4">
      <h2 class="text-base font-semibold">Informations personnelles</h2>
      <p class="text-sm text-muted-foreground">Ces informations apparaissent dans votre compte et vos dossiers.</p>
    </div>
    <form method="POST" action="<?= $base ?>/app/profil" class="space-y-4 p-5">
      <?= \App\Core\Csrf::field() ?>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label for="first_name" class="mb-1.5 block text-sm font-medium">Prénom</label>
          <input id="first_name" type="text" name="first_name" autocomplete="given-name"
                 value="<?= e($user['first_name'] ?? '') ?>"
                 class="app-input">
        </div>
        <div>
          <label for="last_name" class="mb-1.5 block text-sm font-medium">Nom</label>
          <input id="last_name" type="text" name="last_name" autocomplete="family-name"
                 value="<?= e($user['last_name'] ?? '') ?>"
                 class="app-input">
        </div>
      </div>
      <div>
        <label for="job_title" class="mb-1.5 block text-sm font-medium">Titre / Fonction</label>
        <input id="job_title" type="text" name="job_title" placeholder="Directeur, Comptable principal…"
               value="<?= e($user['job_title'] ?? '') ?>"
               class="app-input">
      </div>
      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label for="phone" class="mb-1.5 block text-sm font-medium">Téléphone</label>
          <input id="phone" type="tel" name="phone" autocomplete="tel"
                 value="<?= e($user['phone'] ?? '') ?>"
                 class="app-input">
        </div>
        <div>
          <label for="city" class="mb-1.5 block text-sm font-medium">Ville</label>
          <input id="city" type="text" name="city" placeholder="Conakry, Kankan…"
                 value="<?= e($user['city'] ?? '') ?>"
                 class="app-input">
        </div>
      </div>
      <p class="text-xs text-muted-foreground">Compte : <?= e($user['email'] ?? '—') ?></p>
      <button type="submit" class="app-btn-primary">Enregistrer les modifications</button>
    </form>
  </div>

  <!-- Mot de passe -->
  <div class="rounded-xl border border-border bg-card">
    <div class="border-b border-border px-5 py-4">
      <h2 class="text-base font-semibold">Mot de passe</h2>
      <p class="text-sm text-muted-foreground">Choisissez un mot de passe d'au moins 8 caractères.</p>
    </div>
    <form method="POST" action="<?= $base ?>/app/profil/password" class="space-y-4 p-5">
      <?= \App\Core\Csrf::field() ?>
      <div>
        <label for="password" class="mb-1.5 block text-sm font-medium">Nouveau mot de passe</label>
        <input id="password" type="password" name="password" autocomplete="new-password"
               class="app-input" minlength="8" required>
      </div>
      <button type="submit" class="app-btn-secondary">Modifier le mot de passe</button>
    </form>
  </div>

</div>

<style>
.app-input {
  display: block; width: 100%;
  border-radius: .5rem; border: 1px solid var(--border);
  background: var(--background);
  padding: .5rem .75rem; font-size: .875rem; color: var(--foreground);
  outline: none; transition: border-color .15s, box-shadow .15s;
}
.app-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--primary) 15%, transparent);
}
.app-btn-primary {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; background: var(--primary); color: var(--primary-foreground);
  padding: .5rem 1.25rem; font-size: .875rem; font-weight: 600;
  cursor: pointer; border: none; transition: opacity .15s;
}
.app-btn-primary:hover { opacity: .88; }
.app-btn-secondary {
  display: inline-flex; align-items: center; justify-content: center;
  border-radius: .5rem; border: 1px solid var(--border);
  background: var(--muted); color: var(--foreground);
  padding: .5rem 1.25rem; font-size: .875rem; font-weight: 600;
  cursor: pointer; transition: background .15s;
}
.app-btn-secondary:hover { background: var(--muted-foreground)/10; }
</style>
