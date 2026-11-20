<?php
/** @var array $pj */
$goal = $pj['goal_amount'] !== null ? (float)$pj['goal_amount'] : null;
$pct = $goal ? min(100, round((float)$pj['raised_amount'] / $goal * 100)) : 0;
?>
<article class="card proposal-card project-card">
  <a href="<?= e(url('/projetos/' . $pj['id'])) ?>">
    <div class="proposal-cover">
      <img src="<?= e(upload_url($pj['cover_thumb'])) ?>" alt="" loading="lazy">
      <span class="pill <?= e('pstatus-' . $pj['status']) ?>"><?= e(project_statuses()[$pj['status']] ?? '') ?></span>
    </div>
    <div class="proposal-body">
      <h3><?= e($pj['title']) ?></h3>
      <p class="meta"><?= icon('pin') ?><?= e(excerpt($pj['issue_title'], 50)) ?></p>
      <p class="meta"><?= icon('users') ?><?= e($pj['organization']) ?><?php if ($pj['organization_verified_at']): ?> <span class="verified-dot" title="Organização verificada"><?= icon('shield') ?></span><?php endif ?></p>
      <?php if ($goal): ?>
        <div class="bar"><span style="width:<?= $pct ?>%"></span></div>
        <p class="small"><strong><?= e(money($pj['raised_amount'])) ?></strong> de <?= e(money($goal)) ?></p>
      <?php endif ?>
      <?php if (!empty($pj['totals']['committed'])): ?><p class="muted small"><?= e(money($pj['totals']['committed'])) ?> em compromissos</p><?php endif ?>
      <div class="tags">
        <span class="pill <?= e('auth-' . $pj['auth_status']) ?>"><?= e(auth_statuses()[$pj['auth_status']] ?? '') ?></span>
        <?= moderation_badge($pj['moderation']) ?>
      </div>
    </div>
  </a>
</article>
