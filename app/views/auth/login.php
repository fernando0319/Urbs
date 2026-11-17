<div class="auth">
  <h1>Entrar</h1>
  <?php if ($error): ?><div class="flash flash-warn" role="alert"><?= e($error) ?></div><?php endif ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>E-mail<input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>"></label>
    <label>Senha<input type="password" name="password" required autocomplete="current-password"></label>
    <button class="btn btn-primary btn-block btn-lg">Entrar</button>
  </form>
  <p><a href="<?= e(url('/esqueci-senha')) ?>">Esqueci minha senha</a></p>
  <p>Ainda não participa? <a href="<?= e(url('/cadastro')) ?>">Crie sua conta</a></p>
</div>
