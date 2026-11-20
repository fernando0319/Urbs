<div class="page-head">
  <div>
    <h1>Projetos comunitários</h1>
    <p class="muted">Propostas que a comunidade decidiu tirar do papel: orçamento, autorização, contribuições e prestação de contas, tudo à vista.</p>
  </div>
</div>
<div class="flash flash-info">
  A plataforma <strong>não recebe nem administra dinheiro</strong>: cada projeto tem uma organização responsável, verificada pela moderação.
  Para começar um projeto, abra uma proposta e use "Transformar em projeto comunitário".
</div>
<nav class="tabs" aria-label="Filtrar por status">
  <a href="<?= e(url('/projetos')) ?>" <?= input('status') === '' ? 'aria-current="page"' : '' ?>>Todos</a>
  <?php foreach (project_statuses() as $k => $label): ?>
    <a href="<?= e(url('/projetos', ['status' => $k])) ?>" <?= input('status') === $k ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
  <?php endforeach ?>
</nav>
<?php if ($projects): ?>
  <div class="grid grid-3"><?php foreach ($projects as $pj) echo partial('partials/project_card', ['pj' => $pj]); ?></div>
<?php else: ?>
  <div class="empty">
    <?= icon('users', 'icon icon-xl') ?>
    <p>Nenhum projeto comunitário ainda. Os melhores para começar são pequenos: uma horta, um jardim, bancos numa praça.</p>
    <a class="btn btn-primary" href="<?= e(url('/propostas')) ?>">Ver propostas</a>
  </div>
<?php endif ?>
