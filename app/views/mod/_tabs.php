<div class="page-head"><h1><?= icon('shield') ?>Moderação</h1><a class="btn btn-ghost btn-sm" href="<?= e(url('/transparencia')) ?>">Registro público</a></div>
<nav class="tabs" aria-label="Moderação">
  <a href="<?= e(url('/moderacao')) ?>" <?= $tab === 'queue' ? 'aria-current="page"' : '' ?>>Fila<?php if ($counts['queue']): ?> <span class="count"><?= $counts['queue'] ?></span><?php endif ?></a>
  <a href="<?= e(url('/moderacao/denuncias')) ?>" <?= $tab === 'reports' ? 'aria-current="page"' : '' ?>>Denúncias<?php if ($counts['reports']): ?> <span class="count"><?= $counts['reports'] ?></span><?php endif ?></a>
  <a href="<?= e(url('/moderacao/verificacoes')) ?>" <?= $tab === 'verifications' ? 'aria-current="page"' : '' ?>>Verificações<?php if ($counts['verifications']): ?> <span class="count"><?= $counts['verifications'] ?></span><?php endif ?></a>
  <a href="<?= e(url('/moderacao/usuarios')) ?>" <?= $tab === 'users' ? 'aria-current="page"' : '' ?>>Usuários</a>
  <?php if (is_admin()): ?><a href="<?= e(url('/moderacao/configuracoes')) ?>" <?= $tab === 'settings' ? 'aria-current="page"' : '' ?>>Configurações</a><?php endif ?>
</nav>
