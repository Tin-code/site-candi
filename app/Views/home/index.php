<?php
use function App\Core\e;
use App\Helpers\Format;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');

$heroSlides = [
    ['url' => $appUrl . '/images/hero-candi-equipe-2.jpg', 'alt' => "L'équipe CaNDI recevant le trophée de meilleure entreprise de formation"],
    ['url' => $appUrl . '/images/hero-candi-1.jpg',        'alt' => "L'équipe CaNDI lors d'une cérémonie de remise de distinction"],
    ['url' => $appUrl . '/images/hero-candi-4.jpg',        'alt' => 'Les activités de CaNDI'],
    ['url' => $appUrl . '/images/hero-candi-5.jpg',        'alt' => 'Les activités de CaNDI'],
    ['url' => $appUrl . '/images/hero-candi-6.jpg',        'alt' => 'Les activités de CaNDI'],
    ['url' => $appUrl . '/images/hero-candi-7.jpg',        'alt' => 'Les activités de CaNDI'],
    ['url' => $appUrl . '/images/hero-candi-8.jpg',        'alt' => 'Les activités de CaNDI'],
];
?>

<!-- ===================== HERO ===================== -->
<section class="relative flex min-h-[82vh] items-end overflow-hidden bg-sidebar text-sidebar-foreground">

  <!-- Backdrop carousel -->
  <div class="pointer-events-none absolute inset-0" aria-hidden="true" id="hero-backdrop">
    <?php foreach ($heroSlides as $i => $slide): ?>
      <img
        src="<?= e($slide['url']) ?>"
        alt=""
        <?= $i === 0 ? 'loading="eager"' : 'loading="lazy"' ?>
        class="absolute inset-0 size-full object-cover transition-opacity duration-1000 <?= $i === 0 ? 'opacity-80' : 'opacity-0' ?>"
        data-hero-slide="<?= $i ?>"
      >
    <?php endforeach; ?>
    <div class="absolute inset-0 bg-[linear-gradient(100deg,color-mix(in_oklab,var(--sidebar)_82%,transparent)_0%,color-mix(in_oklab,var(--sidebar)_55%,transparent)_55%,color-mix(in_oklab,var(--sidebar)_35%,transparent)_100%)]"></div>
    <div class="absolute inset-0 bg-[linear-gradient(180deg,transparent_60%,color-mix(in_oklab,var(--background)_90%,transparent)_100%)]"></div>
  </div>

  <div class="relative z-10 mx-auto w-full max-w-6xl px-4 pb-28 pt-40 lg:px-6">
    <div class="max-w-3xl">
      <p class="text-xs font-bold uppercase tracking-[0.25em] text-accent">
        Institut de formation &amp; progiciels — Guinée
      </p>
      <h1 class="mt-6 text-4xl font-bold leading-[1.08] tracking-tight sm:text-5xl md:text-6xl">
        Propulsez votre <span class="text-accent">avenir</span> numérique
      </h1>
      <p class="mt-6 max-w-xl text-base leading-relaxed text-sidebar-foreground/80 md:text-lg">
        Formations certifiantes, progiciels de gestion et services numériques
        pour les professionnels et les entreprises guinéennes.
      </p>
      <div class="mt-10 flex flex-wrap items-center gap-4">
        <a href="<?= $appUrl ?>/formations"
           class="inline-flex items-center gap-2 rounded-full bg-accent px-8 py-4 font-display text-sm font-bold text-accent-foreground transition-colors duration-200 hover:bg-accent/90">
          Nos formations
          <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
          </svg>
        </a>
        <a href="<?= $appUrl ?>/produits-services"
           class="inline-flex items-center gap-2 rounded-full border border-sidebar-foreground/30 px-8 py-4 font-display text-sm font-bold text-sidebar-foreground transition-colors duration-200 hover:border-accent hover:text-accent">
          Logiciels de gestion
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ===================== À PROPOS ===================== -->
<section class="border-b border-border bg-card">
  <div class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-16 lg:grid-cols-[1.2fr_1fr] lg:items-center lg:px-6">
    <div>
      <p class="text-xs font-bold uppercase tracking-[0.25em] text-primary">À propos de CaNDI</p>
      <h2 class="mt-4 font-display text-2xl font-bold leading-snug tracking-tight md:text-3xl">
        Bâtir les compétences, propulser l'excellence
      </h2>
      <p class="mt-4 max-w-2xl text-base leading-relaxed text-muted-foreground">
        Fondée en 2023 par <strong class="text-foreground">Mohamed SOUARE</strong>, son Directeur Général,
        CaNDI réunit trois métiers au service de la Guinée : la formation professionnelle certifiante,
        les progiciels de gestion et la gestion immobilière moderne. Présente à Conakry et à Kankan,
        l'entreprise a été distinguée du trophée de meilleure entreprise de formation.
      </p>
      <a href="<?= $appUrl ?>/a-propos"
         class="group mt-7 inline-flex items-center gap-2 rounded-full bg-primary px-7 py-3.5 font-display text-sm font-bold text-primary-foreground transition-colors duration-200 hover:bg-primary/90">
        Découvrir notre histoire
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>

    <dl class="grid grid-cols-2 gap-5">
      <?php foreach ([
        ['value' => '2023',    'label' => 'Année de fondation'],
        ['value' => '3',       'label' => "Pôles d'activité"],
        ['value' => '2',       'label' => 'Villes : Conakry & Kankan'],
        ['value' => 'Trophée', 'label' => 'Meilleure entreprise de formation'],
      ] as $stat): ?>
        <div class="rounded-2xl border border-border bg-background p-5">
          <dt class="font-display text-2xl font-bold tracking-tight text-primary"><?= e($stat['value']) ?></dt>
          <dd class="mt-1 text-xs leading-relaxed text-muted-foreground"><?= e($stat['label']) ?></dd>
        </div>
      <?php endforeach; ?>
    </dl>
  </div>
