<div class="auth">
  <h1>Criar nova senha</h1>
  <?php if ($error): ?><div class="flash flash-warn"><?= e($error) ?></div><?php endif ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="t" value="<?= e($token) ?>">
    <label>Nova senha (mínimo 8 caracteres)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-primary btn-block">Salvar senha</button>
  </form>
</div>
