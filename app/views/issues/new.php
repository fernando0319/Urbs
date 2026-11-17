<div class="narrow">
<a class="back" href="<?= e(url('/')) ?>"><?= icon('arrow-left') ?>Voltar</a>
<h1>Registrar ocorrência</h1>
<p class="lead-sm">Aponte um problema ou uma ideia para um lugar da cidade. Depois, qualquer pessoa pode propor soluções.</p>
<?= partial('partials/errors', ['errors' => $errors]) ?>

<form method="post" enctype="multipart/form-data" class="form" id="issue-form" data-near="<?= e(url('/api/proximas')) ?>">
  <?= csrf_field() ?>
  <fieldset>
    <legend>O que você quer registrar?</legend>
    <div class="choice-grid">
      <?php foreach (['problem' => ['Problema', 'Buraco, calçada quebrada, falta de iluminação, lixo…', 'flag'], 'idea' => ['Sugestão', 'Uma praça, ciclovia, banco, árvore, mural…', 'pencil']] as $k => [$label, $hint, $ic]): ?>
      <label class="choice">
        <input type="radio" name="kind" value="<?= $k ?>" <?= old('kind', 'problem') === $k ? 'checked' : '' ?>>
        <span class="choice-body"><?= icon($ic) ?><strong><?= $label ?></strong><small><?= $hint ?></small></span>
      </label>
      <?php endforeach ?>
    </div>
  </fieldset>

  <label>Categoria
    <select name="category_id" required>
      <option value="">Escolha…</option>
      <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)old('category_id') === (int)$c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option><?php endforeach ?>
    </select>
  </label>

  <label>Título
    <input name="title" required minlength="5" maxlength="160" value="<?= e(old('title')) ?>" placeholder="Ex.: Calçada quebrada em frente à escola">
  </label>

  <label>Descrição
    <textarea name="description" required minlength="10" maxlength="5000" rows="5" placeholder="Conte o que acontece, desde quando e quem é afetado."><?= e(old('description')) ?></textarea>
  </label>

  <fieldset>
    <legend>Onde fica?</legend>
    <p class="hint">Toque no mapa para marcar o ponto exato ou use sua localização.</p>
    <div id="picker" class="map-picker"
         data-lat="<?= e(setting('map_lat')) ?>" data-lng="<?= e(setting('map_lng')) ?>" data-zoom="<?= e(setting('map_zoom')) ?>"
         data-tiles="<?= e(setting('tile_url')) ?>" data-attribution="<?= e(setting('tile_attribution')) ?>"></div>
    <button type="button" class="btn btn-sm" id="use-gps"><?= icon('gps') ?>Usar minha localização</button>
    <input type="hidden" name="lat" id="lat" value="<?= e(old('lat', $lat)) ?>">
    <input type="hidden" name="lng" id="lng" value="<?= e(old('lng', $lng)) ?>">
    <label>Endereço ou referência
      <input name="address" id="address" maxlength="255" value="<?= e(old('address')) ?>" placeholder="Preenchido automaticamente; ajuste se precisar">
    </label>
    <div id="nearby" class="nearby" hidden></div>
  </fieldset>

  <fieldset>
    <legend>Fotos (até 5)</legend>
    <label class="file-drop">
      <?= icon('image', 'icon icon-lg') ?>
      <span>Tirar foto ou escolher da galeria</span>
      <input type="file" name="photos[]" accept="image/*" multiple data-compress data-max="5" data-preview="photo-preview">
    </label>
    <div id="photo-preview" class="thumbs"></div>
    <label class="check">
      <input type="checkbox" name="photo_consent" value="1" <?= old('photo_consent') ? 'checked' : '' ?>>
      <span>As fotos não mostram rostos identificáveis, placas de veículos nem dados pessoais.</span>
    </label>
  </fieldset>

  <label class="check">
    <input type="checkbox" name="rules_consent" value="1" required <?= old('rules_consent') ? 'checked' : '' ?>>
    <span>Li e aceito as <a href="<?= e(url('/regras')) ?>" target="_blank">regras de participação</a>.</span>
  </label>

  <button class="btn btn-primary btn-lg btn-block" type="submit">Publicar</button>
</form>
</div>
