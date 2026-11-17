<div class="page-head">
  <div>
    <h1>Acervo de propostas</h1>
    <p class="muted">Todas as ideias e projetos enviados para <?= e(setting('city_name')) ?>, com a autoria preservada.</p>
  </div>
</div>
<form class="filters" method="get" action="<?= e(url('/propostas')) ?>">
  <?php if (!config('pretty_urls', true)): ?><input type="hidden" name="r" value="/propostas"><?php endif ?>
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Buscar propostas" aria-label="Buscar">
  <select name="category" aria-label="Categoria">
    <option value="">Categoria</option>
    <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= input_int('category') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
  </select>
  <select name="sort" aria-label="Ordenar">
    <option value="">Mais recentes</option>
    <option value="popular" <?= input('sort') === 'popular' ? 'selected' : '' ?>>Mais preferidas</option>
  </select>
  <label class="check check-inline"><input type="checkbox" name="verified" value="1" <?= input('verified') === '1' ? 'checked' : '' ?>><span>Só profissionais verificados</span></label>
  <button class="btn"><?= icon('search') ?>Filtrar</button>
</form>
<p class="muted"><?= plural($total, 'proposta', 'propostas') ?></p>
<?php if ($proposals): ?>
  <div class="grid grid-3"><?php foreach ($proposals as $p) echo partial('partials/proposal_card', ['p' => $p]); ?></div>
<?php else: ?>
  <div class="empty">
    <?= icon('layers', 'icon icon-xl') ?>
    <p>Nenhuma proposta ainda. Escolha uma ocorrência e envie a primeira.</p>
    <a class="btn btn-primary" href="<?= e(url('/ocorrencias')) ?>">Ver ocorrências</a>
  </div>
<?php endif ?>
<?= partial('partials/pagination', ['page' => $page, 'pages' => $pages, 'path' => '/propostas']) ?>
