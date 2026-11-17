<div class="auth">
  <h1>Criar conta</h1>
  <p class="muted">Participar é gratuito. Seu e-mail não aparece para ninguém.</p>
  <?= partial('partials/errors', ['errors' => $errors]) ?>
  <form method="post" class="form">
    <?= csrf_field() ?>
    <label>Nome (aparece nas suas publicações)<input name="name" required minlength="2" maxlength="120" autocomplete="name" value="<?= e(old('name')) ?>"></label>
    <label>E-mail<input type="email" name="email" required autocomplete="email" value="<?= e(old('email')) ?>"></label>
    <label>Senha (mínimo 8 caracteres)<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <label>Como você se identifica?
      <select name="profile_type" required>
        <?php foreach (profile_types() as $k => $label): ?><option value="<?= e($k) ?>" <?= old('profile_type', 'citizen') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
      </select>
    </label>
    <div class="hp" aria-hidden="true"><label>Não preencha<input name="website" tabindex="-1" autocomplete="off"></label></div>
    <label class="check">
      <input type="checkbox" name="consent" value="1" required>
      <span>Li e aceito os <a href="<?= e(url('/termos')) ?>" target="_blank">termos de uso</a>, as <a href="<?= e(url('/regras')) ?>" target="_blank">regras</a> e a <a href="<?= e(url('/privacidade')) ?>" target="_blank">política de privacidade</a>.</span>
    </label>
    <button class="btn btn-primary btn-block btn-lg">Criar conta</button>
  </form>
  <p>Já tem conta? <a href="<?= e(url('/entrar')) ?>">Entrar</a></p>
</div>
