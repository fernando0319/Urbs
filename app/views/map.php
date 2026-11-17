<div class="map-page">
  <div id="map" class="map-full"
       data-lat="<?= e(setting('map_lat')) ?>" data-lng="<?= e(setting('map_lng')) ?>" data-zoom="<?= e(setting('map_zoom')) ?>"
       data-tiles="<?= e(setting('tile_url')) ?>" data-attribution="<?= e(setting('tile_attribution')) ?>"
       data-api="<?= e(url('/api/ocorrencias')) ?>" data-new="<?= e(url('/ocorrencias/nova')) ?>"></div>
  <form class="map-filters" id="map-filters">
    <label class="sr-only" for="f-category">Categoria</label>
    <select id="f-category" name="category">
      <option value="">Todas as categorias</option>
      <?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach ?>
    </select>
    <label class="sr-only" for="f-status">Status</label>
    <select id="f-status" name="status">
      <option value="">Ativas</option>
      <?php foreach (issue_statuses() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach ?>
      <option value="all">Todas</option>
    </select>
    <label class="sr-only" for="f-kind">Tipo</label>
    <select id="f-kind" name="kind">
      <option value="">Problemas e sugestões</option>
      <?php foreach (issue_kinds() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach ?>
    </select>
  </form>
  <button type="button" class="btn map-locate" id="map-locate" title="Minha localização"><?= icon('gps') ?><span class="sr-only">Minha localização</span></button>
  <p class="map-hint">Toque no mapa para registrar algo naquele ponto.</p>
</div>
