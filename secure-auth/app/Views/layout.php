<?php
use App\Core\Session;

$flash = Session::pullFlash();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="color-scheme" content="light dark">
  <title><?= e($title ?? 'Sign in') ?> · <?= e(app_name()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
  <script src="<?= e(asset('js/app.js')) ?>" defer></script>
</head>
<body>
<div class="shell">
  <aside class="brand">
    <div class="wordmark">
      <svg width="28" height="28" viewBox="0 0 28 28" aria-hidden="true"><rect width="28" height="28" rx="8" fill="#2F4BFF"/><circle cx="14" cy="12" r="4" fill="#fff"/><path d="M12.2 15.5h3.6l.8 5.5h-5.2z" fill="#fff"/></svg>
      <span><?= e(app_name()) ?></span>
    </div>
    <div class="brand-copy">
      <h2>Pick up where you left off.</h2>
      <p>Sign in on any device and your work is right where you left it.</p>
    </div>
  </aside>

  <main class="panel">
    <div class="panel-inner">
      <?php foreach (['error' => 'alert', 'success' => 'status'] as $type => $role): ?>
        <?php if (!empty($flash[$type])): ?>
          <div class="alert alert-<?= e($type) ?>" role="<?= e($role) ?>">
            <?php if (count($flash[$type]) === 1): ?>
              <?= e($flash[$type][0]) ?>
            <?php else: ?>
              <ul><?php foreach ($flash[$type] as $message): ?><li><?= e($message) ?></li><?php endforeach; ?></ul>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>

      <?= $content ?>
    </div>
  </main>
</div>
</body>
</html>
