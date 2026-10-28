<?php
use function App\Core\e;
use App\Helpers\Format;

$appUrl = rtrim(\App\Core\env('APP_URL', ''), '/');

// Résolution vidéo (YouTube/Vimeo embed ou video directe)
$videoEmbed = null;
$videoSrc   = null;
if (!empty($post['video_url'])) {
    $url = $post['video_url'];
    if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_\-]+)/', $url, $m)) {
        $videoEmbed = 'https://www.youtube.com/embed/' . $m[1];
    } elseif (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
        $videoEmbed = 'https://player.vimeo.com/video/' . $m[1];
    } else {
        $videoSrc = $url;
    }
}

// Paragraphes
$paragraphs = preg_split('/\n{2,}/', $post['content'] ?? '') ?: [];
?>

<article class="mx-auto w-full max-w-3xl px-4 py-12">
  <a href="<?= $appUrl ?>/actualites"
     class="inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
    </svg>
    Toutes les actualités
  </a>

  <div class="mt-6 flex items-center gap-3">
    <?php if (!empty($post['category'])): ?>
      <span class="inline-flex items-center rounded-full border border-secondary-foreground/20 bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground">
        <?= e($post['category']) ?>
      </span>
    <?php endif; ?>
    <span class="text-sm text-muted-foreground">
      <?= Format::date($post['published_at'] ?? $post['created_at'] ?? null) ?>
    </span>
  </div>

  <h1 class="mt-3 font-display text-3xl font-bold leading-tight sm:text-4xl"><?= e($post['title']) ?></h1>

  <?php if (!empty($post['excerpt'])): ?>
    <p class="mt-3 text-lg text-muted-foreground"><?= e($post['excerpt']) ?></p>
  <?php endif; ?>

  <?php if (!empty($post['cover_url'])): ?>
    <img src="<?= e(\App\Core\asset_url($post['cover_url'])) ?>" alt="<?= e($post['title']) ?>"
         class="mt-6 w-full rounded-xl object-cover">
  <?php endif; ?>

  <?php if ($videoEmbed): ?>
    <div class="mt-6 aspect-video w-full overflow-hidden rounded-xl">
      <iframe src="<?= e($videoEmbed) ?>" title="<?= e($post['title']) ?>"
              class="h-full w-full"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; picture-in-picture"
              allowfullscreen></iframe>
    </div>
  <?php elseif ($videoSrc): ?>
    <video src="<?= e($videoSrc) ?>" controls class="mt-6 aspect-video w-full rounded-xl bg-black"></video>
  <?php endif; ?>

  <div class="mt-8 space-y-4 text-base leading-relaxed">
    <?php foreach ($paragraphs as $paragraph): ?>
      <p class="whitespace-pre-line"><?= e(trim($paragraph)) ?></p>
    <?php endforeach; ?>
  </div>
</article>
