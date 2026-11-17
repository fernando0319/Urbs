<?= partial('mod/_tabs', ['tab' => $tab, 'counts' => $counts]) ?>
<div class="flash flash-info">
  <strong>Profissional:</strong> confira o número no
  <a href="https://siccau.caubr.gov.br/app/view/sight/externo?form=Pesquisa" target="_blank" rel="noopener">SICCAU (CAU)</a>
  ou na consulta pública do CREA do estado, e veja se o nome bate.
  <strong>Morador:</strong> confira se o nome e o endereço no comprovante batem com a cidade. O documento é apagado automaticamente após a decisão.
</div>
<?php if (!$requests): ?>
  <div class="empty"><?= icon('check', 'icon icon-xl') ?><p>Nenhum pedido pendente.</p></div>
<?php endif ?>
<?php foreach ($requests as $v): ?>
<article class="card mod-item">
  <div class="mod-item-head">
    <span class="pill <?= $v['type'] === 'professional' ? 'badge-pro' : 'badge-res' ?>"><?= $v['type'] === 'professional' ? 'Profissional' : 'Morador' ?></span>
    <a href="<?= e(url('/perfil/' . $v['user_id'])) ?>"><strong><?= e($v['name']) ?></strong></a>
    <span class="muted small"><?= e($v['email']) ?> · conta criada em <?= e(fmt_date($v['user_created_at'])) ?> · pedido <?= e(time_ago($v['created_at'])) ?></span>
  </div>
  <?php if ($v['registry']): ?><p><strong>Registro:</strong> <?= e($v['registry']) ?></p><?php endif ?>
  <?php if ($v['notes']): ?><p><strong>Observações:</strong> <?= e($v['notes']) ?></p><?php endif ?>
  <?php if ($v['file']): ?><p><a class="btn btn-sm" href="<?= e(url('/moderacao/arquivo/' . $v['id'])) ?>" target="_blank"><?= icon('file') ?>Ver documento</a></p><?php endif ?>
  <form method="post" action="<?= e(url('/moderacao/verificacoes/' . $v['id'])) ?>" class="mod-actions">
    <?= csrf_field() ?>
    <input name="note" maxlength="500" placeholder="Observação para o usuário (opcional)">
    <button class="btn btn-sm btn-primary" name="decision" value="approve"><?= icon('check') ?>Aprovar selo</button>
    <button class="btn btn-sm btn-danger" name="decision" value="deny"><?= icon('x') ?>Negar</button>
  </form>
</article>
<?php endforeach ?>
