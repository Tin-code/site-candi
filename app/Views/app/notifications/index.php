<?php
use function App\Core\e;
use App\Helpers\Format;
$base = rtrim(\App\Core\env('APP_URL', ''), '/');

$levelConfig = [
    'info'    => ['label' => 'Info',    'cls' => 'bg-primary/10 text-primary'],
    'succes'  => ['label' => 'Succès',  'cls' => 'bg-success/15 text-success'],
    'alerte'  => ['label' => 'Alerte',  'cls' => 'bg-destructive/10 text-destructive'],
    'warning' => ['label' => 'Avertissement', 'cls' => 'bg-amber-500/10 text-amber-600'],
];

$moduleIcons = [
    'inscriptions' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"/>',
    'paiements'    => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z"/>',
    'taches'       => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
];
$defaultIcon = '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/>';

$notifications = $notifications ?? [];
$unreadCount   = $unreadCount   ?? 0;
?>

<div class="space-y-6">

  <!-- En-tête -->
  <div class="flex flex-wrap items-center justify-between gap-3">
    <div>
      <h1 class="font-display text-2xl font-bold">Notifications</h1>
      <p class="text-sm text-muted-foreground">Vos alertes et rappels liés aux inscriptions, paiements et tâches.</p>
    </div>
    <div class="flex items-center gap-2">
      <?php if ($unreadCount > 0): ?>
        <span class="inline-flex items-center rounded-full bg-primary px-2.5 py-0.5 text-xs font-semibold text-primary-foreground">
          <?= $unreadCount ?> non lue<?= $unreadCount > 1 ? 's' : '' ?>
        </span>
        <form method="POST" action="<?= $base ?>/app/notifications/lire-tout">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit"
                  class="inline-flex items-center gap-2 rounded-lg border border-border px-3 py-1.5 text-sm font-medium hover:bg-muted">
            <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
            </svg>
            Tout marquer comme lu
          </button>
        </form>
      <?php endif ?>
    </div>
  </div>

  <?php if (!empty($success)): ?>
    <div class="rounded-lg border border-success/20 bg-success/10 px-4 py-3 text-sm text-success"><?= e($success) ?></div>
  <?php endif ?>

  <?php if (empty($notifications)): ?>
    <!-- État vide -->
    <div class="rounded-xl border border-border bg-card px-6 py-14 text-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto size-8 text-muted-foreground/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9.143 17.082a24.248 24.248 0 003.844.148m-3.844-.148a23.856 23.856 0 01-5.455-1.31 8.964 8.964 0 002.3-5.542m3.155 6.852a3 3 0 005.667 1.97m1.965-2.277L21 21m-4.225-4.225a8.964 8.964 0 003.089-6.03 8.907 8.907 0 00-.18-1.8M7.5 3A6 6 0 016 8.82m.53-4.82A6.018 6.018 0 0112 3a6 6 0 016 6v.75a8.964 8.964 0 01-2.3 5.541M3 3l18 18"/>
      </svg>
      <p class="mt-4 font-medium">Aucune notification</p>
      <p class="mt-1 text-sm text-muted-foreground">Vous serez prévenu ici dès qu'une action vous concerne.</p>
    </div>

  <?php else: ?>
    <div class="grid gap-3">
      <?php foreach ($notifications as $notif): ?>
        <?php
        $cfg    = $levelConfig[$notif['level']] ?? $levelConfig['info'];
        $isRead = (bool) $notif['is_read'];
        $icon   = $moduleIcons[$notif['module'] ?? ''] ?? $defaultIcon;
        ?>
        <div class="rounded-xl border <?= $isRead ? 'border-border bg-card opacity-70' : 'border-primary/20 bg-card shadow-sm' ?> transition-opacity">
          <div class="flex flex-wrap items-start gap-4 p-4">

            <!-- Icône -->
            <div class="shrink-0 mt-0.5 rounded-full <?= $isRead ? 'bg-muted' : 'bg-primary/10' ?> p-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="size-4 <?= $isRead ? 'text-muted-foreground' : 'text-primary' ?>"
                   fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <?= $icon ?>
              </svg>
            </div>

            <!-- Contenu -->
            <div class="min-w-0 flex-1 space-y-1">
              <div class="flex flex-wrap items-center gap-2">
                <p class="font-medium <?= $isRead ? 'text-foreground/70' : 'text-foreground' ?>"><?= e($notif['title']) ?></p>
                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium <?= $cfg['cls'] ?>">
                  <?= $cfg['label'] ?>
                </span>
                <?php if ($notif['module']): ?>
                  <span class="rounded-full border border-border px-2 py-0.5 text-xs text-muted-foreground capitalize">
                    <?= e($notif['module']) ?>
                  </span>
                <?php endif ?>
              </div>
              <?php if ($notif['body']): ?>
                <p class="text-sm text-muted-foreground"><?= e($notif['body']) ?></p>
              <?php endif ?>
              <p class="text-xs text-muted-foreground"><?= Format::dateTime($notif['created_at']) ?></p>
            </div>

            <!-- Actions -->
            <div class="flex shrink-0 items-center gap-2">
              <?php if ($notif['link']): ?>
                <a href="<?= e($base . $notif['link']) ?>"
                   class="rounded-lg border border-border px-3 py-1.5 text-xs font-medium hover:bg-muted">
                  Ouvrir
                </a>
              <?php endif ?>
              <?php if (!$isRead): ?>
                <form method="POST" action="<?= $base ?>/app/notifications/<?= e($notif['id']) ?>/lire">
                  <?= \App\Core\Csrf::field() ?>
                  <button type="submit" title="Marquer comme lu"
                          class="rounded-lg border border-border p-1.5 hover:bg-muted text-muted-foreground">
                    <svg xmlns="http://www.w3.org/2000/svg" class="size-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                      <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                  </button>
                </form>
              <?php endif ?>
            </div>

          </div>
        </div>
      <?php endforeach ?>
    </div>

    <?php if ($unreadCount === 0 && count($notifications) > 0): ?>
      <div class="text-center">
        <form method="POST" action="<?= $base ?>/app/notifications/supprimer-lues"
              onsubmit="return confirm('Supprimer toutes les notifications lues ?')">
          <?= \App\Core\Csrf::field() ?>
          <button type="submit" class="text-xs text-muted-foreground hover:text-destructive hover:underline">
            Supprimer les notifications lues
          </button>
        </form>
      </div>
    <?php endif ?>

  <?php endif ?>

</div>
