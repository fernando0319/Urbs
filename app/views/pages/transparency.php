<div class="narrow">
<h1>Transparência da moderação</h1>
<p class="lead-sm">Toda ação da moderação fica registrada aqui, para que qualquer pessoa possa acompanhar como as <a href="<?= e(url('/regras')) ?>">regras</a> são aplicadas.</p>
<?php if (!$log): ?><p class="muted">Nenhuma ação registrada ainda.</p><?php endif ?>
<ol class="timeline">
  <?php foreach ($log as $l): ?>
  <li>
    <span class="timeline-date"><?= e(fmt_date($l['created_at'], true)) ?></span>
    <div>
      <strong><?= e($l['mod_name']) ?></strong> <?= e(mod_action_label($l['action'])) ?> <?= e(target_label($l['target_type'])) ?> #<?= (int)$l['target_id'] ?>
      <?php if ($l['note'] && $l['target_type'] !== 'user'): ?><span class="muted"> · <?= e($l['note']) ?></span><?php endif ?>
    </div>
  </li>
  <?php endforeach ?>
</ol>
</div>
