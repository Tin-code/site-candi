<?php use function App\Core\e; ?>

<div class="mx-auto w-full max-w-4xl px-4 py-14">
  <h1 class="font-display text-3xl font-bold">Nous contacter</h1>
  <p class="mt-2 text-muted-foreground">
    Notre équipe répond à vos questions sur les formations et les progiciels de gestion.
  </p>

  <div class="mt-8 grid gap-4 sm:grid-cols-2">

    <!-- Adresses -->
    <div class="rounded-xl border border-border bg-card p-6 shadow-[var(--shadow-card)]">
      <div class="space-y-3 text-sm">
        <p class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Siège — Kaloum, Conakry
        </p>
        <p class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>
          Agence — Sogbè, Kankan
        </p>
      </div>
    </div>

    <!-- Contacts -->
    <div class="rounded-xl border border-border bg-card p-6 shadow-[var(--shadow-card)]">
      <div class="space-y-3 text-sm">
        <?php foreach (['+224 626 01 17 17', '+224 625 29 54 56', '+224 622 98 44 45'] as $phone): ?>
          <p class="flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
            </svg>
            <?= e($phone) ?>
          </p>
        <?php endforeach; ?>
        <p class="flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
          </svg>
          <a href="mailto:info@candi-group.com" class="hover:text-primary">info@candi-group.com</a>
        </p>
      </div>
    </div>

  </div>
</div>
