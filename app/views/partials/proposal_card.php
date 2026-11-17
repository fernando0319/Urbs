<?php /** @var array $p */ $author = author_of($p); ?>
<article class="card proposal-card">
  <a href="<?= e(url('/propostas/' . $p['id'])) ?>">
    <div class="proposal-cover">
      <img src="<?= e(upload_url($p['cover_thumb'])) ?>" alt="" loading="lazy">
      <?= moderation_badge($p['moderation']) ?>
    </div>
    <div class="proposal-body">
      <h3><?= e($p['title']) ?></h3>
      <p class="meta"><?= icon('pin') ?><?= e(excerpt($p['issue_title'], 50)) ?></p>
      <p class="excerpt"><?= e(excerpt($p['summary'], 100)) ?></p>
      <div class="author-line">
        <?= avatar($author) ?>
        <span><?= e(display_name($author)) ?></span>
        <?php if ($author['professional_verified_at']): ?><span class="verified-dot" title="Profissional verificado"><?= icon('shield') ?></span><?php endif ?>
      </div>
      <p class="meta">
        <?php if ($p['estimated_cost'] !== null): ?><span><?= e(money($p['estimated_cost'])) ?></span><?php endif ?>
        <span><?= icon('thumb') ?><?= plural((int)$p['preference_count'], 'preferência', 'preferências') ?></span>
      </p>
    </div>
  </a>
</article>
