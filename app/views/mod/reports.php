<?= partial('mod/_tabs', ['tab' => $tab, 'counts' => $counts]) ?>
<?php if (!$reports): ?>
  <div class="empty"><?= icon('check', 'icon icon-xl') ?><p>Nenhuma denúncia aberta.</p></div>
<?php endif ?>
<?php foreach ($reports as $r):
    $t = $r['target'];
    $link = match ($r['target_type']) {
        'issue' => url('/ocorrencias/' . $r['target_id']),
        'proposal' => url('/propostas/' . $r['target_id']),
        'comment' => $t ? url(target_url($t['target_type'], (int)$t['target_id'])) . '#comentarios' : '#',
        'user' => url('/perfil/' . $r['target_id']),
        'project' => url('/projetos/' . $r['target_id']),
        'suggestion' => $t ? url('/propostas/' . $t['proposal_id']) . '#sugestao-' . $r['target_id'] : '#',
        default => '#',
    }; ?>
<article class="card mod-item">
  <div class="mod-item-head">
    <span class="pill pill-warn"><?= e(report_reasons()[$r['reason']] ?? $r['reason']) ?></span>
    <span><?= e(ucfirst(target_label($r['target_type']))) ?>:</span>
    <a href="<?= e($link) ?>"><strong><?= e($t['title'] ?? excerpt((string)($t['body'] ?? '(removido)'), 60)) ?></strong></a>
    <span class="muted small">denunciado por <?= e($r['reporter_name']) ?> · <?= e(time_ago($r['created_at'])) ?></span>
  </div>
  <?php if ($t && !empty($t['body'])): ?><blockquote><?= e(excerpt((string)$t['body'], 400)) ?></blockquote><?php endif ?>
  <?php if ($r['details']): ?><p class="small"><strong>Detalhes:</strong> <?= e($r['details']) ?></p><?php endif ?>
  <div class="mod-actions">
    <?php if ($t && in_array($r['target_type'], ['issue', 'proposal', 'comment', 'project', 'suggestion'], true) && ($t['moderation'] ?? '') === 'approved'): ?>
    <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="target_type" value="<?= e($r['target_type']) ?>"><input type="hidden" name="target_id" value="<?= (int)$r['target_id'] ?>">
      <input name="note" maxlength="500" placeholder="Motivo (registro público)">
      <button class="btn btn-sm btn-danger" name="action" value="hide">Ocultar conteúdo</button>
    </form>
    <?php endif ?>
    <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="report_id" value="<?= (int)$r['id'] ?>">
      <button class="btn btn-sm" name="action" value="dismiss_report">Arquivar denúncia</button>
    </form>
  </div>
</article>
<?php endforeach ?>
