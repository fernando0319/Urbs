<?php $author = author_of($issue); $isAuthor = (int)$issue['user_id'] === uid(); ?>
<?php ob_start(); ?>
    <div class="card support-card">
      <p class="support-count"><strong data-support-count><?= (int)$issue['support_count'] ?></strong> <span data-support-noun><?= (int)$issue['support_count'] === 1 ? 'apoiador' : 'apoiadores' ?></span></p>
      <?php if ((int)$issue['support_verified_count']): ?>
        <p class="muted small"><?= icon('home') ?><?= (int)$issue['support_verified_count'] ?> moradores verificados</p>
      <?php endif ?>
      <?php if ($issue['moderation'] === 'approved'): ?>
        <?php if (current_user()): ?>
        <form method="post" action="<?= e(url('/ocorrencias/' . $issue['id'] . '/apoiar')) ?>" data-support>
          <?= csrf_field() ?>
          <button class="btn btn-block <?= $supported ? 'btn-supported' : 'btn-primary' ?>" aria-pressed="<?= $supported ? 'true' : 'false' ?>">
            <?= icon('thumb') ?><span data-support-label><?= $supported ? 'Você apoia' : 'Apoiar' ?></span>
          </button>
        </form>
        <?php else: ?>
          <a class="btn btn-primary btn-block" href="<?= e(url('/entrar')) ?>"><?= icon('thumb') ?>Entrar para apoiar</a>
        <?php endif ?>
      <?php endif ?>
      <p class="muted small">Um apoio por pessoa. Apoios mostram o interesse da comunidade e não têm caráter vinculante.</p>
    </div>
<?php $actionCard = ob_get_clean(); ?>
<a class="back" href="<?= e(url('/ocorrencias')) ?>"><?= icon('arrow-left') ?>Ocorrências</a>

<?php if ($issue['moderation'] !== 'approved'): ?>
  <div class="flash flash-info">
    <?= moderation_badge($issue['moderation']) ?>
    <?= $issue['moderation'] === 'pending' ? 'Só você e a moderação veem esta publicação por enquanto.' : 'Esta publicação não está visível para outras pessoas.' ?>
  </div>
<?php endif ?>

