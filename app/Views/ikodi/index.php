<?php
use function App\Core\e;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');
$csrf = \App\Core\Csrf::token();
?>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-12">

  <header class="space-y-2">
    <h1 class="font-display text-3xl font-bold">CaNDI AI, l'assistant de CaNDI</h1>
    <p class="text-sm text-muted-foreground">
      Posez vos questions sur nos formations, nos sessions et nos activités.
      Aucun compte n'est nécessaire.
    </p>
  </header>

  <!-- Zone de conversation -->
  <div class="rounded-2xl border border-border bg-card min-h-[22rem] p-4 space-y-4" id="ikodi-messages">

    <!-- État vide / suggestions -->
    <div id="ikodi-empty" class="space-y-3 py-6 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-6 text-muted-foreground" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904L9 18.75l-.813-2.846a4.5 4.5 0 00-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 003.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 003.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 00-3.09 3.09zM18.259 8.715L18 9.75l-.259-1.035a3.375 3.375 0 00-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 002.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 002.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 00-2.456 2.456z"/>
      </svg>
      <p class="text-sm text-muted-foreground">Posez une question ou choisissez une suggestion.</p>
      <div class="flex flex-wrap justify-center gap-2">
        <?php foreach ([
          'Quelles formations proposez-vous ?',
          'Quelles sessions démarrent bientôt ?',
          'Combien coûte une formation en comptabilité ?',
          'Où se trouvent vos agences ?',
          'Comment m\'inscrire à une formation ?',
        ] as $suggestion): ?>
          <button onclick="ikodiSend(<?= htmlspecialchars(json_encode($suggestion), ENT_QUOTES) ?>)"
                  class="rounded-full border border-border px-3 py-1 text-sm hover:bg-muted transition-colors">
            <?= e($suggestion) ?>
          </button>
        <?php endforeach ?>
      </div>
    </div>

    <!-- Indicateur de chargement -->
    <p id="ikodi-thinking" class="text-sm text-muted-foreground" style="display:none">
      CaNDI AI réfléchit…
    </p>

    <div id="ikodi-bottom"></div>
  </div>

  <!-- Formulaire d'envoi -->
  <form id="ikodi-form" class="flex items-end gap-2" onsubmit="ikodiSubmit(event)">
    <textarea id="ikodi-input"
              rows="2"
              placeholder="Votre question…"
              aria-label="Question à CaNDI AI"
              class="flex-1 resize-none rounded-xl border border-border bg-background px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-primary"
              onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();ikodiSubmit(event);}"></textarea>
    <button type="submit" id="ikodi-send-btn"
            class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-50">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12zm0 0h7.5"/>
      </svg>
      Envoyer
    </button>
  </form>

  <p class="text-xs text-muted-foreground">
    Pour suivre vos inscriptions et vos paiements,
    <a href="<?= $base ?>/auth?mode=signup" class="underline hover:text-foreground">créez un compte</a>.
  </p>

</div>

<script>
(function () {
  const CSRF   = <?= json_encode($csrf) ?>;
  const BASE   = <?= json_encode($base) ?>;
  const history = [];

  function ikodiAppend(role, text) {
    document.getElementById('ikodi-empty')?.remove();
    const wrap = document.getElementById('ikodi-messages');
    const div  = document.createElement('div');
    div.className = role === 'user'
      ? 'ml-auto max-w-[85%] rounded-xl bg-primary px-3 py-2 text-sm text-primary-foreground'
      : role === 'error'
        ? 'mr-auto max-w-[85%] rounded-xl bg-destructive/10 border border-destructive/20 px-3 py-2 text-sm text-destructive'
        : 'mr-auto max-w-[85%] whitespace-pre-wrap rounded-xl bg-muted px-3 py-2 text-sm';
    div.textContent = text;
    wrap.insertBefore(div, document.getElementById('ikodi-bottom'));
    document.getElementById('ikodi-bottom').scrollIntoView({ behavior: 'smooth' });
  }

  window.ikodiSend = function (text) {
    const content = (text || '').trim();
    if (!content) return;
    document.getElementById('ikodi-input').value = '';
    history.push({ role: 'user', content });
    ikodiAppend('user', content);
    document.getElementById('ikodi-thinking').style.display = '';
    document.getElementById('ikodi-send-btn').disabled = true;

    fetch(BASE + '/ikodi/demander', {
      method:  'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
      body:    JSON.stringify({ messages: history }),
    })
      .then(r => r.json())
      .then(data => {
        document.getElementById('ikodi-thinking').style.display = 'none';
        document.getElementById('ikodi-send-btn').disabled = false;
        if (data.error) {
          ikodiAppend('error', data.error);
        } else {
          history.push({ role: 'assistant', content: data.reply });
          ikodiAppend('assistant', data.reply);
        }
      })
      .catch(() => {
        document.getElementById('ikodi-thinking').style.display = 'none';
        document.getElementById('ikodi-send-btn').disabled = false;
        ikodiAppend('error', 'Erreur réseau. Vérifiez votre connexion et réessayez.');
      });
  };

  window.ikodiSubmit = function (e) {
    if (e) e.preventDefault();
    ikodiSend(document.getElementById('ikodi-input').value);
  };
})();
</script>
