<?php
$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');
$year   = date('Y');
?>
<footer class="relative bg-footer text-footer-foreground">
  <div class="accent-gradient h-1 w-full" aria-hidden="true"></div>
  <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-12 sm:grid-cols-2 lg:grid-cols-4">

    <div>
      <p class="font-display text-lg font-bold">CaNDI</p>
      <p class="mt-2 text-sm text-footer-muted">
        Formation professionnelle, progiciels de gestion et services numériques en Guinée.
      </p>
    </div>

    <div>
      <p class="text-sm font-semibold">Nos agences</p>
      <ul class="mt-2 space-y-1 text-sm text-footer-muted">
        <li>Siège — Kaloum, Conakry</li>
        <li>Agence — Centre-ville, Kankan</li>
      </ul>
    </div>

    <div>
      <p class="text-sm font-semibold">Contact</p>
      <ul class="mt-2 space-y-1 text-sm text-footer-muted">
        <li>+224 626 01 17 17</li>
        <li>+224 625 29 54 56</li>
        <li>+224 622 98 44 45</li>
        <li>info@candi-group.com</li>
      </ul>
    </div>

    <div>
      <p class="text-sm font-semibold">Liens</p>
      <ul class="mt-2 space-y-1 text-sm text-footer-muted">
        <li><a href="<?= $appUrl ?>/a-propos" class="transition-colors hover:text-footer-foreground">À propos de nous</a></li>
        <li><a href="<?= $appUrl ?>/formations" class="transition-colors hover:text-footer-foreground">Catalogue des formations</a></li>
        <li><a href="<?= $appUrl ?>/produits-services" class="transition-colors hover:text-footer-foreground">Nos produits et services</a></li>
        <li><a href="<?= $appUrl ?>/actualites" class="transition-colors hover:text-footer-foreground">Actualités</a></li>
        <li><a href="<?= $appUrl ?>/contact" class="transition-colors hover:text-footer-foreground">Nous écrire</a></li>
      </ul>
    </div>

  </div>
  <div class="border-t border-footer-border py-4 text-center text-xs text-footer-muted">
    © <?= $year ?> CaNDI — Tous droits réservés.
  </div>
</footer>
