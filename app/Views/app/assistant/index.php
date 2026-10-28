<?php
use function App\Core\e;
$base      = rtrim(\App\Core\env('APP_URL', ''), '/');
$csrfToken = \App\Core\Csrf::token();
?>

<div class="mx-auto flex max-w-3xl flex-col gap-6">

  <!-- Header -->
  <header>
    <h1 class="font-display text-2xl font-bold">CaNDI AI</h1>
    <p class="text-sm text-muted-foreground">
      Analyse de vos inscriptions, finances, sessions et tâches — dans la limite de vos droits d'accès.
    </p>
  </header>

  <!-- Messages card -->
  <div class="rounded-xl border border-border bg-card min-h-[22rem]">
    <div id="ai-messages" class="space-y-4 p-4">

      <!-- Empty state -->
      <div id="ai-empty" class="space-y-3 py-6 text-center">
        <!-- Sparkles icon -->
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-6 text-muted-foreground" viewBox="0 0 24 24"
             fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
             aria-hidden="true">
          <path d="M9.937 15.5A2 2 0 0 0 8.5 14.063l-6.135-1.582a.5.5 0 0 1 0-.962L8.5 9.936A2 2 0 0 0 9.937 8.5l1.582-6.135a.5.5 0 0 1 .963 0L14.063 8.5A2 2 0 0 0 15.5 9.937l6.135 1.581a.5.5 0 0 1 0 .964L15.5 14.063a2 2 0 0 0-1.437 1.437l-1.582 6.135a.5.5 0 0 1-.963 0z"/>
          <path d="M20 3v4m2-2h-4M4 17v2m1-1H3"/>
        </svg>
        <p class="text-sm text-muted-foreground">Posez une question ou choisissez une suggestion.</p>
        <div id="ai-suggestions" class="flex flex-wrap justify-center gap-2">
          <?php
          $suggestions = [
            'Quel est le résultat net actuel ?',
            'Quels sont les soldes de chaque caisse et compte ?',
            'Combien reste-t-il à encaisser sur les inscriptions ?',
            'Quelles sessions démarrent bientôt et sont peu remplies ?',
            'Quels clients ont été ajoutés récemment ?',
            'Quelles formations sont publiées et à quel prix ?',
            'Résume les tâches urgentes ou en retard.',
          ];
          foreach ($suggestions as $s): ?>
            <button type="button"
                    onclick="aiSend(<?= htmlspecialchars(json_encode($s), ENT_QUOTES) ?>)"
                    class="rounded-full border border-border bg-background px-3 py-1.5 text-xs font-medium
                           hover:bg-muted transition-colors">
              <?= e($s) ?>
            </button>
          <?php endforeach ?>
        </div>
      </div>

    </div>
    <!-- Typing indicator (hidden by default) -->
    <div id="ai-typing" class="hidden px-4 pb-3">
      <p class="text-sm text-muted-foreground">CaNDI AI réfléchit…</p>
    </div>
    <!-- Scroll anchor -->
    <div id="ai-bottom"></div>
  </div>

  <!-- Input form -->
  <form id="ai-form" class="flex items-end gap-2" onsubmit="aiSubmit(event)">
    <textarea id="ai-input"
              placeholder="Votre question…"
              rows="2"
              aria-label="Question à CaNDI AI"
              class="app-input flex-1 resize-none"
              style="min-height:3rem"></textarea>
    <button type="submit" id="ai-send-btn"
            class="app-btn-primary shrink-0 flex items-center gap-2"
            disabled>
      <!-- Send icon -->
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" viewBox="0 0 24 24"
           fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"
           aria-hidden="true">
        <path d="M14.536 21.686a.5.5 0 0 0 .937-.024l6.5-19a.496.496 0 0 0-.635-.635l-19 6.5a.5.5 0 0 0-.024.937l7.93 3.18a2 2 0 0 1 1.112 1.11z"/>
        <path d="m21.854 2.147-10.94 10.939"/>
      </svg>
      Envoyer
    </button>
  </form>

</div>

<script>
(function () {
  const BASE   = <?= json_encode($base) ?>;
  const CSRF   = <?= json_encode($csrfToken) ?>;
  const input  = document.getElementById('ai-input');
  const btn    = document.getElementById('ai-send-btn');
  const msgs   = document.getElementById('ai-messages');
  const empty  = document.getElementById('ai-empty');
  const typing = document.getElementById('ai-typing');
  const bottom = document.getElementById('ai-bottom');

  let history  = [];
  let busy     = false;

  // Enable/disable send button based on input
  input.addEventListener('input', () => {
    btn.disabled = !input.value.trim() || busy;
  });

  // Enter = send (Shift+Enter = newline)
  input.addEventListener('keydown', (e) => {
    if (e.key === 'Enter' && !e.shiftKey) {
      e.preventDefault();
      if (!btn.disabled) aiSubmit(null);
    }
  });

  window.aiSubmit = function (e) {
    if (e) e.preventDefault();
    const text = input.value.trim();
    if (!text || busy) return;
    window.aiSend(text);
  };

  window.aiSend = function (text) {
    if (busy) return;
    // Hide empty state on first message
    if (empty) empty.style.display = 'none';

    appendMessage('user', text);
    history.push({ role: 'user', content: text });
    input.value = '';
    btn.disabled = true;
    busy = true;
    typing.classList.remove('hidden');
    scrollBottom();

    fetch(BASE + '/app/ai/demander', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': CSRF,
      },
      body: JSON.stringify({ messages: history }),
    })
      .then(r => r.json())
      .then(data => {
        typing.classList.add('hidden');
        if (data.error) {
          appendMessage('error', data.error);
        } else {
          const reply = data.reply || 'Aucune réponse générée.';
          appendMessage('assistant', reply);
          history.push({ role: 'assistant', content: reply });
        }
      })
      .catch(err => {
        typing.classList.add('hidden');
        appendMessage('error', 'Erreur de connexion : ' + err.message);
      })
      .finally(() => {
        busy = false;
        btn.disabled = !input.value.trim();
        scrollBottom();
      });
  };

  function appendMessage(role, content) {
    const div = document.createElement('div');
    if (role === 'user') {
      div.className = 'ml-auto max-w-[85%] rounded-lg bg-primary px-3 py-2 text-sm text-primary-foreground';
      div.textContent = content;
    } else if (role === 'assistant') {
      div.className = 'mr-auto max-w-[85%] whitespace-pre-wrap rounded-lg bg-muted px-3 py-2 text-sm';
      div.textContent = content;
    } else {
      // error
      div.className = 'mx-auto max-w-[90%] rounded-lg border border-destructive/40 bg-destructive/10 px-3 py-2 text-sm text-destructive';
      div.textContent = content;
    }
    msgs.appendChild(div);
    scrollBottom();
  }

  function scrollBottom() {
    setTimeout(() => bottom.scrollIntoView({ behavior: 'smooth' }), 50);
  }
})();
</script>
