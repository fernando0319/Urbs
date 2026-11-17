<div class="auth">
  <h1>Recuperar senha</h1>
  <?php if ($sent): ?>
    <div class="flash flash-ok">Se houver uma conta com esse e-mail, você vai receber um link para criar uma nova senha.</div>
  <?php else: ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>E-mail da conta<input type="email" name="email" required autocomplete="email"></label>
    <button class="btn btn-primary btn-block">Enviar link</button>
  </form>
  <?php endif ?>
  <p><a href="<?= e(url('/entrar')) ?>">Voltar para entrar</a></p>
</div>
