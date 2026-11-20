<?php /** @var array $p @var array $suggestions */ $isAuthor = (int)$p['user_id'] === uid(); ?>
<section class="section" id="sugestoes">
  <div class="section-head">
    <h2><?= icon('pencil') ?>Sugestões de alteração <span class="muted">(<?= count($suggestions) ?>)</span></h2>
  </div>
  <p class="muted small">Viu algo que poderia melhorar, como mais sombra, acessibilidade ou um material mais barato? Sugira ao autor. Ele pode responder, analisar e incorporar a sugestão numa nova versão da proposta, e o histórico fica registrado.</p>

  <?php if ($suggestions): ?>
  <ul class="suggestions">
    <?php foreach ($suggestions as $s): $a = author_of($s); ?>
    <li class="suggestion <?= $s['moderation'] !== 'approved' ? 'is-hidden' : '' ?>" id="sugestao-<?= (int)$s['id'] ?>">
      <div class="suggestion-head">
        <?= avatar($a) ?>
        <strong><?= e(display_name($a)) ?></strong><?= user_badges($a) ?>
        <span class="muted small"><?= e(time_ago($s['created_at'])) ?></span>
        <span class="pill <?= e('sugg-' . $s['status']) ?>"><?= e(suggestion_statuses()[$s['status']] ?? '') ?></span>
        <?= moderation_badge($s['moderation']) ?>
      </div>
      <div class="prose"><?= nl2p($s['body']) ?></div>
      <?php if ($s['image']): ?><a href="<?= e(upload_url($s['image'])) ?>" target="_blank"><img class="suggestion-img" src="<?= e(upload_url($s['image_thumb'])) ?>" alt="Imagem da sugestão" loading="lazy"></a><?php endif ?>
      <?php if ($s['author_reply']): ?>
        <blockquote class="author-reply"><strong>Resposta do autor:</strong> <?= e($s['author_reply']) ?></blockquote>
      <?php endif ?>
      <div class="suggestion-actions">
        <?php if (current_user() && email_verified() && (int)$s['user_id'] !== uid()): ?>
        <form method="post" action="<?= e(url('/sugestoes/' . $s['id'] . '/apoiar')) ?>">
          <?= csrf_field() ?>
          <button class="btn btn-sm <?= !empty($s['supported']) ? 'btn-supported' : '' ?>"><?= icon('thumb') ?><?= (int)$s['support_count'] ?> <?= !empty($s['supported']) ? '· você apoia' : '· apoiar' ?></button>
        </form>
        <?php else: ?>
          <span class="muted small"><?= icon('thumb') ?><?= plural((int)$s['support_count'], 'apoio', 'apoios') ?></span>
        <?php endif ?>
        <?php if ($isAuthor || is_mod()): ?>
        <details class="reply-box">
          <summary class="linklike">Responder</summary>
          <form method="post" action="<?= e(url('/sugestoes/' . $s['id'] . '/responder')) ?>" class="form form-tight">
            <?= csrf_field() ?>
            <select name="status" aria-label="Situação">
              <?php foreach (suggestion_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $s['status'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
            </select>
            <textarea name="reply" rows="2" maxlength="1000" placeholder="Explique sua decisão"><?= e($s['author_reply']) ?></textarea>
            <button class="btn btn-sm btn-primary">Salvar resposta</button>
          </form>
        </details>
        <?php endif ?>
        <?php if ((int)$s['user_id'] === uid() || is_mod()): ?>
        <form method="post" action="<?= e(url('/sugestoes/' . $s['id'] . '/excluir')) ?>" data-confirm="Remover esta sugestão?">
          <?= csrf_field() ?><button class="linklike">Remover</button>
        </form>
        <?php endif ?>
        <?php if ((int)$s['user_id'] !== uid()): ?><?= partial('partials/report', ['type' => 'suggestion', 'id' => (int)$s['id']]) ?><?php endif ?>
      </div>
    </li>
    <?php endforeach ?>
  </ul>
  <?php endif ?>

  <?php if ($p['moderation'] === 'approved' && !$isAuthor): ?>
    <?php if (current_user() && email_verified()): ?>
    <form method="post" action="<?= e(url('/propostas/' . $p['id'] . '/sugerir')) ?>" enctype="multipart/form-data" class="card form form-tight">
      <?= csrf_field() ?>
      <label>Sua sugestão
        <textarea name="body" required minlength="10" maxlength="2000" rows="3" placeholder="Ex.: Trocar o piso de concreto por blocos drenantes, para a água da chuva infiltrar."></textarea>
      </label>
      <label class="file-drop"><?= icon('image') ?><span>Imagem ou croqui (opcional)</span><input type="file" name="image" accept="image/*" data-compress data-preview="sugg-preview"></label>
      <div id="sugg-preview" class="thumbs"></div>
      <button class="btn btn-primary btn-sm">Enviar sugestão</button>
    </form>
    <?php elseif (!current_user()): ?>
      <p><a href="<?= e(url('/entrar')) ?>">Entre</a> para sugerir alterações.</p>
    <?php endif ?>
  <?php endif ?>
</section>