</section>

<!-- ===================== ATOUTS ===================== -->
<section class="border-b border-border">
  <div class="mx-auto grid w-full max-w-6xl gap-10 px-4 py-16 sm:grid-cols-3 lg:px-6">

    <div class="flex gap-5">
      <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </span>
      <div>
        <h2 class="font-display text-base font-bold tracking-tight">Expertise certifiée</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">Attestation officielle CaNDI à l'issue de chaque parcours validé.</p>
      </div>
    </div>

    <div class="flex gap-5">
      <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
        </svg>
      </span>
      <div>
        <h2 class="font-display text-base font-bold tracking-tight">Solutions agiles</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">Des progiciels flexibles pour automatiser vos processus métiers.</p>
      </div>
    </div>

    <div class="flex gap-5">
      <span class="flex size-12 shrink-0 items-center justify-center rounded-xl bg-primary/10">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
      </span>
      <div>
        <h2 class="font-display text-base font-bold tracking-tight">Accompagnement</h2>
        <p class="mt-1.5 text-sm leading-relaxed text-muted-foreground">Un support local dédié pour garantir la réussite de vos projets.</p>
      </div>
    </div>

  </div>
</section>

<!-- ===================== FORMATIONS À LA UNE ===================== -->
<section class="mx-auto w-full max-w-6xl px-4 py-20 lg:px-6">
  <div class="mb-10 flex items-end justify-between gap-6">
    <div>
      <h2 class="font-display text-2xl font-bold tracking-tight text-primary md:text-3xl">
        Formations à la une
      </h2>
      <p class="mt-2 text-muted-foreground">
        Développez les compétences les plus recherchées par les entreprises aujourd'hui.
      </p>
    </div>
    <a href="<?= $appUrl ?>/formations"
       class="group hidden shrink-0 items-center gap-1.5 text-sm font-bold text-primary md:inline-flex">
      Voir tout le catalogue
      <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
      </svg>
    </a>
  </div>

  <?php if (empty($programs)): ?>
    <p class="text-muted-foreground">Aucune formation disponible pour le moment.</p>
  <?php else: ?>
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
      <?php foreach ($programs as $program): ?>
        <a href="<?= $appUrl ?>/formations"
           class="group flex flex-col rounded-2xl border border-border bg-card p-6 transition-shadow duration-200 hover:shadow-[var(--shadow-elevated)]">
          <div class="flex items-center justify-between gap-2">
            <span class="inline-flex items-center rounded-full border border-secondary-foreground/20 bg-secondary px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest text-secondary-foreground">
              <?= e($program['level'] ?? '') ?>
            </span>
            <span class="text-xs text-muted-foreground"><?= e($program['duration_label'] ?? '') ?></span>
          </div>
          <h3 class="mt-4 font-display text-base font-bold leading-snug tracking-tight transition-colors duration-200 group-hover:text-primary">
            <?= e($program['title']) ?>
          </h3>
          <p class="mt-2 line-clamp-2 text-sm text-muted-foreground"><?= e($program['summary'] ?? '') ?></p>
          <div class="mt-auto pt-5">
            <span class="font-display text-lg font-bold text-primary">
              <?= Format::money((float) ($program['price'] ?? 0)) ?>
            </span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <a href="<?= $appUrl ?>/formations"
     class="mt-8 inline-flex items-center gap-1.5 text-sm font-bold text-primary md:hidden">
    Voir tout le catalogue
    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
    </svg>
  </a>
</section>

