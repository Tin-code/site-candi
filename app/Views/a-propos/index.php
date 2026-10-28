<?php
use function App\Core\e;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
$a      = $about; // alias

$pillarIcons = [
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
];
?>

<!-- EN-TÊTE -->
<section class="relative overflow-hidden bg-sidebar text-sidebar-foreground">
  <img src="<?= e($a['images']['hero'] ?? $appUrl . '/images/hero-candi-equipe-2.jpg') ?>"
       alt="" aria-hidden="true"
       class="absolute inset-0 size-full object-cover opacity-30">
  <div class="absolute inset-0 bg-[linear-gradient(100deg,color-mix(in_oklab,var(--sidebar)_88%,transparent)_0%,color-mix(in_oklab,var(--sidebar)_60%,transparent)_100%)]"></div>
  <div class="relative z-10 mx-auto w-full max-w-6xl px-4 py-24 lg:px-6">
    <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent">À propos de nous</p>
    <h1 class="mt-6 max-w-3xl text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl">
      <?= e($a['heroTitle']) ?>
    </h1>
    <p class="mt-6 max-w-2xl text-base leading-relaxed text-sidebar-foreground/85 md:text-lg">
      <?= e($a['heroIntro']) ?>
    </p>
  </div>
</section>

<!-- CHIFFRES CLÉS -->
<section class="border-b border-border bg-card">
  <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4 lg:px-6">
    <?php foreach ($a['stats'] as $stat): ?>
      <div>
        <p class="font-display text-3xl font-bold tracking-tight text-primary"><?= e($stat['value']) ?></p>
        <p class="mt-1 text-sm text-muted-foreground"><?= e($stat['label']) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<!-- NOTRE HISTOIRE -->
<section class="mx-auto w-full max-w-6xl px-4 py-20 lg:px-6">
  <div class="grid gap-12 lg:grid-cols-[1.15fr_1fr] lg:items-center">
    <div>
      <h2 class="font-display text-2xl font-bold tracking-tight text-primary md:text-3xl">Notre histoire</h2>
      <div class="mt-6 space-y-4 text-base leading-relaxed text-muted-foreground">
        <?php foreach ($a['history'] as $paragraph): ?>
          <p><?= e($paragraph) ?></p>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="grid gap-4">
      <img src="<?= e($a['images']['history1'] ?? $appUrl . '/images/hero-candi-1.jpg') ?>"
           alt="L'équipe CaNDI lors d'une remise de distinction"
           class="h-64 w-full rounded-2xl object-cover shadow-[var(--shadow-elevated)]" loading="lazy">
      <img src="<?= e($a['images']['history2'] ?? $appUrl . '/images/hero-candi-4.jpg') ?>"
           alt="Les activités de formation de CaNDI"
           class="h-48 w-full rounded-2xl object-cover shadow-[var(--shadow-elevated)]" loading="lazy">
    </div>
  </div>
</section>

<!-- MOT DU DIRECTEUR -->
<section class="border-y border-border bg-primary/5">
  <div class="mx-auto grid w-full max-w-5xl gap-10 px-4 py-16 lg:grid-cols-[320px_1fr] lg:items-center lg:px-6">
    <div class="mx-auto w-full max-w-xs lg:max-w-none">
      <img src="<?= e($a['images']['founder'] ?? $appUrl . '/images/fondateur-candi.png') ?>"
           alt="Mohamed SOUARE, Fondateur et Directeur Général de CaNDI"
           class="aspect-[3/4] w-full rounded-2xl object-cover shadow-[var(--shadow-elevated)]" loading="lazy">
    </div>
    <div class="text-center lg:text-left">
      <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-8 text-primary lg:mx-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
      </svg>
      <blockquote class="mt-6 font-display text-xl font-semibold leading-relaxed tracking-tight text-foreground md:text-2xl">
        « <?= e($a['quote']) ?> »
      </blockquote>
      <p class="mt-6 font-display text-sm font-bold tracking-tight text-primary"><?= e($a['directorName']) ?></p>
      <p class="text-sm text-muted-foreground"><?= e($a['directorTitle']) ?></p>
    </div>
  </div>
