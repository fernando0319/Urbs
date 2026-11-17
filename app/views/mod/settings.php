<?= partial('mod/_tabs', ['tab' => $tab, 'counts' => $counts]) ?>
<div class="narrow">
<form method="post" class="form card">
  <?= csrf_field() ?>
  <h2>Identidade</h2>
  <div class="row-2">
    <label>Nome da plataforma<input name="site_name" required value="<?= e($values['site_name']) ?>"></label>
    <label>Cidade<input name="city_name" required value="<?= e($values['city_name']) ?>"></label>
  </div>
  <label>E-mail de contato (aparece nas páginas institucionais)<input type="email" name="contact_email" value="<?= e($values['contact_email']) ?>"></label>
  <label>Texto da página "Sobre" (opcional)<textarea name="about_text" rows="5"><?= e($values['about_text']) ?></textarea></label>

  <h2>Mapa</h2>
  <div class="row-3">
    <label>Latitude do centro<input name="map_lat" required value="<?= e($values['map_lat']) ?>"></label>
    <label>Longitude do centro<input name="map_lng" required value="<?= e($values['map_lng']) ?>"></label>
    <label>Zoom inicial<input name="map_zoom" type="number" min="3" max="19" value="<?= e($values['map_zoom']) ?>"></label>
  </div>
  <label>URL dos tiles do mapa
    <input name="tile_url" required value="<?= e($values['tile_url']) ?>">
    <small class="hint">Os tiles públicos do OpenStreetMap servem para testes, mas têm política de uso restrita. Em produção, use um provedor como MapTiler (ex.: <code>https://api.maptiler.com/maps/streets-v2/{z}/{x}/{y}.png?key=SUA_CHAVE</code>) ou Stadia Maps.</small>
  </label>
  <label>Atribuição dos tiles (HTML)<input name="tile_attribution" value="<?= e($values['tile_attribution']) ?>"></label>

  <h2>Participação</h2>
  <label>Publicações aprovadas para a conta ser considerada confiável
    <input name="trust_threshold" type="number" min="0" max="50" value="<?= e($values['trust_threshold']) ?>">
    <small class="hint">Até atingir esse número, as publicações da conta passam pela fila de moderação. Use 0 para publicar tudo direto (não recomendado).</small>
  </label>
  <label class="check"><input type="checkbox" name="registration_open" value="1" <?= $values['registration_open'] ? 'checked' : '' ?>><span>Cadastro aberto para novas contas</span></label>
  <label class="check"><input type="checkbox" name="require_email_verification" value="1" <?= $values['require_email_verification'] ? 'checked' : '' ?>><span>Exigir e-mail confirmado para publicar, apoiar e votar</span></label>
  <button class="btn btn-primary">Salvar configurações</button>
</form>

<section class="card" id="categorias">
  <h2>Categorias</h2>
  <?php foreach ($categories as $c): ?>
  <form method="post" action="<?= e(url('/moderacao/categorias')) ?>" class="cat-row">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
    <input type="color" name="color" value="<?= e($c['color']) ?>" aria-label="Cor">
    <input name="name" value="<?= e($c['name']) ?>" aria-label="Nome">
    <input type="number" name="sort" value="<?= (int)$c['sort'] ?>" aria-label="Ordem" class="w-num">
    <label class="check check-inline"><input type="checkbox" name="active" value="1" <?= $c['active'] ? 'checked' : '' ?>><span>Ativa</span></label>
    <button class="btn btn-sm">Salvar</button>
  </form>
  <?php endforeach ?>
  <form method="post" action="<?= e(url('/moderacao/categorias')) ?>" class="cat-row">
    <?= csrf_field() ?>
    <input type="color" name="color" value="#16a34a" aria-label="Cor">
    <input name="name" placeholder="Nova categoria" aria-label="Nome">
    <input type="number" name="sort" value="100" aria-label="Ordem" class="w-num">
    <input type="hidden" name="active" value="1">
    <button class="btn btn-sm btn-primary">Adicionar</button>
  </form>
</section>
</div>