<!-- ===================== ACTUALITÉS + SESSIONS ===================== -->
<?php if (!empty($posts)): ?>
<section class="border-t border-border bg-card/40">
  <div class="mx-auto grid w-full max-w-6xl gap-14 px-4 py-20 lg:grid-cols-12 lg:px-6">

    <!-- Actualités -->
    <div class="lg:col-span-7">
      <h2 class="font-display text-2xl font-bold tracking-tight text-primary md:text-3xl">
        L'actualité de CaNDI
      </h2>
      <div class="mt-8 divide-y divide-border">
        <?php foreach ($posts as $post): ?>
          <a href="<?= $appUrl ?>/actualites/<?= e($post['slug']) ?>"
             class="group flex items-start gap-6 py-6 first:pt-0 last:pb-0">
            <?php if (!empty($post['cover_url'])): ?>
              <div class="size-20 shrink-0 overflow-hidden rounded-xl bg-muted sm:size-24">
                <img src="<?= e(\App\Core\asset_url($post['cover_url'])) ?>" alt="<?= e($post['title']) ?>" loading="lazy" class="size-full object-cover">
              </div>
            <?php endif; ?>
            <div class="min-w-0">
              <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-accent"><?= e($post['category'] ?? '') ?></p>
              <h3 class="mt-1.5 font-display text-lg font-bold leading-snug tracking-tight transition-colors duration-200 group-hover:text-primary">
                <?= e($post['title']) ?>
              </h3>
              <p class="mt-1.5 line-clamp-2 text-sm text-muted-foreground"><?= e($post['excerpt'] ?? '') ?></p>
              <p class="mt-2 text-xs text-muted-foreground">
                <?= Format::date($post['published_at'] ?? $post['created_at'] ?? null) ?>
              </p>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
      <a href="<?= $appUrl ?>/actualites"
         class="group mt-6 inline-flex items-center gap-1.5 text-sm font-bold text-primary">
        Toutes les actualités
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 transition-transform duration-200 group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
        </svg>
      </a>
    </div>

    <!-- Prochaines sessions -->
    <div class="lg:col-span-5">
      <div class="rounded-3xl bg-primary p-8 text-primary-foreground">
        <h2 class="font-display text-xl font-bold tracking-tight">Prochaines sessions</h2>

        <?php if (empty($sessions)): ?>
          <p class="mt-4 text-sm text-primary-foreground/70">Aucune session programmée pour le moment.</p>
        <?php else: ?>
          <div class="mt-6 divide-y divide-white/10">
            <?php foreach ($sessions as $session): ?>
              <?php
                $chip      = Format::monthDay($session['start_date']);
                $remaining = max((int)($session['capacity'] ?? 0) - (int)($session['seats_taken'] ?? 0), 0);
                $modalities = ['presentiel' => 'Présentiel', 'en_ligne' => 'En ligne', 'hybride' => 'Hybride'];
              ?>
              <div class="flex items-center gap-4 py-4 first:pt-0 last:pb-0">
                <div class="flex size-12 shrink-0 flex-col items-center justify-center rounded-xl bg-white/10">
                  <span class="text-[10px] font-bold uppercase opacity-70"><?= e($chip['month']) ?></span>
                  <span class="font-display text-base font-bold leading-none"><?= e($chip['day']) ?></span>
                </div>
                <div class="min-w-0">
                  <p class="truncate text-sm font-bold"><?= e($session['program_title'] ?? 'Session') ?></p>
                  <div class="mt-1 flex flex-wrap items-center gap-x-3 text-xs text-primary-foreground/70">
                    <span class="text-accent"><?= $remaining ?> place<?= $remaining === 1 ? '' : 's' ?></span>
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                      </svg>
                      <?= e($session['location'] ?? '') ?>
                    </span>
                    <span class="inline-flex items-center gap-1">
                      <svg xmlns="http://www.w3.org/2000/svg" class="size-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                      </svg>
                      <?= e($modalities[$session['modality'] ?? ''] ?? ($session['modality'] ?? '')) ?>
                    </span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <a href="<?= $appUrl ?>/formations"
           class="mt-8 block rounded-full bg-accent py-3.5 text-center font-display text-sm font-bold text-accent-foreground transition-colors duration-200 hover:bg-accent/90">
          Toutes les sessions
        </a>
      </div>
    </div>

  </div>
</section>
<?php endif; ?>

<!-- Script carousel hero -->
<script>
(function () {
  const slides = document.querySelectorAll('[data-hero-slide]');
  if (!slides.length) return;
  let current = 0;
  setInterval(function () {
    slides[current].classList.remove('opacity-80');
    slides[current].classList.add('opacity-0');
    current = (current + 1) % slides.length;
    slides[current].classList.remove('opacity-0');
    slides[current].classList.add('opacity-80');
  }, 5000);
})();
</script>
