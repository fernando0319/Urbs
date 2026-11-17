<a class="back" href="<?= e(url('/ocorrencias/' . $issue['id'])) ?>"><?= icon('arrow-left') ?><?= e($issue['title']) ?></a>
<div class="page-head">
  <div>
    <h1>Comparar propostas</h1>
    <p class="muted"><?= plural(count($proposals), 'proposta', 'propostas') ?> para <strong><?= e($issue['title']) ?></strong><?= $issue['address'] ? ' · ' . e($issue['address']) : '' ?></p>
  </div>
</div>

<div class="flash flash-info">
  Indique a proposta que você prefere. É uma preferência por pessoa, e você pode mudar quando quiser.
  O resultado mostra a opinião de quem participou e <strong>não tem caráter vinculante</strong>.
  Apoios de moradores verificados aparecem separados.
</div>

<div class="compare-scroll">
  <div class="compare" style="--cols:<?= count($proposals) + ($photo ? 1 : 0) ?>">
    <?php if ($photo): ?>
    <article class="compare-col compare-now">
      <div class="compare-img"><img src="<?= e(upload_url($photo['file'])) ?>" alt="Situação atual" loading="lazy"></div>
      <div class="compare-body">
        <span class="pill">Situação atual</span>
        <h3>Como está hoje</h3>
        <p class="excerpt"><?= e(excerpt($issue['description'], 180)) ?></p>
      </div>
    </article>
    <?php endif ?>
    <?php foreach ($proposals as $p):
        $a = author_of($p);
        $pct = $total ? round($p['preference_count'] / $total * 100) : 0;
        $isMine = $mine === (int)$p['id']; ?>
    <article class="compare-col <?= $isMine ? 'is-chosen' : '' ?>">
      <a class="compare-img" href="<?= e(url('/propostas/' . $p['id'])) ?>"><img src="<?= e(upload_url($p['after_file'])) ?>" alt="Imagem da proposta" loading="lazy"></a>
      <div class="compare-body">
        <h3><a href="<?= e(url('/propostas/' . $p['id'])) ?>"><?= e($p['title']) ?></a></h3>
        <div class="author-line"><?= avatar($a) ?><span><?= e(display_name($a)) ?></span></div>
        <div class="tags"><?= user_badges($a) ?></div>
        <p class="excerpt"><?= e($p['summary']) ?></p>
        <dl class="facts">
          <dt>Custo estimado</dt><dd><?= $p['estimated_cost'] !== null ? e(money($p['estimated_cost'])) : 'Não informado' ?></dd>
          <dt>Licença</dt><dd><?= e(licenses()[$p['license']][0] ?? $p['license']) ?></dd>
        </dl>
        <div class="bar" role="img" aria-label="<?= $pct ?>% das preferências"><span style="width:<?= $pct ?>%"></span></div>
        <p class="small"><strong><?= $pct ?>%</strong> · <?= plural((int)$p['preference_count'], 'preferência', 'preferências') ?>
          <?php if ((int)$p['preference_verified_count']): ?><br><span class="muted"><?= (int)$p['preference_verified_count'] ?> de moradores verificados</span><?php endif ?></p>
        <?php if (current_user()): ?>
        <form method="post" action="<?= e(url('/ocorrencias/' . $issue['id'] . '/preferir')) ?>">
          <?= csrf_field() ?>
          <input type="hidden" name="proposal_id" value="<?= (int)$p['id'] ?>">
          <button class="btn btn-block <?= $isMine ? 'btn-supported' : 'btn-primary' ?>"><?= icon($isMine ? 'check' : 'thumb') ?><?= $isMine ? 'Sua preferida (desfazer)' : 'Prefiro esta' ?></button>
        </form>
        <?php else: ?>
          <a class="btn btn-block btn-primary" href="<?= e(url('/entrar')) ?>">Entrar para escolher</a>
        <?php endif ?>
      </div>
    </article>
    <?php endforeach ?>
  </div>
</div>
<p class="muted small">Total: <?= plural($total, 'preferência', 'preferências') ?><?= $totalVerified ? ' · ' . $totalVerified . ' de moradores verificados' : '' ?>.</p>
<p><a class="btn" href="<?= e(url('/ocorrencias/' . $issue['id'] . '/propor')) ?>"><?= icon('pencil') ?>Enviar outra proposta</a></p>
