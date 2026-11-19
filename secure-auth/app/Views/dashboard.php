<?php
$initial = mb_strtoupper(mb_substr($user['name'], 0, 1));
$labels = ['google' => 'Google', 'github' => 'GitHub', 'discord' => 'Discord'];
?>
<h1>You're signed in</h1>
<p class="lede">This page is only visible to signed-in users.</p>

<div class="profile">
  <?php if (!empty($user['avatar_url'])): ?>
    <img class="avatar" src="<?= e($user['avatar_url']) ?>" alt="" width="56" height="56">
  <?php else: ?>
    <div class="avatar" aria-hidden="true"><?= e($initial) ?></div>
  <?php endif; ?>
  <div>
    <div class="profile-name"><?= e($user['name']) ?></div>
    <div class="profile-email"><?= e($user['email']) ?></div>
  </div>
</div>

<h3 class="subhead">Ways to sign in</h3>
<ul class="chips">
  <?php if (!empty($user['password_hash'])): ?><li>Email and password</li><?php endif; ?>
  <?php foreach ($providers as $provider): ?><li><?= e($labels[$provider] ?? ucfirst($provider)) ?></li><?php endforeach; ?>
</ul>

<form method="post" action="<?= e(url('/logout')) ?>">
  <?= csrf_field() ?>
  <button class="btn btn-secondary" type="submit">Sign out</button>
</form>
