<?php
/**
 * Partial réutilisable : sélecteur d'image avec prévisualisation
 *
 * Variables attendues :
 *   $fieldName  string  nom du champ file  (ex. 'cover_image')
 *   $currentUrl string  valeur actuelle (chemin relatif ou URL absolue)
 *   $label      string  libellé
 *   $hint       string  texte d'aide (optionnel)
 */
use function App\Core\e;
$base       = rtrim(\App\Core\env('APP_URL', ''), '/');
$pickerId   = 'imgpick-' . preg_replace('/[^a-z0-9]/', '', strtolower($fieldName ?? 'img'));
$currentUrl = $currentUrl ?? '';
$label      = $label ?? 'Image';
$hint       = $hint ?? 'JPG, PNG ou WebP — 2 Mo max.';
$previewSrc = '';
if ($currentUrl !== '') {
    $previewSrc = str_starts_with($currentUrl, 'http')
        ? $currentUrl
        : $base . '/' . ltrim($currentUrl, '/');
}
?>
<div class="grid gap-1.5">
  <span class="text-sm font-medium"><?= e($label) ?></span>

  <div class="flex items-start gap-4">

    <!-- Zone de prévisualisation -->
    <div id="<?= $pickerId ?>-wrap"
         style="width:70px;height:70px"
         class="relative flex shrink-0 items-center justify-center overflow-hidden rounded-lg border-2 border-dashed border-border bg-muted transition-colors">
      <?php if ($previewSrc): ?>
        <img id="<?= $pickerId ?>-preview"
             src="<?= e($previewSrc) ?>"
             alt="aperçu"
             class="size-full object-cover">
      <?php else: ?>
        <img id="<?= $pickerId ?>-preview"
             src=""
             alt="aperçu"
             style="display:none;width:100%;height:100%;object-fit:cover;">
        <svg id="<?= $pickerId ?>-icon"
             xmlns="http://www.w3.org/2000/svg"
             class="size-7 text-muted-foreground/30"
             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z"/>
        </svg>
      <?php endif ?>
    </div>

    <!-- Contrôles -->
    <div class="flex flex-1 flex-col gap-2">

      <button type="button"
              id="<?= $pickerId ?>-btn"
              class="inline-flex w-fit items-center gap-2 rounded-lg border border-border bg-background px-3 py-2 text-sm font-medium hover:bg-muted transition-colors">
        <svg xmlns="http://www.w3.org/2000/svg" class="size-4 shrink-0" fill="none" viewBox="0 0 24 24"
             stroke="currentColor" stroke-width="1.5">
          <path stroke-linecap="round" stroke-linejoin="round"
                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/>
        </svg>
        Choisir une image
      </button>

      <p id="<?= $pickerId ?>-filename" class="text-xs text-muted-foreground">
        <?= $currentUrl ? e(basename($currentUrl)) : 'Aucun fichier sélectionné' ?>
      </p>

      <?php if ($currentUrl): ?>
        <label class="inline-flex cursor-pointer items-center gap-1.5" id="<?= $pickerId ?>-removelabel">
          <input type="checkbox" id="<?= $pickerId ?>-removechk"
                 name="<?= e($fieldName) ?>_remove" value="1" class="size-3.5 rounded">
          <span class="text-xs text-muted-foreground">Supprimer l'image actuelle</span>
        </label>
      <?php endif ?>

      <p class="text-xs text-muted-foreground/60"><?= e($hint) ?></p>
    </div>
  </div>

  <!-- Champ file caché -->
  <input type="file"
         id="<?= $pickerId ?>-input"
         name="<?= e($fieldName) ?>"
         accept="image/jpeg,image/png,image/webp,image/gif"
         style="display:none">
</div>

<script>
(function () {
  var id      = <?= json_encode($pickerId) ?>;
  var btn     = document.getElementById(id + '-btn');
  var fileIn  = document.getElementById(id + '-input');
  var preview = document.getElementById(id + '-preview');
  var icon    = document.getElementById(id + '-icon');
  var fname   = document.getElementById(id + '-filename');
  var remLbl  = document.getElementById(id + '-removelabel');
  var remChk  = document.getElementById(id + '-removechk');

  if (btn && fileIn) {
    btn.addEventListener('click', function () { fileIn.click(); });
  }

  if (fileIn) {
    fileIn.addEventListener('change', function () {
      var file = this.files && this.files[0];
      if (!file) return;

      var reader = new FileReader();
      reader.onload = function (ev) {
        preview.src = ev.target.result;
        preview.style.display = 'block';
        if (icon) icon.style.display = 'none';
      };
      reader.readAsDataURL(file);

      fname.textContent = file.name;
      if (remLbl) remLbl.style.display = 'none';
      if (remChk) remChk.checked = false;
    });
  }
})();
</script>
