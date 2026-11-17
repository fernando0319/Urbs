<div class="page-head">
  <h1>Ocorrências e sugestões</h1>
  <a class="btn btn-primary" href="<?= e(url('/ocorrencias/nova')) ?>"><?= icon('plus') ?>Registrar</a>
</div>
<form class="filters" method="get" action="<?= e(url('/ocorrencias')) ?>">
  <?php if (!config('pretty_urls', true)): ?><input type="hidden" name="r" value="/ocorrencias"><?php endif ?>
  <label class="sr-only" for="q">Buscar</label>
  <input id="q" type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Buscar por título, descrição ou endereço">
  <select name="category" aria-label="Categoria">
    <option value="">Categoria</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= input_int('category') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
  </select>
  <select name="status" aria-label="Status">
    <option value="">Status</option>
    <?php foreach (issue_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= input('status') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
  </select>
  <select name="sort" aria-label="Ordenar">
    <option value="">Mais recentes</option>
    <option value="supports" <?= input('sort') === 'supports' ? 'selected' : '' ?>>Mais apoiadas</option>
    <option value="proposals" <?= input('sort') === 'proposals' ? 'selected' : '' ?>>Mais propostas</option>
  </select>
  <button class="btn"><?= icon('search') ?>Filtrar</button>
</form>
<p class="muted"><?= plural($total, 'resultado', 'resultados') ?></p>
<?php if ($issues): ?>
  <div class="grid grid-2"><?php foreach ($issues as $i) echo partial('partials/issue_card', ['i' => $i]); ?></div>
<?php else: ?>
  <div class="empty"><?= icon('search', 'icon icon-xl') ?><p>Nenhuma ocorrência encontrada com esses filtros.</p></div>
<?php endif ?>
<?= partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'path' => '/ocorrencias']) ?>
