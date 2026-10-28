<?php
use function App\Core\e;
$base     = rtrim(\App\Core\env('APP_URL', ''), '/');
$tab      = $tab  ?? 'phone';
$mode     = $mode ?? 'signin';
$old      = \App\Core\Session::flash('old') ?? [];
$redirect = $_GET['redirect'] ?? '';
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
      <h1 class="text-lg font-semibold leading-none tracking-tight">Votre espace CaNDI</h1>
      <p class="mt-1.5 text-sm text-muted-foreground">
        Connectez-vous ou créez votre compte pour vous inscrire à une formation.
      </p>
    </div>

    <!-- CardContent -->
    <div class="px-6 pb-6">

      <!-- Onglets principaux : grid segmented -->
      <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1 mb-4" role="tablist">
        <button type="button" role="tab" id="tab-phone" aria-controls="panel-phone"
                data-tab="phone"
                class="tab-btn flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition">
          <!-- Smartphone icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="14" height="20" x="5" y="2" rx="2" ry="2"/>
            <path d="M12 18h.01"/>
          </svg>
          Par téléphone
        </button>
        <button type="button" role="tab" id="tab-email" aria-controls="panel-email"
                data-tab="email"
                class="tab-btn flex items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-medium transition">
          <!-- Mail icon -->
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" viewBox="0 0 24 24"
               fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="20" height="16" x="2" y="4" rx="2"/>
            <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
          </svg>
          Par e-mail
        </button>
      </div>

      <!-- ── ONGLET TÉLÉPHONE ─────────────────────────────────────────────── -->
      <div id="panel-phone" role="tabpanel" aria-labelledby="tab-phone" class="space-y-4">

        <!-- Sous-onglets téléphone : segmented control -->
        <div class="grid grid-cols-3 gap-1 rounded-lg bg-muted p-1 text-xs">
          <button type="button" class="phone-mode-btn rounded-md px-2 py-2 font-medium transition"
                  data-mode="signin">Connexion</button>
          <button type="button" class="phone-mode-btn rounded-md px-2 py-2 font-medium transition"
                  data-mode="signup">Créer un compte</button>
          <button type="button" class="phone-mode-btn rounded-md px-2 py-2 font-medium transition"
                  data-mode="reset">Mot de passe oublié</button>
        </div>

        <!-- Connexion téléphone -->
        <div id="phone-signin" class="phone-panel space-y-4">
          <form method="POST" action="<?= $base ?>/auth/login" class="space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="tab" value="phone">
            <?php if ($redirect !== ''): ?>
              <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <?php endif ?>
            <div>
              <label for="ps-phone" class="auth-label">Numéro de téléphone</label>
              <input id="ps-phone" type="tel" name="phone" inputmode="tel"
                     placeholder="620 00 00 00" autocomplete="tel"
                     class="auth-input" required>
            </div>
            <div>
              <label for="ps-pass" class="auth-label">Mot de passe</label>
              <input id="ps-pass" type="password" name="password" autocomplete="current-password"
                     class="auth-input" required minlength="8">
            </div>
            <button type="submit" class="auth-btn-primary">Se connecter</button>
          </form>
        </div>

        <!-- Inscription téléphone (signup) -->
        <div id="phone-signup" class="phone-panel hidden space-y-4">
          <form method="POST" action="<?= $base ?>/auth/otp/send" class="space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="purpose" value="signup">
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="su-first" class="auth-label">Prénom</label>
                <input id="su-first" type="text" name="first_name" autocomplete="given-name"
                       value="<?= e($old['first_name'] ?? '') ?>"
                       class="auth-input" required minlength="2">
              </div>
              <div>
                <label for="su-last" class="auth-label">Nom</label>
                <input id="su-last" type="text" name="last_name" autocomplete="family-name"
                       value="<?= e($old['last_name'] ?? '') ?>"
                       class="auth-input" required minlength="2">
              </div>
            </div>
            <div>
              <label for="su-phone" class="auth-label">Numéro de téléphone</label>
              <input id="su-phone" type="tel" name="phone" inputmode="tel"
                     placeholder="620 00 00 00" autocomplete="tel"
                     class="auth-input" required>
              <p class="mt-1 text-xs text-muted-foreground">
                Vous recevrez un code de confirmation par SMS sur ce numéro.
              </p>
            </div>
            <button type="submit" class="auth-btn-primary">Envoyer le code par SMS</button>
          </form>
        </div>

        <!-- Réinitialisation mot de passe (reset) -->
        <div id="phone-reset" class="phone-panel hidden space-y-4">
          <form method="POST" action="<?= $base ?>/auth/otp/send" class="space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="purpose" value="reset">
            <div>
              <label for="rp-phone" class="auth-label">Numéro de téléphone</label>
              <input id="rp-phone" type="tel" name="phone" inputmode="tel"
                     placeholder="620 00 00 00" autocomplete="tel"
                     class="auth-input" required>
              <p class="mt-1 text-xs text-muted-foreground">
                Vous recevrez un code pour définir un nouveau mot de passe.
              </p>
            </div>
            <button type="submit" class="auth-btn-primary">Envoyer le code par SMS</button>
          </form>
        </div>

      </div><!-- /panel-phone -->

      <!-- ── ONGLET E-MAIL ────────────────────────────────────────────────── -->
      <div id="panel-email" role="tabpanel" aria-labelledby="tab-email" class="hidden space-y-4">

        <!-- Sous-onglets e-mail : segmented control -->
        <div class="grid grid-cols-2 gap-1 rounded-lg bg-muted p-1 text-sm">
          <button type="button" class="email-mode-btn rounded-md px-3 py-2 font-medium transition"
                  data-mode="signin">Connexion</button>
          <button type="button" class="email-mode-btn rounded-md px-3 py-2 font-medium transition"
                  data-mode="signup">Créer un compte</button>
        </div>

        <!-- Connexion e-mail -->
        <div id="email-signin" class="email-panel space-y-4">
          <form method="POST" action="<?= $base ?>/auth/login" class="space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <input type="hidden" name="tab" value="email">
            <?php if ($redirect !== ''): ?>
              <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
            <?php endif ?>
            <div>
              <label for="es-email" class="auth-label">Adresse e-mail</label>
              <input id="es-email" type="email" name="email" autocomplete="email"
                     value="<?= e($old['email'] ?? '') ?>"
                     class="auth-input" required>
            </div>
            <div>
              <label for="es-pass" class="auth-label">Mot de passe</label>
              <input id="es-pass" type="password" name="password" autocomplete="current-password"
                     class="auth-input" required minlength="8">
            </div>
            <button type="submit" class="auth-btn-primary">Se connecter</button>
          </form>
        </div>

        <!-- Inscription e-mail -->
        <div id="email-signup" class="email-panel hidden space-y-4">
          <form method="POST" action="<?= $base ?>/auth/register" class="space-y-4">
            <?= \App\Core\Csrf::field() ?>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label for="eu-first" class="auth-label">Prénom</label>
                <input id="eu-first" type="text" name="first_name" autocomplete="given-name"
                       value="<?= e($old['first_name'] ?? '') ?>"
                       class="auth-input" required minlength="2">
              </div>
              <div>
                <label for="eu-last" class="auth-label">Nom</label>
                <input id="eu-last" type="text" name="last_name" autocomplete="family-name"
                       value="<?= e($old['last_name'] ?? '') ?>"
                       class="auth-input" required minlength="2">
              </div>
            </div>
            <div>
              <label for="eu-phone" class="auth-label">
                Téléphone <span class="text-xs text-muted-foreground">(optionnel)</span>
              </label>
              <input id="eu-phone" type="tel" name="phone" inputmode="tel"
                     placeholder="+224 6.. .. .. .." autocomplete="tel"
                     value="<?= e($old['phone'] ?? '') ?>"
                     class="auth-input">
            </div>
            <div>
              <label for="eu-email" class="auth-label">Adresse e-mail</label>
              <input id="eu-email" type="email" name="email" autocomplete="email"
                     value="<?= e($old['email'] ?? '') ?>"
                     class="auth-input" required>
            </div>
            <div>
              <label for="eu-pass" class="auth-label">Mot de passe</label>
              <input id="eu-pass" type="password" name="password" autocomplete="new-password"
                     class="auth-input" required minlength="8">
              <p class="mt-1 text-xs text-muted-foreground">8 caractères minimum.</p>
            </div>
            <button type="submit" class="auth-btn-primary">Créer mon compte</button>
          </form>
        </div>

      </div><!-- /panel-email -->

      <!-- Retour au site public -->
      <p class="mt-4 text-center text-xs text-muted-foreground">
        <a href="<?= $base ?>/" class="underline">Retour au site public</a>
      </p>

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
  const $ = id => document.getElementById(id);

  // ── Onglets principaux ─────────────────────────────────────────────────
  const tabBtns = document.querySelectorAll('.tab-btn');
  const panels  = { phone: $('panel-phone'), email: $('panel-email') };

  function activateTab(active) {
    tabBtns.forEach(b => {
      const on = b.dataset.tab === active;
      b.classList.toggle('bg-background', on);
      b.classList.toggle('shadow-sm',     on);
      b.classList.toggle('text-muted-foreground', !on);
    });
    Object.entries(panels).forEach(([k, p]) => p.classList.toggle('hidden', k !== active));
  }

  tabBtns.forEach(b => b.addEventListener('click', () => activateTab(b.dataset.tab)));

  // ── Sous-modes téléphone ───────────────────────────────────────────────
  const phoneModes = ['signin', 'signup', 'reset'];
  const phoneBtns  = document.querySelectorAll('.phone-mode-btn');

  function setPhoneMode(mode) {
    phoneModes.forEach(m => {
      const p = $('phone-' + m);
      if (p) p.classList.toggle('hidden', m !== mode);
    });
    phoneBtns.forEach(b => {
      const on = b.dataset.mode === mode;
      b.classList.toggle('bg-background', on);
      b.classList.toggle('shadow-sm',     on);
      b.classList.toggle('text-muted-foreground', !on);
    });
  }

  phoneBtns.forEach(b => b.addEventListener('click', () => setPhoneMode(b.dataset.mode)));

  // ── Sous-modes e-mail ──────────────────────────────────────────────────
  const emailModes = ['signin', 'signup'];
  const emailBtns  = document.querySelectorAll('.email-mode-btn');

  function setEmailMode(mode) {
    emailModes.forEach(m => {
      const p = $('email-' + m);
      if (p) p.classList.toggle('hidden', m !== mode);
    });
    emailBtns.forEach(b => {
      const on = b.dataset.mode === mode;
      b.classList.toggle('bg-background', on);
      b.classList.toggle('shadow-sm',     on);
      b.classList.toggle('text-muted-foreground', !on);
    });
  }

  emailBtns.forEach(b => b.addEventListener('click', () => setEmailMode(b.dataset.mode)));

  // ── État initial depuis PHP ────────────────────────────────────────────
  const initTab  = <?= json_encode($tab) ?>;
  const initMode = <?= json_encode($mode) ?>;

  activateTab(initTab);
  if (initTab === 'phone') {
    setPhoneMode(phoneModes.includes(initMode) ? initMode : 'signin');
  } else {
    setEmailMode(emailModes.includes(initMode) ? initMode : 'signin');
  }
})();
</script>