</section>

<!-- NOS PÔLES -->
<section class="mx-auto w-full max-w-6xl px-4 py-20 lg:px-6">
  <h2 class="font-display text-2xl font-bold tracking-tight text-primary md:text-3xl">Nos trois pôles d'activité</h2>
  <p class="mt-2 max-w-2xl text-muted-foreground">Trois métiers complémentaires, une même exigence de qualité et de transparence.</p>
  <div class="mt-10 grid gap-6 md:grid-cols-3">
    <?php foreach ($a['pillars'] as $i => $pillar): ?>
      <article class="flex flex-col rounded-2xl border border-border bg-card p-7 transition-shadow duration-200 hover:shadow-[var(--shadow-elevated)]">
        <span class="flex size-12 items-center justify-center rounded-xl bg-primary/10">
          <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <?= $pillarIcons[$i % count($pillarIcons)] ?>
          </svg>
        </span>
        <h3 class="mt-5 font-display text-base font-bold tracking-tight"><?= e($pillar['title']) ?></h3>
        <p class="mt-2 text-sm leading-relaxed text-muted-foreground"><?= e($pillar['text']) ?></p>
      </article>
    <?php endforeach; ?>
  </div>
</section>

<!-- VALEURS -->
<section class="border-y border-border bg-card">
  <div class="mx-auto w-full max-w-6xl px-4 py-16 lg:px-6">
    <h2 class="font-display text-2xl font-bold tracking-tight text-primary md:text-3xl">Ce qui nous guide</h2>
    <div class="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
      <?php foreach ($a['values'] as $value): ?>
        <div class="border-l-2 border-accent pl-4">
          <h3 class="font-display text-base font-bold tracking-tight"><?= e($value['title']) ?></h3>
          <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground"><?= e($value['text']) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- DISTINCTION & PRÉSENCE -->
<section class="mx-auto w-full max-w-6xl px-4 py-20 lg:px-6">
  <div class="grid gap-6 md:grid-cols-3">
    <div class="rounded-2xl border border-border bg-card p-7">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
      </svg>
      <h3 class="mt-4 font-display text-base font-bold tracking-tight">Une entreprise distinguée</h3>
      <p class="mt-2 text-sm leading-relaxed text-muted-foreground"><?= e($a['distinction']) ?></p>
    </div>

    <div class="rounded-2xl border border-border bg-card p-7">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <h3 class="mt-4 font-display text-base font-bold tracking-tight">Où nous trouver</h3>
      <p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-muted-foreground"><?= e($a['locations']) ?></p>
      <p class="mt-1 text-sm text-muted-foreground">+224 626 01 17 17 · +224 625 29 54 56</p>
      <p class="mt-0.5 text-sm text-muted-foreground">info@candi-group.com</p>
    </div>

    <div class="rounded-2xl border border-border bg-card p-7">
      <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
      <h3 class="mt-4 font-display text-base font-bold tracking-tight">Une entreprise officielle</h3>
      <p class="mt-2 text-sm leading-relaxed text-muted-foreground">
        RCCM/GN-KA/2023/B/3521. <?= e($a['official']) ?>
      </p>
    </div>
  </div>

  <div class="mt-12 flex flex-wrap items-center gap-4">
    <a href="<?= $appUrl ?>/formations"
       class="inline-flex items-center gap-2 rounded-full bg-primary px-8 py-4 font-display text-sm font-bold text-primary-foreground transition-colors duration-200 hover:bg-primary/90">
      Découvrir nos formations
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
      </svg>
    </a>
    <a href="<?= $appUrl ?>/contact"
       class="inline-flex items-center gap-2 rounded-full border border-border px-8 py-4 font-display text-sm font-bold transition-colors duration-200 hover:border-primary hover:text-primary">
      Nous contacter
    </a>
  </div>
</section>
