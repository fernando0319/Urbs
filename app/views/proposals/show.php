<?php $a = author_of($p); $isAuthor = (int)$p['user_id'] === uid(); $lic = licenses()[$p['license']] ?? [$p['license'], '', '']; ?>
<?php ob_start(); ?>
    <div class="card support-card">
      <p class="support-count"><strong><?= (int)$p['preference_count'] ?></strong> <?= (int)$p['preference_count'] === 1 ? 'pessoa prefere' : 'pessoas preferem' ?> esta proposta</p>
      <?php if ((int)$p['preference_verified_count']): ?><p class="muted small"><?= (int)$p['preference_verified_count'] ?> moradores verificados</p><?php endif ?>
      <?php if (current_user()): ?>
      <form method="post" action="<?= e(url('/ocorrencias/' . $p['issue_id'] . '/preferir')) ?>">
        <?= csrf_field() ?><input type="hidden" name="proposal_id" value="<?= (int)$p['id'] ?>">
        <button class="btn btn-block <?= $preferred ? 'btn-supported' : 'btn-primary' ?>"><?= icon($preferred ? 'check' : 'thumb') ?><?= $preferred ? 'Sua preferida (desfazer)' : 'Prefiro esta' ?></button>
      </form>
      <?php else: ?>
        <a class="btn btn-primary btn-block" href="<?= e(url('/entrar')) ?>">Entrar para escolher</a>
      <?php endif ?>
      <p class="muted small">Você escolhe uma proposta por lugar. Resultado não vinculante.</p>
    </div>
<?php $actionCard = ob_get_clean(); ?>
<a class="back" href="<?= e(url('/ocorrencias/' . $p['issue_id'])) ?>"><?= icon('arrow-left') ?><?= e($p['issue_title']) ?></a>

<?php if ($p['moderation'] !== 'approved'): ?>
  <div class="flash flash-info"><?= moderation_badge($p['moderation']) ?> Só você e a moderação veem esta proposta por enquanto.</div>
<?php endif ?>

