<h1><?= e($title ?? 'Something went wrong') ?></h1>
<p class="lede"><?= e($message ?? 'Please try again in a moment.') ?></p>
<a class="btn btn-primary" href="<?= e(url('/')) ?>">Go to the start page</a>
