<?php if (!empty($providers)): ?>
  <div class="social">
    <?php foreach ($providers as $key => $provider): ?>
      <a class="btn btn-social js-social" href="<?= e(url('/auth/' . $key)) ?>">
        <?= provider_icon($key) ?>
        <span>Continue with <?= e($provider['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="divider"><span>or use your email</span></div>
<?php endif; ?>
