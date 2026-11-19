<h1>Reset your password</h1>
<p class="lede">Enter the email you signed up with and we'll send you a link to choose a new password.</p>

<form method="post" action="<?= e(url('/forgot-password')) ?>">
  <?= csrf_field() ?>
  <div class="field">
    <label for="email">Email</label>
    <input id="email" name="email" type="email" autocomplete="email" maxlength="255" required autofocus>
  </div>
  <button class="btn btn-primary" type="submit">Send reset link</button>
</form>

<p class="alt"><a href="<?= e(url('/login')) ?>">Back to sign in</a></p>