<div class="detail">
  <div class="detail-main">
    <p class="eyebrow">Proposta para <?= e($p['issue_title']) ?></p>
    <h1><?= e($p['title']) ?></h1>
    <?php if ($p['moderation'] === 'approved'): ?><div class="only-mobile"><?= $actionCard ?></div><?php endif ?>
    <p class="lead-sm"><?= e($p['summary']) ?></p>

    <?php if ($p['before_file']): ?>
    <figure class="ba" data-before-after>
      <img class="ba-after" src="<?= e(upload_url($p['after_file'])) ?>" alt="Depois: como ficaria">
      <div class="ba-before-wrap"><img class="ba-before" src="<?= e(upload_url($p['before_file'])) ?>" alt="Antes: situação atual"></div>
      <span class="ba-label ba-label-l">Antes</span><span class="ba-label ba-label-r">Depois</span>
      <input class="ba-range" type="range" min="0" max="100" value="50" aria-label="Comparar antes e depois">
      <span class="ba-handle" aria-hidden="true"></span>
    </figure>
    <figcaption class="muted small">Arraste para comparar o antes e o depois.</figcaption>
    <?php else: ?>
    <figure class="hero-img"><img src="<?= e(upload_url($p['after_file'])) ?>" alt="Imagem da proposta"></figure>
    <?php endif ?>

    <div class="prose"><?= nl2p($p['description']) ?></div>

    <?php if ($images): ?>
    <section class="section">
      <h2><?= icon('image') ?>Imagens</h2>
      <div class="gallery">
        <?php foreach ($images as $img): ?>
          <a href="<?= e(upload_url($img['file'])) ?>" target="_blank"><img src="<?= e(upload_url($img['thumb'])) ?>" alt="" loading="lazy"></a>
        <?php endforeach ?>
      </div>
    </section>
    <?php endif ?>

    <?php if ($pdfs): ?>
    <section class="section">
      <h2><?= icon('file') ?>Plantas e documentos</h2>
      <ul class="file-list">
        <?php foreach ($pdfs as $f): ?>
        <li><a href="<?= e(upload_url($f['file'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?><?= e($f['original_name'] ?: 'documento.pdf') ?></a> <span class="muted small"><?= number_format($f['size'] / 1048576, 1, ',', '.') ?> MB</span></li>
        <?php endforeach ?>
      </ul>
    </section>
    <?php endif ?>

    <?php if ($siblings): ?>
    <section class="section">
      <div class="section-head">
        <h2>Outras propostas para o mesmo lugar</h2>
        <a class="btn btn-sm" href="<?= e(url('/ocorrencias/' . $p['issue_id'] . '/comparar')) ?>"><?= icon('compare') ?>Comparar todas</a>
      </div>
      <div class="mini-cards">
        <?php foreach ($siblings as $s): ?>
        <a class="mini-card" href="<?= e(url('/propostas/' . $s['id'])) ?>"><img src="<?= e(upload_url($s['cover_thumb'])) ?>" alt="" loading="lazy"><span><?= e($s['title']) ?></span></a>
        <?php endforeach ?>
      </div>
    </section>
    <?php endif ?>

    <?php if ($projects): ?>
    <section class="section">
      <h2><?= icon('users') ?>Projeto comunitário</h2>
      <div class="grid grid-2"><?php foreach ($projects as $pj) echo partial('partials/project_card', ['pj' => $pj]); ?></div>
    </section>
    <?php endif ?>

    <?php if ($p['moderation'] === 'approved'): ?>
      <?= partial('partials/suggestions', ['p' => $p, 'suggestions' => $suggestions]) ?>
    <?php endif ?>

    <?php if ($revisions): ?>
    <section class="section" id="versoes">
      <h2><?= icon('clock') ?>Histórico de versões</h2>
      <ol class="timeline">
        <?php foreach ($revisions as $r): ?>
        <li>
          <span class="timeline-date"><?= e(fmt_date($r['created_at'], true)) ?></span>
          <div><?= e($r['note']) ?>
            <?php if ($r['incorporated']): ?><span class="muted small"> · incorporou <?= plural(count(explode(',', $r['incorporated'])), 'sugestão', 'sugestões') ?></span><?php endif ?>
          </div>
          <?php if ($r['prev_after_file']): ?><a href="<?= e(upload_url($r['prev_after_file'])) ?>" target="_blank" class="small">Ver imagem da versão anterior</a><?php endif ?>
        </li>
        <?php endforeach ?>
        <li><span class="timeline-date"><?= e(fmt_date($p['created_at'], true)) ?></span><div>Primeira versão publicada</div></li>
      </ol>
    </section>
    <?php endif ?>

    <?php if ($p['moderation'] === 'approved'): ?>
      <?= partial('partials/comments', ['comments' => $comments, 'type' => 'proposal', 'id' => (int)$p['id']]) ?>
    <?php endif ?>
  </div>

  <aside class="detail-side">
    <div class="card">
      <h3>Autoria</h3>
      <div class="author-line">
        <?= avatar($a, '', 'md') ?>
        <div>
          <?php if (!$a['deleted_at']): ?><a href="<?= e(url('/perfil/' . $p['user_id'])) ?>"><strong><?= e(display_name($a)) ?></strong></a><?php else: ?><strong>Usuário removido</strong><?php endif ?>
          <div class="muted small"><?= e(profile_types()[$p['author_profile_type']] ?? '') ?></div>
        </div>
      </div>
      <div class="tags"><?= user_badges($a) ?></div>
      <dl class="facts">
        <dt>Publicada em</dt><dd><?= e(fmt_date($p['created_at'])) ?></dd>
        <?php if ($revisions): ?><dt>Versão</dt><dd><a href="#versoes"><?= count($revisions) + 1 ?>ª, de <?= e(fmt_date($revisions[0]['created_at'])) ?></a></dd><?php endif ?>
        <dt>Custo estimado</dt><dd><?= $p['estimated_cost'] !== null ? e(money($p['estimated_cost'])) : 'Não informado' ?><?php if ($p['cost_notes']): ?><br><span class="muted small"><?= e($p['cost_notes']) ?></span><?php endif ?></dd>
        <dt>Licença</dt><dd><?php if ($lic[2]): ?><a href="<?= e($lic[2]) ?>" target="_blank" rel="noopener"><?= e($lic[0]) ?></a><?php else: ?><?= e($lic[0]) ?><?php endif ?><br><span class="muted small"><?= e($lic[1]) ?></span></dd>
      </dl>
    </div>

    <?php if ($isAuthor): ?>
      <a class="btn btn-block" href="<?= e(url('/propostas/' . $p['id'] . '/editar')) ?>"><?= icon('pencil') ?>Editar proposta (nova versão)</a>
    <?php endif ?>

    <?php if ($p['moderation'] === 'approved' && !$projects && (($p['license'] ?? '') !== 'arr' || $isAuthor || is_mod())): ?>
    <div class="card">
      <h3>Tirar do papel</h3>
      <p class="small">A comunidade gostou desta proposta? Organize um projeto comunitário com orçamento, autorização, contribuições e prestação de contas.</p>
      <a class="btn btn-block" href="<?= e(url('/propostas/' . $p['id'] . '/projeto')) ?>"><?= icon('users') ?>Transformar em projeto comunitário</a>
    </div>
    <?php endif ?>

    <?php if ($p['moderation'] === 'approved'): ?>
    <div class="only-desktop"><?= $actionCard ?></div>
    <?php endif ?>

    <div class="card notice-card">
      <p class="small"><?= icon('info') ?>Proposta conceitual. Uma obra de verdade exige projeto executivo e responsabilidade técnica (RRT/ART) de profissional habilitado.</p>
    </div>

    <button type="button" class="btn btn-block" data-share data-title="<?= e($p['title']) ?>">Compartilhar</button>

    <?php if (is_mod() && $p['moderation'] !== 'approved'): ?>
    <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="card form form-tight">
      <?= csrf_field() ?><input type="hidden" name="target_type" value="proposal"><input type="hidden" name="target_id" value="<?= (int)$p['id'] ?>">
      <button class="btn btn-sm btn-primary" name="action" value="approve">Aprovar proposta</button>
    </form>
    <?php elseif (is_mod()): ?>
    <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="card form form-tight" data-confirm="Ocultar esta proposta?">
      <?= csrf_field() ?><input type="hidden" name="target_type" value="proposal"><input type="hidden" name="target_id" value="<?= (int)$p['id'] ?>">
      <input name="note" placeholder="Motivo (fica no registro público)" maxlength="500">
      <button class="btn btn-sm btn-danger" name="action" value="hide">Ocultar proposta</button>
    </form>
    <?php endif ?>

    <?php if ($isAuthor || is_mod()): ?>
    <form method="post" action="<?= e(url('/propostas/' . $p['id'] . '/excluir')) ?>" data-confirm="Excluir esta proposta definitivamente?">
      <?= csrf_field() ?><button class="linklike danger">Excluir proposta</button>
    </form>
    <?php endif ?>
    <?php if (!$isAuthor): ?><?= partial('partials/report', ['type' => 'proposal', 'id' => (int)$p['id']]) ?><?php endif ?>
  </aside>
</div>
