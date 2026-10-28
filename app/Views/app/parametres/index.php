<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$cp   = $cinetpay ?? [];
$nb   = $nimba ?? [];
?>
<div class="space-y-8">

  <header>
    <h1 class="flex items-center gap-2 font-display text-2xl font-bold">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 010 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 010-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
      Paramétrage de la plateforme
    </h1>
    <p class="text-sm text-muted-foreground">Listes de choix, passerelles de paiement et configuration générale.</p>
  </header>

  <?php $flash = \App\Core\Session::flash('success'); if ($flash): ?>
    <div class="rounded-lg bg-success/10 border border-success/20 px-4 py-3 text-sm text-success"><?= e($flash) ?></div>
  <?php endif ?>

  <!-- CinetPay (super_admin only) -->
  <?php if ($isSuperAdmin ?? false): ?>
    <section>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Passerelle de paiement CinetPay</h2>
      <div class="rounded-xl border border-border bg-card p-5">
        <form method="POST" action="<?= $base ?>/app/parametres" class="space-y-4">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="_action" value="cinetpay">

          <div class="flex items-center justify-between rounded-lg border border-border p-3">
            <div>
              <p class="text-sm font-medium">Activer CinetPay</p>
              <p class="text-xs text-muted-foreground">Permet aux apprenants de payer en ligne.</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
              <input type="checkbox" name="is_enabled" value="1" <?= ($cp['is_enabled'] ?? 0) ? 'checked' : '' ?> class="peer sr-only">
              <div class="h-5 w-9 rounded-full bg-border peer-checked:bg-primary after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4"></div>
            </label>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Site ID (client_id)</label>
              <input name="client_id" type="text" value="<?= e($cp['client_id'] ?? '') ?>"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">API Key (client_secret)</label>
              <input name="client_secret" type="text" value="<?= e($cp['client_secret'] ?? '') ?>"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Devise</label>
              <input name="currency" type="text" value="<?= e($cp['currency'] ?? 'GNF') ?>" placeholder="GNF"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Environnement</label>
              <select name="environment" class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="sandbox" <?= ($cp['environment'] ?? 'sandbox') === 'sandbox' ? 'selected' : '' ?>>Sandbox (test)</option>
                <option value="production" <?= ($cp['environment'] ?? '') === 'production' ? 'selected' : '' ?>>Production</option>
              </select>
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">Sauvegarder CinetPay</button>
          </div>
        </form>
      </div>
    </section>
  <?php endif ?>

  <!-- Nimba SMS (super_admin only) -->
  <?php if ($isSuperAdmin ?? false): ?>
    <?php
      $nbHasSid    = !empty($nb['client_id']);
      $nbHasSecret = !empty($nb['client_secret']);
    ?>
    <section>
      <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Passerelle SMS — Nimba</h2>
      <div class="rounded-xl border border-border bg-card p-5 space-y-6">

        <!-- Config form -->
        <form method="POST" action="<?= $base ?>/app/parametres" class="space-y-4">
          <?= \App\Core\Csrf::field() ?>
          <input type="hidden" name="_action" value="nimba_sms">

          <div class="flex items-center justify-between rounded-lg border border-border p-3">
            <div>
              <p class="text-sm font-medium">Activer Nimba SMS</p>
              <p class="text-xs text-muted-foreground">Envoi des codes OTP par SMS aux apprenants.</p>
            </div>
            <label class="relative inline-flex cursor-pointer items-center">
              <input type="checkbox" name="is_enabled" value="1" <?= ($nb['is_enabled'] ?? 0) ? 'checked' : '' ?> class="peer sr-only">
              <div class="h-5 w-9 rounded-full bg-border peer-checked:bg-primary after:absolute after:left-0.5 after:top-0.5 after:size-4 after:rounded-full after:bg-white after:transition-all peer-checked:after:translate-x-4"></div>
            </label>
          </div>

          <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Service ID <span class="text-muted-foreground">(SID)</span></label>
              <input name="sid" type="password" autocomplete="new-password"
                     value="" placeholder="<?= $nbHasSid ? '••••' . substr($nb['client_id'], -4) : 'Votre Service ID Nimba…' ?>"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
              <?php if ($nbHasSid): ?>
                <p class="text-xs text-success">Configuré — laisser vide pour ne pas modifier.</p>
              <?php endif ?>
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Secret Token</label>
              <input name="token_nimba" type="password" autocomplete="new-password"
                     value="" placeholder="<?= $nbHasSecret ? '••••' . substr($nb['client_secret'], -4) : 'Votre Secret Token…' ?>"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
              <?php if ($nbHasSecret): ?>
                <p class="text-xs text-success">Configuré — laisser vide pour ne pas modifier.</p>
              <?php endif ?>
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Nom d'expéditeur <span class="text-muted-foreground">(11 car. max)</span></label>
              <input name="sender" type="text" maxlength="11"
                     value="<?= e($nb['merchant_key'] ?? 'CaNDI') ?>" placeholder="CaNDI"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">URL de l'API <span class="text-muted-foreground">(optionnel)</span></label>
              <input name="api_base_url" type="url"
                     value="<?= e($nb['api_base_url'] ?? 'https://api.nimbasms.com') ?>"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div class="grid gap-1.5 sm:col-span-2">
              <label class="text-xs font-medium">Authorization Header <span class="text-muted-foreground">(optionnel — remplace SID + Secret)</span></label>
              <input name="auth_header" type="password" autocomplete="new-password"
                     placeholder="Basic base64(sid:secret)"
                     class="rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
              <p class="text-xs text-muted-foreground">Si renseigné et que SID/Token sont vides, sera décodé automatiquement.</p>
            </div>
          </div>

          <div class="flex justify-end">
            <button type="submit" class="rounded-lg bg-primary px-5 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90">
              Sauvegarder Nimba SMS
            </button>
          </div>
        </form>

        <!-- Test SMS -->
        <div class="border-t border-border pt-4">
          <p class="mb-3 text-sm font-medium">Envoyer un SMS de test</p>
          <?php $smsError = \App\Core\Session::flash('sms_error'); $smsOk = \App\Core\Session::flash('sms_ok'); ?>
          <?php if ($smsError): ?>
            <div class="mb-3 rounded-lg bg-destructive/10 border border-destructive/20 px-4 py-2 text-sm text-destructive"><?= e($smsError) ?></div>
          <?php elseif ($smsOk): ?>
            <div class="mb-3 rounded-lg bg-success/10 border border-success/20 px-4 py-2 text-sm text-success"><?= e($smsOk) ?></div>
          <?php endif ?>
          <form method="POST" action="<?= $base ?>/app/parametres" class="flex flex-wrap items-end gap-3">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="_action" value="test_sms">
            <div class="grid gap-1.5">
              <label class="text-xs font-medium">Numéro de téléphone</label>
              <input name="test_phone" type="tel" placeholder="ex. 626000000"
                     class="w-48 rounded-lg border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <button type="submit"
                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium hover:bg-muted transition-colors flex items-center gap-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"/></svg>
              Envoyer un SMS de test
            </button>
          </form>
        </div>

      </div>
    </section>
  <?php endif ?>

  <!-- ── Comptes & catégories financières ── -->
  <section id="finances">
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Finances — Comptes &amp; catégories</h2>
    <div class="grid gap-4 md:grid-cols-2">

      <!-- Comptes financiers -->
      <div class="rounded-xl border border-border bg-card">
        <div class="border-b border-border px-5 py-3">
          <h3 class="text-sm font-semibold">Comptes financiers</h3>
          <p class="text-xs text-muted-foreground">Caisses, banques et comptes Mobile Money</p>
        </div>
        <div class="p-4 space-y-3">
          <!-- Ajouter -->
          <form method="POST" action="<?= $base ?>/app/parametres" class="space-y-2">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="_action" value="add_account">
            <input type="text" name="acc_name" required placeholder="Nom du compte (ex : Caisse principale)"
                   class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            <div class="flex gap-2">
              <select name="acc_type" class="flex-1 rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="caisse">Caisse (espèces)</option>
                <option value="banque">Compte bancaire</option>
                <option value="mobile">Mobile Money</option>
              </select>
              <input type="number" name="acc_balance" value="0" min="0" placeholder="Solde initial"
                     class="w-32 rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
              <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:opacity-90">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
              </button>
            </div>
          </form>
          <!-- Liste -->
          <?php $accounts = $finAccounts ?? []; ?>
          <?php if (empty($accounts)): ?>
            <p class="text-sm text-muted-foreground py-2">Aucun compte configuré.</p>
          <?php else: ?>
            <div class="divide-y divide-border">
              <?php foreach ($accounts as $acc): ?>
                <div class="flex items-center justify-between gap-2 py-2">
                  <div>
                    <p class="text-sm font-medium"><?= e($acc['name']) ?></p>
                    <p class="text-xs text-muted-foreground"><?= ['caisse' => 'Caisse', 'banque' => 'Banque', 'mobile' => 'Mobile Money'][$acc['type']] ?? $acc['type'] ?> · Solde : <?= number_format((float)($acc['current_balance'] ?? 0), 0, ',', ' ') ?> GNF</p>
                  </div>
                  <form method="POST" action="<?= $base ?>/app/parametres"
                        onsubmit="return confirm('Désactiver ce compte ?')">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="_action" value="delete_account">
                    <input type="hidden" name="account_id" value="<?= e($acc['id']) ?>">
                    <button class="rounded-md p-1 text-destructive hover:bg-destructive/10">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                    </button>
                  </form>
                </div>
              <?php endforeach ?>
            </div>
          <?php endif ?>
        </div>
      </div>

      <!-- Catégories financières -->
      <div class="rounded-xl border border-border bg-card">
        <div class="border-b border-border px-5 py-3">
          <h3 class="text-sm font-semibold">Catégories financières</h3>
          <p class="text-xs text-muted-foreground">Classement des entrées et sorties d'argent</p>
        </div>
        <div class="p-4 space-y-3">
          <!-- Ajouter -->
          <form method="POST" action="<?= $base ?>/app/parametres" class="space-y-2">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="_action" value="add_fin_cat">
            <input type="text" name="fincat_name" required placeholder="Nom (ex : Frais bancaires)"
                   class="w-full rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
            <div class="flex gap-2">
              <select name="fincat_direction" class="flex-1 rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
                <option value="entree">Catégorie Entrée</option>
                <option value="sortie">Catégorie Sortie</option>
              </select>
              <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:opacity-90">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
              </button>
            </div>
          </form>
          <!-- Liste -->
          <?php $fincats = $finCategories ?? []; ?>
          <?php if (empty($fincats)): ?>
            <p class="text-sm text-muted-foreground py-2">Aucune catégorie configurée.</p>
          <?php else: ?>
            <div class="divide-y divide-border">
              <?php foreach ($fincats as $cat): ?>
                <div class="flex items-center justify-between gap-2 py-2">
                  <div class="flex items-center gap-2">
                    <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium <?= $cat['direction'] === 'entree' ? 'bg-success/15 text-success' : 'bg-destructive/10 text-destructive' ?>">
                      <?= $cat['direction'] === 'entree' ? '↑ Entrée' : '↓ Sortie' ?>
                    </span>
                    <span class="text-sm"><?= e($cat['name']) ?></span>
                  </div>
                  <form method="POST" action="<?= $base ?>/app/parametres"
                        onsubmit="return confirm('Supprimer cette catégorie ?')">
                    <?= \App\Core\Csrf::field() ?>
                    <input type="hidden" name="_action" value="delete_fin_cat">
                    <input type="hidden" name="fincat_id" value="<?= e($cat['id']) ?>">
                    <button class="rounded-md p-1 text-destructive hover:bg-destructive/10">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                    </button>
                  </form>
                </div>
              <?php endforeach ?>
            </div>
          <?php endif ?>
        </div>
      </div>

    </div>
  </section>

  <!-- Options -->
  <section>
    <h2 class="mb-3 text-sm font-semibold uppercase tracking-wider text-muted-foreground">Listes de choix</h2>
    <div class="grid gap-4 md:grid-cols-2">
      <?php foreach ($optionCategories as $cat):
        $catOptions = $grouped[$cat['category']] ?? [];
      ?>
        <div class="rounded-xl border border-border bg-card">
          <div class="border-b border-border px-5 py-3">
            <h3 class="text-sm font-semibold"><?= e($cat['title']) ?></h3>
            <p class="text-xs text-muted-foreground"><?= e($cat['description']) ?></p>
          </div>
          <div class="p-4 space-y-3">
            <!-- Add -->
            <form method="POST" action="<?= $base ?>/app/parametres" class="flex gap-2">
              <?= \App\Core\Csrf::field() ?>
              <input type="hidden" name="_action" value="add_option">
              <input type="hidden" name="category" value="<?= e($cat['category']) ?>">
              <input type="text" name="label" required placeholder="Nouvel élément…"
                     class="flex-1 rounded-lg border border-border bg-background px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-primary">
              <button type="submit" class="rounded-lg bg-primary px-3 py-1.5 text-xs font-semibold text-primary-foreground hover:opacity-90">
                <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
              </button>
            </form>

            <!-- List -->
            <?php if (empty($catOptions)): ?>
              <p class="text-sm text-muted-foreground py-2">Aucun élément pour l'instant.</p>
            <?php else: ?>
              <div class="divide-y divide-border">
                <?php foreach ($catOptions as $opt): ?>
                  <div class="flex items-center justify-between gap-2 py-2">
                    <span class="text-sm <?= $opt['is_active'] ? '' : 'text-muted-foreground line-through' ?>"><?= e($opt['label']) ?></span>
                    <div class="flex items-center gap-1.5">
                      <!-- Toggle -->
                      <form method="POST" action="<?= $base ?>/app/parametres">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="_action" value="toggle_option">
                        <input type="hidden" name="option_id" value="<?= e($opt['id']) ?>">
                        <input type="hidden" name="is_active" value="<?= $opt['is_active'] ? '0' : '1' ?>">
                        <button type="submit" class="rounded-md border border-border px-2 py-0.5 text-xs hover:bg-muted">
                          <?= $opt['is_active'] ? 'Désactiver' : 'Activer' ?>
                        </button>
                      </form>
                      <!-- Delete -->
                      <form method="POST" action="<?= $base ?>/app/parametres"
                            onsubmit="return confirm('Supprimer cet élément ?')">
                        <?= \App\Core\Csrf::field() ?>
                        <input type="hidden" name="_action" value="delete_option">
                        <input type="hidden" name="option_id" value="<?= e($opt['id']) ?>">
                        <button type="submit" class="rounded-md p-1 text-destructive hover:bg-destructive/10">
                          <svg xmlns="http://www.w3.org/2000/svg" class="size-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916"/></svg>
                        </button>
                      </form>
                    </div>
                  </div>
                <?php endforeach ?>
              </div>
            <?php endif ?>
          </div>
        </div>
      <?php endforeach ?>
    </div>
  </section>

</div>
