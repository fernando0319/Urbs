<div class="narrow">
<a class="back" href="<?= e(url('/ocorrencias/' . $issue['id'])) ?>"><?= icon('arrow-left') ?><?= e($issue['title']) ?></a>
<h1>Enviar proposta</h1>
<p class="lead-sm">Mostre como este lugar poderia ficar. Vale projeto técnico, croqui à mão, ilustração, render ou fotomontagem. Você não precisa ser profissional: o selo de profissional verificado só acrescenta credibilidade.</p>
<?= partial('partials/errors', ['errors' => $errors]) ?>

<form method="post" enctype="multipart/form-data" class="form">
  <?= csrf_field() ?>

  <label>Título da proposta
    <input name="title" required minlength="5" maxlength="160" value="<?= e(old('title')) ?>" placeholder="Ex.: Praça com jardim de chuva e bancos sombreados">
  </label>
  <label>Resumo (até 300 caracteres)
    <textarea name="summary" required minlength="10" maxlength="300" rows="2" data-counter placeholder="A ideia em uma ou duas frases."><?= e(old('summary')) ?></textarea>
  </label>
  <label>Descrição completa
    <textarea name="description" required minlength="30" maxlength="20000" rows="8" placeholder="O que muda, por que, materiais, etapas, quem se beneficia…"><?= e(old('description')) ?></textarea>
  </label>

  <fieldset>
    <legend>Antes e depois</legend>
    <p class="hint">As duas imagens aparecem num controle deslizante. Use o mesmo enquadramento para o efeito ficar bom.</p>
    <?php if ($photos): ?>
    <p class="label">"Antes": usar uma foto da ocorrência</p>
    <div class="choice-grid choice-photos">
      <label class="choice choice-photo"><input type="radio" name="before_photo" value="0" <?= (int)old('before_photo', 0) === 0 ? 'checked' : '' ?>><span class="choice-body"><small>Nenhuma / enviar outra</small></span></label>
      <?php foreach ($photos as $ph): ?>
      <label class="choice choice-photo"><input type="radio" name="before_photo" value="<?= (int)$ph['id'] ?>" <?= (int)old('before_photo') === (int)$ph['id'] ? 'checked' : '' ?>><span class="choice-body"><img src="<?= e(upload_url($ph['thumb'])) ?>" alt=""></span></label>
      <?php endforeach ?>
    </div>
    <?php endif ?>
    <label class="file-drop">
      <?= icon('image', 'icon icon-lg') ?><span>Imagem do "antes" (opcional<?= $photos ? ', substitui a foto escolhida' : '' ?>)</span>
      <input type="file" name="before" accept="image/*" data-compress data-preview="before-preview">
    </label>
    <div id="before-preview" class="thumbs"></div>
    <label class="file-drop file-drop-strong">
      <?= icon('image', 'icon icon-lg') ?><span><strong>Imagem do "depois" (obrigatória)</strong></span>
      <input type="file" name="after" accept="image/*" required data-compress data-preview="after-preview">
    </label>
    <div id="after-preview" class="thumbs"></div>
  </fieldset>

  <fieldset>
    <legend>Material complementar</legend>
    <label class="file-drop">
      <?= icon('layers', 'icon icon-lg') ?><span>Mais imagens (até 8): perspectivas, detalhes, referências</span>
      <input type="file" name="gallery[]" accept="image/*" multiple data-compress data-max="8" data-preview="gallery-preview">
    </label>
    <div id="gallery-preview" class="thumbs"></div>
    <label class="file-drop">
      <?= icon('file', 'icon icon-lg') ?><span>PDFs (até 5): plantas, pranchas, memorial</span>
      <input type="file" name="pdfs[]" accept="application/pdf" multiple data-max="5" data-preview="pdf-preview">
    </label>
    <div id="pdf-preview" class="file-list"></div>
  </fieldset>

  <fieldset>
    <legend>Custo</legend>
    <div class="row-2">
      <label>Custo estimado em R$ (opcional)
        <input name="estimated_cost" inputmode="decimal" value="<?= e(old('estimated_cost')) ?>" placeholder="Ex.: 45.000,00">
      </label>
      <label>Como chegou nesse valor?
        <input name="cost_notes" maxlength="255" value="<?= e(old('cost_notes')) ?>" placeholder="Ex.: tabela SINAPI, orçamento informal">
      </label>
    </div>
  </fieldset>

  <fieldset>
    <legend>Licença do seu trabalho</legend>
    <p class="hint">A autoria fica sempre registrada no seu nome. A licença define o que outras pessoas podem fazer com o material.</p>
    <div class="license-list">
      <?php foreach (licenses() as $k => [$label, $hint, $link]): ?>
      <label class="check">
        <input type="radio" name="license" value="<?= e($k) ?>" <?= old('license', 'cc-by') === $k ? 'checked' : '' ?>>
        <span><strong><?= e($label) ?></strong>: <?= e($hint) ?><?php if ($link): ?> <a href="<?= e($link) ?>" target="_blank" rel="noopener">saiba mais</a><?php endif ?></span>
      </label>
      <?php endforeach ?>
    </div>
  </fieldset>

  <label class="check">
    <input type="checkbox" name="authorship" value="1" required <?= old('authorship') ? 'checked' : '' ?>>
    <span>Sou autor(a) deste material ou tenho autorização de quem fez. Referências de terceiros estão creditadas.</span>
  </label>
  <label class="check">
    <input type="checkbox" name="disclaimer" value="1" required <?= old('disclaimer') ? 'checked' : '' ?>>
    <span>Entendo que esta é uma proposta conceitual. Uma obra de verdade exige projeto executivo e responsabilidade técnica (RRT/ART).</span>
  </label>

  <button class="btn btn-primary btn-lg btn-block" type="submit">Enviar proposta</button>
</form>
</div>