<div class="detail">
  <div class="detail-main">
    <div class="tags">
      <?= status_badge($issue['status']) ?>
      <span class="pill"><?= e(issue_kinds()[$issue['kind']] ?? '') ?></span>
      <?php if ($issue['category_name']): ?><span class="pill pill-cat" style="--cat:<?= e($issue['category_color']) ?>"><?= e($issue['category_name']) ?></span><?php endif ?>
    </div>
    <h1><?= e($issue['title']) ?></h1>
    <div class="only-mobile"><?= $actionCard ?></div>
    <?php if ($issue['address']): ?><p class="meta"><?= icon('pin') ?><?= e($issue['address']) ?></p><?php endif ?>

    <?php if ($photos): ?>
    <div class="gallery">
      <?php foreach ($photos as $ph): ?>
        <a href="<?= e(upload_url($ph['file'])) ?>" target="_blank"><img src="<?= e(upload_url($ph['thumb'])) ?>" alt="Foto da ocorrência" loading="lazy"></a>
      <?php endforeach ?>
    </div>
    <?php endif ?>

    <div class="prose"><?= nl2p($issue['description']) ?></div>

    <div class="author-line">
      <?= avatar($author) ?>
      <span>
        <?php if (!$author['deleted_at']): ?><a href="<?= e(url('/perfil/' . $issue['user_id'])) ?>"><?= e(display_name($author)) ?></a><?php else: ?>Usuário removido<?php endif ?>
        <span class="muted">· <?= e(fmt_date($issue['created_at'])) ?></span>
      </span>
      <?= user_badges($author) ?>
    </div>

    <section class="section">
      <div class="section-head">
        <h2><?= icon('layers') ?>Propostas para este lugar <span class="muted">(<?= count($proposals) ?>)</span></h2>
        <?php if (array_filter($proposals, fn($p) => $p['moderation'] === 'approved')): ?>
          <a class="btn btn-sm" href="<?= e(url('/ocorrencias/' . $issue['id'] . '/comparar')) ?>"><?= icon('compare') ?>Comparar e escolher</a>
        <?php endif ?>
      </div>
      <?php if ($proposals): ?>
        <div class="grid grid-2"><?php foreach ($proposals as $p) echo partial('partials/proposal_card', ['p' => $p]); ?></div>
      <?php endif ?>
      <?php if ($issue['moderation'] === 'approved' && !in_array($issue['status'], ['done', 'archived'], true)): ?>
      <div class="callout callout-sm">
        <div>
          <strong><?= $proposals ? 'Tem outra ideia para este lugar?' : 'Ainda não há propostas.' ?></strong>
          <p>Envie um projeto, croqui, ilustração ou fotomontagem mostrando o "depois". Não precisa ser profissional.</p>
        </div>
        <a class="btn btn-primary" href="<?= e(url('/ocorrencias/' . $issue['id'] . '/propor')) ?>"><?= icon('pencil') ?>Enviar proposta</a>
      </div>
      <?php endif ?>
    </section>

    <?php if ($projects): ?>
    <section class="section">
      <h2><?= icon('users') ?>Projetos comunitários</h2>
      <div class="grid grid-2"><?php foreach ($projects as $pj) echo partial('partials/project_card', ['pj' => $pj]); ?></div>
    </section>
    <?php endif ?>

    <?php if ($timeline): ?>
    <section class="section">
      <h2><?= icon('clock') ?>Histórico</h2>
      <ol class="timeline">
        <?php foreach ($timeline as $t): ?>
        <li>
          <span class="timeline-date"><?= e(fmt_date($t['created_at'], true)) ?></span>
          <div><?= status_badge($t['status']) ?><?php if ($t['note']): ?> <span><?= e($t['note']) ?></span><?php endif ?></div>
        </li>
        <?php endforeach ?>
        <li><span class="timeline-date"><?= e(fmt_date($issue['created_at'], true)) ?></span><div>Registrada</div></li>
      </ol>
    </section>
    <?php endif ?>

    <?php if ($issue['moderation'] === 'approved'): ?>
      <?= partial('partials/comments', ['comments' => $comments, 'type' => 'issue', 'id' => (int)$issue['id']]) ?>
    <?php endif ?>
  </div>

  <aside class="detail-side">
    <div class="only-desktop"><?= $actionCard ?></div>

    <div class="card">
      <div id="mini-map" class="map-mini" data-lat="<?= e($issue['lat']) ?>" data-lng="<?= e($issue['lng']) ?>"
           data-tiles="<?= e(setting('tile_url')) ?>" data-attribution="<?= e(setting('tile_attribution')) ?>"></div>
      <a class="small" href="https://www.openstreetmap.org/?mlat=<?= e($issue['lat']) ?>&amp;mlon=<?= e($issue['lng']) ?>#map=18/<?= e($issue['lat']) ?>/<?= e($issue['lng']) ?>" target="_blank" rel="noopener">Abrir no OpenStreetMap</a>
    </div>

    <button type="button" class="btn btn-block" data-share data-title="<?= e($issue['title']) ?>">Compartilhar</button>

    <?php if (is_mod()): ?>
    <div class="card">
      <h3>Moderação</h3>
      <form method="post" action="<?= e(url('/ocorrencias/' . $issue['id'] . '/status')) ?>" class="form form-tight">
        <?= csrf_field() ?>
        <label>Status
          <select name="status"><?php foreach (issue_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $issue['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select>
        </label>
        <label>Nota pública (opcional)<input name="note" maxlength="500" placeholder="Ex.: Prefeitura informou conserto para março"></label>
        <button class="btn btn-sm">Atualizar status</button>
      </form>
      <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="form form-tight" <?= $issue['moderation'] === 'approved' ? 'data-confirm="Ocultar esta ocorrência?"' : '' ?>>
        <?= csrf_field() ?><input type="hidden" name="target_type" value="issue"><input type="hidden" name="target_id" value="<?= (int)$issue['id'] ?>">
        <?php if ($issue['moderation'] !== 'approved'): ?>
          <button class="btn btn-sm btn-primary" name="action" value="approve">Aprovar publicação</button>
        <?php else: ?>
          <input name="note" placeholder="Motivo (fica no registro público)" maxlength="500">
          <button class="btn btn-sm btn-danger" name="action" value="hide">Ocultar</button>
        <?php endif ?>
      </form>
    </div>
    <?php endif ?>

    <?php if ($isAuthor || is_mod()): ?>
    <form method="post" action="<?= e(url('/ocorrencias/' . $issue['id'] . '/excluir')) ?>" data-confirm="Excluir esta ocorrência definitivamente? Fotos e propostas ligadas a ela serão apagadas.">
      <?= csrf_field() ?><button class="linklike danger">Excluir ocorrência</button>
    </form>
    <?php endif ?>
    <?php if (!$isAuthor): ?><?= partial('partials/report', ['type' => 'issue', 'id' => (int)$issue['id']]) ?><?php endif ?>
  </aside>
</div>
