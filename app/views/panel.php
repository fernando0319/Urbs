<?php
$statusTotal = max(1, array_sum($byStatus));
$catMax = max(1, ...array_map(fn($c) => (int)$c['n'], $byCategory ?: [['n' => 1]]));
?>
<div class="page-head">
  <div>
    <h1>Painel público</h1>
    <p class="muted">O que está acontecendo em <?= e(setting('city_name')) ?>, em números e em linha do tempo.</p>
  </div>
</div>

<section class="stats">
  <div><strong><?= $stats['issues'] ?></strong><span>ocorrências</span></div>
  <div><strong><?= $stats['proposals'] ?></strong><span>propostas</span></div>
  <div><strong><?= $stats['supports'] ?></strong><span>apoios</span></div>
  <div><strong><?= $stats['done'] ?></strong><span>resolvidas</span></div>
</section>

<div class="grid grid-2">
  <section class="card">
    <h2>Por status</h2>
    <ul class="hbars">
      <?php foreach (issue_statuses() as $k => $label): $n = $byStatus[$k] ?? 0; ?>
      <li>
        <span class="hbar-label"><?= e($label) ?></span>
        <span class="hbar"><span class="<?= status_class($k) ?>" style="width:<?= round($n / $statusTotal * 100) ?>%"></span></span>
        <span class="hbar-n"><?= $n ?></span>
      </li>
      <?php endforeach ?>
    </ul>
  </section>
  <section class="card">
    <h2>Por categoria</h2>
    <ul class="hbars">
      <?php foreach ($byCategory as $c): ?>
      <li>
        <span class="hbar-label"><?= e($c['name']) ?></span>
        <span class="hbar"><span style="width:<?= round((int)$c['n'] / $catMax * 100) ?>%;background:<?= e($c['color']) ?>"></span></span>
        <span class="hbar-n"><?= (int)$c['n'] ?></span>
      </li>
      <?php endforeach ?>
    </ul>
  </section>
</div>

<section class="section">
  <h2><?= icon('clock') ?>Linha do tempo</h2>
  <?php if (!$timeline): ?><p class="muted">Ainda não há atividade.</p><?php endif ?>
  <ol class="timeline">
    <?php foreach ($timeline as $t): ?>
    <li>
      <span class="timeline-date"><?= e(fmt_date($t['at'], true)) ?></span>
      <div>
        <?php if ($t['type'] === 'issue'): ?>
          Nova ocorrência: <a href="<?= e(url('/ocorrencias/' . $t['id'])) ?>"><?= e($t['title']) ?></a>
        <?php elseif ($t['type'] === 'proposal'): ?>
          Nova proposta: <a href="<?= e(url('/propostas/' . $t['id'])) ?>"><?= e($t['title']) ?></a>
        <?php else: ?>
          <a href="<?= e(url('/ocorrencias/' . $t['issue_id'])) ?>"><?= e($t['title']) ?></a> mudou para <?= status_badge((string)$t['status']) ?>
          <?php if ($t['note']): ?><span class="muted"> · <?= e($t['note']) ?></span><?php endif ?>
        <?php endif ?>
      </div>
    </li>
    <?php endforeach ?>
  </ol>
</section>
