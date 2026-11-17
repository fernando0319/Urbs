<?= partial('mod/_tabs', ['tab' => $tab, 'counts' => $counts]) ?>
<p class="muted">Publicações de contas novas aguardam aqui. Verifique se há rostos, placas, dados pessoais, propaganda partidária ou conteúdo ofensivo. Recusas avisam o autor por e-mail com o motivo.</p>

<?php if (!$issues && !$proposals): ?>
  <div class="empty"><?= icon('check', 'icon icon-xl') ?><p>Fila vazia. Bom trabalho!</p></div>
<?php endif ?>

<?php foreach ($issues as $i): ?>
<article class="card mod-item">
  <div class="mod-item-head">
    <span class="pill">Ocorrência</span>
    <a href="<?= e(url('/ocorrencias/' . $i['id'])) ?>"><strong><?= e($i['title']) ?></strong></a>
    <span class="muted small">por <?= e($i['author_name']) ?> · <?= e(time_ago($i['created_at'])) ?></span>
  </div>
  <p><?= e(excerpt($i['description'], 400)) ?></p>
  <?php if ($i['photos']): ?>
  <div class="gallery gallery-sm">
    <?php foreach ($i['photos'] as $ph): ?><a href="<?= e(upload_url($ph['file'])) ?>" target="_blank"><img src="<?= e(upload_url($ph['thumb'])) ?>" alt=""></a><?php endforeach ?>
  </div>
  <?php endif ?>
  <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="mod-actions">
    <?= csrf_field() ?><input type="hidden" name="target_type" value="issue"><input type="hidden" name="target_id" value="<?= (int)$i['id'] ?>">
    <input name="note" maxlength="500" placeholder="Motivo da recusa (enviado ao autor)">
    <button class="btn btn-sm btn-primary" name="action" value="approve"><?= icon('check') ?>Aprovar</button>
    <button class="btn btn-sm btn-danger" name="action" value="reject"><?= icon('x') ?>Recusar</button>
  </form>
</article>
<?php endforeach ?>

<?php foreach ($proposals as $p): ?>
<article class="card mod-item">
  <div class="mod-item-head">
    <span class="pill">Proposta</span>
    <a href="<?= e(url('/propostas/' . $p['id'])) ?>"><strong><?= e($p['title']) ?></strong></a>
    <span class="muted small">por <?= e($p['author_name']) ?> · para "<?= e($p['issue_title']) ?>" · <?= e(time_ago($p['created_at'])) ?></span>
  </div>
  <div class="mod-proposal">
    <a href="<?= e(upload_url($p['after_file'])) ?>" target="_blank"><img src="<?= e(upload_url($p['cover_thumb'])) ?>" alt=""></a>
    <p><?= e($p['summary']) ?></p>
  </div>
  <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="mod-actions">
    <?= csrf_field() ?><input type="hidden" name="target_type" value="proposal"><input type="hidden" name="target_id" value="<?= (int)$p['id'] ?>">
    <input name="note" maxlength="500" placeholder="Motivo da recusa (enviado ao autor)">
    <button class="btn btn-sm btn-primary" name="action" value="approve"><?= icon('check') ?>Aprovar</button>
    <button class="btn btn-sm btn-danger" name="action" value="reject"><?= icon('x') ?>Recusar</button>
  </form>
</article>
<?php endforeach ?>
