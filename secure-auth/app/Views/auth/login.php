<h1>Welcome back</h1>
<p class="lede">Sign in to continue to <?= e(app_name()) ?>.</p>

<?php require APP_PATH . '/Views/partials/social.php'; ?>

<form method="post" action="<?= e(url('/login')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="username" value="<?= e($old['email'] ?? '') ?>" required autofocus>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <div class="pw">
      <input id="password" name="password" type="password" autocomplete="current-password" required>
      <button type="button" class="reveal" data-reveal aria-label="Show password">Show</button>
    </div>
  </div>
  <div class="row">
    <label class="check"><input type="checkbox" name="remember" value="1"> Keep me signed in for 30 days</label>
  </div>
  <button class="btn btn-primary" type="submit">Sign in</button>
  <p class="forgot"><a href="<?= e(url('/forgot-password')) ?>">Forgot your password?</a></p>
</form>

<p class="alt">New here? <a href="<?= e(url('/register')) ?>">Create an account</a></p>
