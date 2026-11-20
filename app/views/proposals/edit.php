<?php $v = fn(string $k) => old($k, $p[$k] ?? ''); ?>
<div class="narrow">
<a class="back" href="<?= e(url('/propostas/' . $p['id'])) ?>"><?= icon('arrow-left') ?><?= e($p['title']) ?></a>
<h1>Nova versão da proposta</h1>
<p class="lead-sm">Ajuste a proposta e explique o que mudou. A versão anterior fica no histórico, inclusive a imagem do "depois", para todos acompanharem a evolução.</p>
<?= partial('partials/errors', ['errors' => $errors]) ?>

<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_field() ?>

  <?php if ($openSuggestions): ?>
  <fieldset>
    <legend>Sugestões que esta versão incorpora</legend>
    <p class="hint">Quem sugeriu será avisado e a sugestão aparecerá como "Incorporada".</p>
    <?php foreach ($openSuggestions as $s): ?>
    <label class="check">
      <input type="checkbox" name="incorporated[]" value="<?= (int)$s['id'] ?>" <?= in_array((string)$s['id'], (array)old('incorporated', []), true) ? 'checked' : '' ?>>
      <span><strong><?= e($s['author_name']) ?></strong> (<?= plural((int)$s['support_count'], 'apoio', 'apoios') ?>): <?= e(excerpt($s['body'], 160)) ?></span>
    </label>
    <?php endforeach ?>
  </fieldset>
  <?php endif ?>

  <label>O que mudou nesta versão? (fica público no histórico)
    <textarea name="note" required minlength="5" maxlength="1000" rows="2" placeholder="Ex.: Troquei o piso por blocos drenantes e aumentei a área de sombra, como sugerido."><?= e(old('note')) ?></textarea>
  </label>

  <label>Título<input name="title" required minlength="5" maxlength="160" value="<?= e($v('title')) ?>"></label>
  <label>Resumo<textarea name="summary" required minlength="10" maxlength="300" rows="2"><?= e($v('summary')) ?></textarea></label>
  <label>Descrição completa<textarea name="description" required minlength="30" rows="8"><?= e($v('description')) ?></textarea></label>

  <fieldset>
    <legend>Imagens principais</legend>
    <div class="thumbs"><img src="<?= e(upload_url($p['cover_thumb'])) ?>" alt="Imagem atual do depois"></div>
    <label class="file-drop"><?= icon('image', 'icon icon-lg') ?><span>Nova imagem do "depois" (opcional; a atual vai para o histórico)</span>
      <input type="file" name="after" accept="image/*" data-compress data-preview="after-preview"></label>
    <div id="after-preview" class="thumbs"></div>
    <label class="file-drop"><?= icon('image', 'icon icon-lg') ?><span>Nova imagem do "antes" (opcional)</span>
      <input type="file" name="before" accept="image/*" data-compress data-preview="before-preview"></label>
    <div id="before-preview" class="thumbs"></div>
  </fieldset>

  <fieldset>
    <legend>Material complementar</legend>
    <?php if ($files): ?>
    <p class="label">Marque para remover:</p>
    <?php foreach ($files as $f): ?>
      <label class="check"><input type="checkbox" name="remove_files[]" value="<?= (int)$f['id'] ?>"><span><?= $f['kind'] === 'pdf' ? icon('file') : icon('image') ?> <?= e($f['original_name'] ?: basename($f['file'])) ?></span></label>
    <?php endforeach ?>
    <?php endif ?>
    <label class="file-drop"><?= icon('layers', 'icon icon-lg') ?><span>Adicionar imagens</span><input type="file" name="gallery[]" accept="image/*" multiple data-compress data-max="8" data-preview="gallery-preview"></label>
    <div id="gallery-preview" class="thumbs"></div>
    <label class="file-drop"><?= icon('file', 'icon icon-lg') ?><span>Adicionar PDFs</span><input type="file" name="pdfs[]" accept="application/pdf" multiple data-max="5" data-preview="pdf-preview"></label>
    <div id="pdf-preview" class="file-list"></div>
  </fieldset>

  <div class="row-2">
    <label>Custo estimado (R$)<input name="estimated_cost" inputmode="decimal" value="<?= e(old('estimated_cost', $p['estimated_cost'] !== null ? number_format((float)$p['estimated_cost'], 2, ',', '.') : '')) ?>"></label>
    <label>Como chegou nesse valor?<input name="cost_notes" maxlength="255" value="<?= e($v('cost_notes')) ?>"></label>
  </div>
  <p class="hint">A licença (<?= e(licenses()[$p['license']][0] ?? $p['license']) ?>) não pode ser alterada: quem já usou o material sob essa licença continua tendo esse direito.</p>

  <button class="btn btn-primary btn-lg btn-block">Publicar nova versão</button>
</form>
</div>
