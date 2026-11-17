<?php if ($pages > 1): $q = $_GET; unset($q['r']); ?>
<nav class="pagination" aria-label="Paginação">
  <?php if ($page > 1): ?><a class="btn btn-ghost btn-sm" href="<?= e(url($path, ['page' => $page - 1] + $q)) ?>">Anterior</a><?php endif ?>
  <span>Página <?= $page ?> de <?= $pages ?></span>
  <?php if ($page < $pages): ?><a class="btn btn-ghost btn-sm" href="<?= e(url($path, ['page' => $page + 1] + $q)) ?>">Próxima</a><?php endif ?>
</nav>
<?php endif ?>
