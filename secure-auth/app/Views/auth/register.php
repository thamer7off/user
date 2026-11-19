<h1>Create your account</h1>
<p class="lede">It takes less than a minute.</p>

<?php require APP_PATH . '/Views/partials/social.php'; ?>

<form method="post" action="<?= e(url('/register')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="name">Name</label>
    <input id="name" name="name" type="text" autocomplete="name" maxlength="100" value="<?= e($old['name'] ?? '') ?>" required>
  </div>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="email" maxlength="255" value="<?= e($old['email'] ?? '') ?>" required>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <div class="pw">
      <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="pw-hint" required>
      <button type="button" class="reveal" data-reveal aria-label="Show password">Show</button>
    </div>
    <p class="hint" id="pw-hint">Use 8 to 72 characters.</p>
  </div>
  <button class="btn btn-primary" type="submit">Create account</button>
</form>

<p class="alt">Already have an account? <a href="<?= e(url('/login')) ?>">Sign in</a></p>
