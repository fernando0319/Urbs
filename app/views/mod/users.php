<?= partial('mod/_tabs', ['tab' => $tab, 'counts' => $counts]) ?>
<form class="filters" method="get" action="<?= e(url('/moderacao/usuarios')) ?>">
  <?php if (!config('pretty_urls', true)): ?><input type="hidden" name="r" value="/moderacao/usuarios"><?php endif ?>
  <input type="search" name="q" value="<?= e(input('q')) ?>" placeholder="Nome ou e-mail" aria-label="Buscar">
  <select name="filter" aria-label="Filtro">
    <option value="">Todos</option>
    <option value="verified" <?= input('filter') === 'verified' ? 'selected' : '' ?>>Com selo</option>
    <option value="staff" <?= input('filter') === 'staff' ? 'selected' : '' ?>>Equipe</option>
    <option value="blocked" <?= input('filter') === 'blocked' ? 'selected' : '' ?>>Bloqueados</option>
  </select>
  <button class="btn"><?= icon('search') ?>Buscar</button>
</form>
<div class="table-wrap">
<table class="table">
  <thead><tr><th>Usuário</th><th>Perfil</th><th>Situação</th><th>Ações</th></tr></thead>
  <tbody>
  <?php foreach ($users as $usr): $self = (int)$usr['id'] === uid(); ?>
  <tr>
    <td><a href="<?= e(url('/perfil/' . $usr['id'])) ?>"><strong><?= e($usr['name']) ?></strong></a><br><span class="muted small"><?= e($usr['email']) ?></span></td>
    <td><span class="small"><?= e(profile_types()[$usr['profile_type']] ?? '') ?></span><div class="tags"><?= user_badges($usr) ?></div></td>
    <td class="small">
      <?php if ($usr['blocked_at']): ?><span class="pill pill-danger">Bloqueado</span><?= $usr['block_reason'] ? '<br>' . e($usr['block_reason']) : '' ?>
      <?php elseif (!$usr['email_verified_at']): ?><span class="pill pill-warn">E-mail não confirmado</span>
      <?php else: ?>Ativo<?php endif ?>
      <br><span class="muted">desde <?= e(fmt_date($usr['created_at'])) ?></span>
    </td>
    <td>
      <?php if (!$self): ?>
      <form method="post" action="<?= e(url('/moderacao/usuarios/' . $usr['id'])) ?>" class="inline-form">
        <?= csrf_field() ?>
        <?php if ($usr['blocked_at']): ?>
          <button class="btn btn-sm" name="action" value="unblock">Desbloquear</button>
        <?php else: ?>
          <input name="note" maxlength="255" placeholder="Motivo do bloqueio">
          <button class="btn btn-sm btn-danger" name="action" value="block" data-confirm="Bloquear este usuário?">Bloquear</button>
        <?php endif ?>
        <?php if ($usr['professional_verified_at']): ?><button class="btn btn-sm" name="action" value="revoke_professional">Remover selo profissional</button><?php endif ?>
        <?php if ($usr['resident_verified_at']): ?><button class="btn btn-sm" name="action" value="revoke_resident">Remover selo de morador</button><?php endif ?>
      </form>
      <?php if (is_admin()): ?>
      <form method="post" action="<?= e(url('/moderacao/usuarios/' . $usr['id'])) ?>" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="role">
        <select name="role" aria-label="Papel">
          <?php foreach (['user' => 'Participante', 'moderator' => 'Moderador', 'admin' => 'Administrador'] as $k => $label): ?><option value="<?= $k ?>" <?= $usr['role'] === $k ? 'selected' : '' ?>><?= $label ?></option><?php endforeach ?>
        </select>
        <button class="btn btn-sm">Alterar papel</button>
      </form>
      <?php endif ?>
      <?php else: ?><span class="muted small">Você</span><?php endif ?>
    </td>
  </tr>
  <?php endforeach ?>
  </tbody>
</table>
</div>
