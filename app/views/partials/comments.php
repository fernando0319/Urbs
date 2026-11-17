<?php /** @var array $comments @var string $type @var int $id */ ?>
<section class="section" id="comentarios">
  <h2><?= icon('message') ?>Comentários <span class="muted">(<?= count($comments) ?>)</span></h2>
  <?php if (!$comments): ?><p class="muted">Ninguém comentou ainda. Comece a conversa.</p><?php endif ?>
  <ul class="comments">
    <?php foreach ($comments as $c): $a = author_of($c); ?>
    <li class="comment <?= $c['moderation'] !== 'approved' ? 'is-hidden' : '' ?>">
      <?= avatar($a) ?>
      <div class="comment-body">
        <div class="comment-head">
          <?php if (!$a['deleted_at']): ?><a href="<?= e(url('/perfil/' . $c['user_id'])) ?>"><strong><?= e(display_name($a)) ?></strong></a><?php else: ?><strong>Usuário removido</strong><?php endif ?>
          <?= user_badges($a) ?>
          <span class="muted"><?= e(time_ago($c['created_at'])) ?></span>
          <?= moderation_badge($c['moderation']) ?>
        </div>
        <div class="prose"><?= nl2p($c['body']) ?></div>
        <div class="comment-actions">
          <?php if ((int)$c['user_id'] === uid() || is_mod()): ?>
          <form method="post" action="<?= e(url('/comentarios/' . $c['id'] . '/excluir')) ?>" data-confirm="Remover este comentário?">
            <?= csrf_field() ?><button class="linklike">Remover</button>
          </form>
          <?php endif ?>
          <?php if ((int)$c['user_id'] !== uid()): ?><?= partial('partials/report', ['type' => 'comment', 'id' => (int)$c['id']]) ?><?php endif ?>
        </div>
      </div>
    </li>
    <?php endforeach ?>
  </ul>
  <?php if (current_user()): ?>
    <?php if (email_verified()): ?>
    <form method="post" action="<?= e(url('/comentar')) ?>" class="comment-form">
      <?= csrf_field() ?>
      <input type="hidden" name="target_type" value="<?= e($type) ?>">
      <input type="hidden" name="target_id" value="<?= (int)$id ?>">
      <label class="sr-only" for="comment-body">Seu comentário</label>
      <textarea id="comment-body" name="body" rows="3" maxlength="2000" required placeholder="Escreva com respeito. Críticas a ideias são bem-vindas; ataques a pessoas, não."></textarea>
      <button class="btn btn-primary">Comentar</button>
    </form>
    <?php else: ?><p class="muted">Confirme seu e-mail para comentar.</p><?php endif ?>
  <?php else: ?>
    <p><a href="<?= e(url('/entrar')) ?>">Entre</a> ou <a href="<?= e(url('/cadastro')) ?>">crie uma conta</a> para comentar.</p>
  <?php endif ?>
</section>
