<?php /** @var array $i */ ?>
<article class="card issue-card">
  <a class="issue-card-link" href="<?= e(url('/ocorrencias/' . $i['id'])) ?>">
    <div class="issue-thumb" style="--cat:<?= e($i['category_color'] ?: '#2563eb') ?>">
      <?php if ($i['cover_thumb']): ?>
        <img src="<?= e(upload_url($i['cover_thumb'])) ?>" alt="" loading="lazy">
      <?php else: ?>
        <?= icon($i['kind'] === 'idea' ? 'pencil' : 'pin', 'icon icon-lg') ?>
      <?php endif ?>
    </div>
    <div class="issue-body">
      <h3><?= e($i['title']) ?></h3>
      <?php if ($i['address']): ?><p class="meta"><?= icon('pin') ?><?= e(excerpt($i['address'], 60)) ?></p><?php endif ?>
      <p class="excerpt"><?= e(excerpt($i['description'], 110)) ?></p>
      <p class="meta">
        <span><?= icon('users') ?><?= plural((int)$i['support_count'], 'apoiador', 'apoiadores') ?></span>
        <?php if ((int)$i['proposal_count']): ?><span><?= icon('layers') ?><?= plural((int)$i['proposal_count'], 'proposta', 'propostas') ?></span><?php endif ?>
      </p>
      <div class="tags">
        <?= status_badge($i['status']) ?>
        <?php if ($i['category_name']): ?><span class="pill pill-cat" style="--cat:<?= e($i['category_color']) ?>"><?= e($i['category_name']) ?></span><?php endif ?>
        <?= moderation_badge($i['moderation']) ?>
      </div>
    </div>
  </a>
</article>
