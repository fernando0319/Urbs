<div class="empty empty-page">
  <p class="error-code"><?= (int)($code ?? 500) ?></p>
  <h1><?= e($title ?? 'Erro') ?></h1>
  <p><?= e($message ?? '') ?></p>
  <a class="btn btn-primary" href="<?= e(url('/')) ?>">Ir para o início</a>
</div>
