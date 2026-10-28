<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$statusLabels = [
    'brouillon'            => 'Brouillon',
    'nouvelle_demande'     => 'Nouvelle demande',
    'a_contacter'          => 'À contacter',
    'contacte'             => 'Contacté',
    'en_attente_documents' => 'En att. documents',
    'en_attente_paiement'  => 'En att. paiement',
    'paiement_partiel'     => 'Paiement partiel',
    'confirmee'            => 'Confirmée',
    'en_cours'             => 'En cours',
    'terminee'             => 'Terminée',
    'abandonnee'           => 'Abandonnée',
    'annulee'              => 'Annulée',
    'refusee'              => 'Refusée',
    'remboursee'           => 'Remboursée',
];

function iBadge(string $s, array $l): string {
    $lbl = $l[$s] ?? $s;
    $cls = match(true) {
        in_array($s, ['confirmee','en_cours'], true)           => 'bg-success/15 text-success',
        in_array($s, ['terminee'], true)                       => 'bg-muted text-muted-foreground',
        in_array($s, ['annulee','refusee','abandonnee'], true) => 'bg-destructive/10 text-destructive',
        default                                                => 'bg-primary/10 text-primary',
    };
    return '<span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium '.$cls.'">'.e($lbl).'</span>';
}
?>

<div class="space-y-4">
  <div class="flex flex-wrap items-center justify-between gap-3">
    <h1 class="font-display text-xl font-bold">
      <?= ($isStaff ?? false) ? 'Inscriptions' : 'Mes inscriptions' ?>
    </h1>
    <?php if ($isStaff ?? false): ?>
      <a href="<?= $base ?>/app/inscriptions/creer" class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Nouvelle inscription
      </a>
    <?php endif ?>
  </div>

  <?php if ($isStaff ?? false): ?>
    <!-- Filtres staff -->
    <form method="GET" action="<?= $base ?>/app/inscriptions" class="flex flex-wrap gap-3">
      <input type="text" name="q" value="<?= e($search ?? '') ?>"
             placeholder="Rechercher…" class="app-input max-w-xs">
      <select name="status" class="app-input w-auto">
        <option value="">Tous les statuts</option>
        <?php foreach ($statuses ?? [] as $s): ?>
          <option value="<?= e($s) ?>" <?= ($status ?? '') === $s ? 'selected' : '' ?>>
            <?= e($statusLabels[$s] ?? $s) ?>
          </option>
        <?php endforeach ?>
      </select>
      <button type="submit" class="app-btn-primary">Filtrer</button>
    </form>
  <?php endif ?>

  <!-- Tableau -->
  <div class="overflow-x-auto rounded-xl border border-border bg-card">
    <table class="w-full text-sm">
      <thead class="border-b border-border bg-muted/50">
        <tr>
          <th class="px-4 py-3 text-left font-medium text-muted-foreground">N° / Formation</th>
          <?php if ($isStaff ?? false): ?>
            <th class="px-4 py-3 text-left font-medium text-muted-foreground">Apprenant</th>
          <?php endif ?>
          <th class="px-4 py-3 text-left font-medium text-muted-foreground">Statut</th>
          <th class="px-4 py-3 text-right font-medium text-muted-foreground">Montant</th>
          <th class="px-4 py-3 text-left font-medium text-muted-foreground">Date</th>
          <th class="px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-border">
        <?php if (empty($enrollments)): ?>
          <tr>
            <td colspan="<?= ($isStaff ?? false) ? 6 : 5 ?>" class="px-4 py-8 text-center text-muted-foreground">
              <?= ($isStaff ?? false) ? 'Aucune inscription trouvée.' : 'Vous n\'avez encore aucune inscription.' ?>
            </td>
          </tr>
          <?php if (!($isStaff ?? false)): ?>
            <tr>
              <td colspan="5" class="pb-6 text-center">
                <a href="<?= $base ?>/formations" class="text-sm font-medium text-primary hover:underline">
                  Voir le catalogue des formations →
                </a>
              </td>
            </tr>
          <?php endif ?>
        <?php else: ?>
          <?php foreach ($enrollments as $e): ?>
            <tr class="hover:bg-muted/30 transition-colors">
              <td class="px-4 py-3">
                <p class="font-medium"><?= e($e['program_title'] ?? 'Formation') ?></p>
                <p class="text-xs text-muted-foreground"><?= e($e['enrollment_number'] ?? '—') ?></p>
              </td>
              <?php if ($isStaff ?? false): ?>
                <td class="px-4 py-3 text-muted-foreground">
                  <?= e(trim(($e['first_name'] ?? '') . ' ' . ($e['last_name'] ?? ''))) ?: '—' ?>
                </td>
              <?php endif ?>
              <td class="px-4 py-3"><?= iBadge($e['status'], $statusLabels) ?></td>
              <td class="px-4 py-3 text-right">
                <?= Format::money((float) $e['paid_amount']) ?>
                <span class="text-muted-foreground">/</span>
                <?= Format::money((float) $e['agreed_amount']) ?>
              </td>
              <td class="px-4 py-3 text-xs text-muted-foreground"><?= Format::date($e['created_at']) ?></td>
              <td class="px-4 py-3 text-right">
                <a href="<?= $base ?>/app/inscriptions/<?= e($e['id']) ?>"
                   class="text-xs font-medium text-primary hover:underline">Voir</a>
              </td>
            </tr>
          <?php endforeach ?>
        <?php endif ?>
      </tbody>
    </table>
  </div>

  <?php if ($isStaff ?? false): ?>
    <!-- Pagination -->
    <?php $p = $pagination ?? []; ?>
    <?php if (($p['last_page'] ?? 1) > 1): ?>
      <div class="flex items-center justify-between text-sm">
        <span class="text-muted-foreground">
          <?= $p['total'] ?> résultats · page <?= $p['page'] ?>/<?= $p['last_page'] ?>
        </span>
        <div class="flex gap-2">
          <?php if ($p['page'] > 1): ?>
            <a href="?q=<?= urlencode($search ?? '') ?>&status=<?= urlencode($status ?? '') ?>&page=<?= $p['page'] - 1 ?>"
               class="rounded-md border border-border px-3 py-1 hover:bg-muted">← Précédent</a>
          <?php endif ?>
          <?php if ($p['page'] < $p['last_page']): ?>
            <a href="?q=<?= urlencode($search ?? '') ?>&status=<?= urlencode($status ?? '') ?>&page=<?= $p['page'] + 1 ?>"
               class="rounded-md border border-border px-3 py-1 hover:bg-muted">Suivant →</a>
          <?php endif ?>
        </div>
      </div>
    <?php endif ?>
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
  padding: .4375rem 1rem; font-size: .875rem; font-weight: 600;
  cursor: pointer; border: none; transition: opacity .15s;
}
.app-btn-primary:hover { opacity: .88; }
</style>
