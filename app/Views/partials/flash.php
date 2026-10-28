<?php
use App\Core\Session;
use function App\Core\e;

$success = Session::flash('success');
$error   = Session::flash('error');
$errors  = Session::flash('errors') ?? [];
?>
<?php if ($success): ?>
  <div role="alert" class="rounded-md border border-success/30 bg-success/10 px-4 py-3 text-sm text-success-foreground">
    <?= e($success) ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div role="alert" class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
    <?= e($error) ?>
  </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div role="alert" class="rounded-md border border-destructive/30 bg-destructive/10 px-4 py-3 text-sm text-destructive">
    <ul class="list-inside list-disc space-y-1">
      <?php foreach ($errors as $field => $fieldErrors): ?>
        <?php foreach ((array) $fieldErrors as $msg): ?>
          <li><?= e($msg) ?></li>
        <?php endforeach; ?>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>
