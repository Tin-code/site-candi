<?php
use function App\Core\e;
$base    = rtrim(\App\Core\env('APP_URL', ''), '/');
$phone   = $phone   ?? '';
$purpose = $purpose ?? 's'; // 's' = signup, 'r' = reset
$code    = $code    ?? '';
$isReset = $purpose === 'r';

// Format d'affichage du numéro (ex: 224 620 000 000)
$displayPhone = '';
if ($phone !== '') {
    $d = preg_replace('/\D/', '', $phone);
    if (strlen($d) === 12 && str_starts_with($d, '224')) {
        $displayPhone = '224 ' . substr($d, 3, 3) . ' ' . substr($d, 6, 3) . ' ' . substr($d, 9, 3);
    } else {
        $displayPhone = $d;
    }
}
?>
<div class="flex min-h-screen flex-col items-center justify-center bg-muted px-4 py-10">

  <!-- Logo -->
  <a href="<?= $base ?>/" class="mb-8 flex items-center justify-center">
    <img src="<?= $base ?>/images/candi-logo-trim.png" alt="CaNDI"
         class="h-16 w-auto drop-shadow-sm" style="max-height:5rem">
  </a>

  <?php include __DIR__ . '/../partials/flash.php'; ?>

  <!-- Carte -->
  <div class="w-full max-w-md rounded-xl border border-border bg-card shadow-[0_4px_24px_rgba(0,0,0,.08)]">

    <!-- CardHeader -->
    <div class="px-6 pt-6 pb-4">
      <h1 class="text-lg font-semibold leading-none tracking-tight">
        <?= $isReset ? 'Nouveau mot de passe' : 'Confirmer mon numéro' ?>
      </h1>
      <p class="mt-1.5 text-sm text-muted-foreground">
        <?php if ($displayPhone !== ''): ?>
          Code envoyé au <strong class="font-medium text-foreground"><?= e($displayPhone) ?></strong>.
        <?php else: ?>
          Saisissez le code reçu par SMS.
        <?php endif ?>
      </p>
    </div>

    <!-- CardContent -->
    <div class="px-6 pb-6">
      <form method="POST" action="<?= $base ?>/confirmer-telephone" class="space-y-4">
        <?= \App\Core\Csrf::field() ?>
        <input type="hidden" name="purpose" value="<?= e($purpose) ?>">

        <?php if ($phone !== ''): ?>
          <input type="hidden" name="phone" value="<?= e($phone) ?>">
        <?php else: ?>
          <div>
            <label for="cp-phone" class="auth-label">Numéro de téléphone</label>
            <input id="cp-phone" type="tel" name="phone" inputmode="tel"
                   placeholder="620 00 00 00" autocomplete="tel"
                   class="auth-input" required>
          </div>
        <?php endif ?>

        <!-- Code OTP -->
        <div>
          <label for="cp-code" class="auth-label">Code reçu par SMS</label>
          <input id="cp-code" type="text" name="code" inputmode="numeric" maxlength="6"
                 value="<?= e($code) ?>"
                 placeholder="123456"
                 autocomplete="one-time-code"
                 class="auth-input text-center text-lg tracking-[.4em]" required pattern="\d{6}">
        </div>

        <!-- Prénom + Nom (uniquement pour signup) -->
        <?php if (!$isReset): ?>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label for="cp-first" class="auth-label">Prénom</label>
              <input id="cp-first" type="text" name="first_name" autocomplete="given-name"
                     class="auth-input" required minlength="2">
            </div>
            <div>
              <label for="cp-last" class="auth-label">Nom</label>
              <input id="cp-last" type="text" name="last_name" autocomplete="family-name"
                     class="auth-input" required minlength="2">
            </div>
          </div>
        <?php endif ?>

        <!-- Mot de passe -->
        <div>
          <label for="cp-pass" class="auth-label">
            <?= $isReset ? 'Nouveau mot de passe' : 'Choisissez un mot de passe' ?>
          </label>
          <input id="cp-pass" type="password" name="password" autocomplete="new-password"
                 class="auth-input" required minlength="8">
          <p class="mt-1 text-xs text-muted-foreground">8 caractères minimum.</p>
        </div>

        <button type="submit" class="auth-btn-primary" id="cp-submit">
          <?= $isReset ? 'Valider le nouveau mot de passe' : 'Créer mon compte' ?>
        </button>

        <div class="flex items-center justify-between text-xs">
          <a href="<?= $base ?>/auth?tab=phone&mode=<?= $isReset ? 'reset' : 'signup' ?>"
             class="text-muted-foreground underline">
            Changer de numéro
          </a>
          <a href="<?= $base ?>/auth?tab=phone&mode=<?= $isReset ? 'reset' : 'signup' ?>"
             class="underline">
            Renvoyer le code
          </a>
        </div>
      </form>
    </div><!-- /CardContent -->
  </div><!-- /Card -->
</div>

<style>
.auth-label {
  display: block;
  margin-bottom: .375rem;
  font-size: .875rem;
  font-weight: 500;
  line-height: 1.25rem;
}
.auth-input {
  display: block;
  width: 100%;
  border-radius: .375rem;
  border: 1px solid var(--border);
  background: var(--background);
  padding: .5rem .75rem;
  font-size: .875rem;
  line-height: 1.5rem;
  color: var(--foreground);
  outline: none;
  transition: border-color .15s, box-shadow .15s;
}
.auth-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px color-mix(in oklab, var(--primary) 15%, transparent);
}
.auth-btn-primary {
  display: flex;
  width: 100%;
  align-items: center;
  justify-content: center;
  border-radius: .5rem;
  background: var(--primary);
  color: var(--primary-foreground);
  padding: .5rem 1.25rem;
  font-size: .875rem;
  font-weight: 600;
  cursor: pointer;
  border: none;
  transition: opacity .15s;
}
.auth-btn-primary:hover  { opacity: .88; }
.auth-btn-primary:disabled { opacity: .5; cursor: not-allowed; }
</style>

<script>
(function () {
  const codeInput  = document.getElementById('cp-code');
  const submitBtn  = document.getElementById('cp-submit');

  function updateBtn() {
    const digits = codeInput.value.replace(/\D/g, '');
    codeInput.value  = digits.slice(0, 6);
    submitBtn.disabled = digits.length !== 6;
  }

  codeInput.addEventListener('input', updateBtn);
  updateBtn();
})();
</script>
