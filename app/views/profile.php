<div class="profile-head">
  <?= avatar($profile, '', 'lg') ?>
  <div>
    <h1><?= e($profile['name']) ?></h1>
    <p class="muted"><?= e(profile_types()[$profile['profile_type']] ?? '') ?> · participa desde <?= e(fmt_date($profile['created_at'])) ?></p>
    <div class="tags"><?= user_badges($profile) ?></div>
  </div>
</div>
<?php if ($profile['bio']): ?><div class="prose"><?= nl2p($profile['bio']) ?></div><?php endif ?>
<?php if ((int)$profile['id'] !== uid()): ?><?= partial('partials/report', ['type' => 'user', 'id' => (int)$profile['id']]) ?><?php endif ?>

<section class="section">
  <h2>Propostas <span class="muted">(<?= count($proposals) ?>)</span></h2>
  <?php if ($proposals): ?>
    <div class="grid grid-3"><?php foreach ($proposals as $p) echo partial('partials/proposal_card', ['p' => $p]); ?></div>
  <?php else: ?><p class="muted">Nenhuma proposta publicada.</p><?php endif ?>
</section>

<section class="section">
  <h2>Ocorrências registradas <span class="muted">(<?= count($issues) ?>)</span></h2>
  <?php if ($issues): ?>
    <div class="grid grid-2"><?php foreach ($issues as $i) echo partial('partials/issue_card', ['i' => $i]); ?></div>
  <?php else: ?><p class="muted">Nenhuma ocorrência registrada.</p><?php endif ?>
</section>
