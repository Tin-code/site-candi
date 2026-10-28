<?php
// Partial form used in "Nouvelle tâche" dialog
$base      = $base ?? rtrim(\App\Core\env('APP_URL', ''), '/');
$statuses  = $statuses  ?? [];
$staffList = $staffList ?? [];
$modules   = $modules   ?? ['Formations', 'Inscriptions', 'Finances', 'Clients', 'Administration'];
?>
<form method="POST" action="<?= $base ?>/app/taches" class="space-y-4">
  <?= \App\Core\Csrf::field() ?>
  <div class="grid gap-4 sm:grid-cols-2">
    <div class="space-y-1 sm:col-span-2">
      <label class="text-sm font-medium">Titre *</label>
      <input type="text" name="title" required class="app-input w-full" placeholder="Titre de la tâche">
    </div>
    <div class="space-y-1 sm:col-span-2">
      <label class="text-sm font-medium">Description</label>
      <textarea name="description" rows="2" class="app-input w-full" placeholder="Détails optionnels"></textarea>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Priorité</label>
      <select name="priority" class="app-input w-full">
        <option value="basse">Basse</option>
        <option value="normale" selected>Normale</option>
        <option value="haute">Haute</option>
        <option value="urgente">Urgente</option>
      </select>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Statut</label>
      <select name="status" class="app-input w-full">
        <option value="a_faire" selected>À faire</option>
        <option value="en_cours">En cours</option>
        <option value="en_attente">En attente</option>
        <option value="terminee">Terminée</option>
        <option value="annulee">Annulée</option>
      </select>
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Échéance</label>
      <input type="date" name="due_date" class="app-input w-full">
    </div>
    <div class="space-y-1">
      <label class="text-sm font-medium">Module</label>
      <select name="module" class="app-input w-full">
        <option value="">Non précisé</option>
        <?php foreach ($modules as $m): ?><option value="<?= $m ?>"><?= $m ?></option><?php endforeach ?>
      </select>
    </div>
    <div class="space-y-1 sm:col-span-2">
      <label class="text-sm font-medium">Assignée à</label>
      <select name="assigned_to" class="app-input w-full">
        <option value="">Personne</option>
        <?php foreach ($staffList as $s): ?>
          <option value="<?= \App\Core\e($s['id']) ?>"><?= \App\Core\e($s['name']) ?></option>
        <?php endforeach ?>
      </select>
    </div>
  </div>
  <div class="flex justify-end gap-2 pt-2">
    <button type="button" onclick="this.closest('dialog').close()" class="app-btn-outline">Annuler</button>
    <button type="submit" class="app-btn-primary">Créer la tâche</button>
  </div>
</form>
