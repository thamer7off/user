<h1>Choose a new password</h1>
<p class="lede">Pick something you don't use anywhere else.</p>

<form method="post" action="<?= e(url('/reset-password')) ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="token" value="<?= e($token) ?>">
  <div class="field">
    <label for="password">New password</label>
    <div class="pw">
      <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" maxlength="72" aria-describedby="pw-hint" required autofocus>
      <button type="button" class="reveal" data-reveal aria-label="Show password">Show</button>
    </div>
    <p class="hint" id="pw-hint">Use 8 to 72 characters.</p>
  </div>
  <div class="field">
    <label for="password_confirm">Confirm new password</label>
    <input id="password_confirm" name="password_confirm" type="password" autocomplete="new-password" minlength="8" maxlength="72" required>
  </div>
  <button class="btn btn-primary" type="submit">Update password</button>
</form>
