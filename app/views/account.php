<?php
$pendingByType = [];
foreach ($requests as $r) {
    if ($r['status'] === 'pending') $pendingByType[$r['type']] = true;
}
?>
<div class="narrow">
<div class="page-head">
  <div class="author-line"><?= avatar($u, '', 'lg') ?><div><h1><?= e($u['name']) ?></h1><div class="tags"><?= user_badges($u) ?></div></div></div>
  <a class="btn btn-ghost btn-sm" href="<?= e(url('/perfil/' . $u['id'])) ?>">Ver perfil público</a>
</div>
<?= partial('partials/errors', ['errors' => $errors]) ?>

<?php if (!$u['email_verified_at']): ?>
<section class="card card-warn">
  <h2>Confirme seu e-mail</h2>
  <p>Enviamos um link para <strong><?= e($u['email']) ?></strong>. Sem a confirmação você pode navegar, mas não pode publicar, apoiar, votar nem comentar.</p>
  <form method="post" action="<?= e(url('/conta/reenviar')) ?>"><?= csrf_field() ?><button class="btn btn-sm">Reenviar link</button></form>
</section>
<?php endif ?>

<?php if ($pendingIssues || $pendingProposals): ?>
<section class="card">
  <h2>Suas publicações em moderação</h2>
  <ul class="plain-list">
    <?php foreach ($pendingIssues as $i): ?><li><a href="<?= e(url('/ocorrencias/' . $i['id'])) ?>"><?= e($i['title']) ?></a> <?= moderation_badge($i['moderation']) ?></li><?php endforeach ?>
    <?php foreach ($pendingProposals as $p): ?><li><a href="<?= e(url('/propostas/' . $p['id'])) ?>"><?= e($p['title']) ?></a> <?= moderation_badge($p['moderation']) ?></li><?php endforeach ?>
  </ul>
</section>
<?php endif ?>

<section class="card">
  <h2>Perfil</h2>
  <form method="post" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>
    <label>Nome<input name="name" required maxlength="120" value="<?= e($u['name']) ?>"></label>
    <label>Como você se identifica
      <select name="profile_type"><?php foreach (profile_types() as $k => $label): ?><option value="<?= e($k) ?>" <?= $u['profile_type'] === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?></select>
    </label>
    <label>Sobre você (opcional)<textarea name="bio" rows="3" maxlength="1000" placeholder="Formação, portfólio, bairro onde mora…"><?= e($u['bio']) ?></textarea></label>
    <label>Foto de perfil (opcional)<input type="file" name="avatar" accept="image/*" data-compress></label>
    <button class="btn btn-primary">Salvar perfil</button>
  </form>
</section>

<section class="card">
  <h2>Selos de verificação</h2>
  <p class="muted">Selos são opcionais. Eles aumentam a credibilidade das suas propostas e apoios. Toda conta pode propor e votar mesmo sem selo.</p>

  <div class="verify-block">
    <h3><?= icon('shield') ?>Profissional verificado</h3>
    <?php if ($u['professional_verified_at']): ?>
      <p class="ok-text">Verificado em <?= e(fmt_date($u['professional_verified_at'])) ?><?= $u['registry'] ? ' · ' . e($u['registry']) : '' ?></p>
    <?php elseif (!empty($pendingByType['professional'])): ?>
      <p class="muted">Pedido em análise.</p>
    <?php else: ?>
      <p class="small">Para arquitetos, urbanistas e engenheiros. A moderação confere seu registro no site do CAU ou do CREA.</p>
      <form method="post" action="<?= e(url('/conta/verificacao')) ?>" class="form form-tight">
        <?= csrf_field() ?><input type="hidden" name="type" value="professional">
        <label>Número do registro<input name="registry" required maxlength="60" placeholder="Ex.: CAU A123456-7 ou CREA-PR 12345/D"></label>
        <label>Observações (opcional)<input name="notes" maxlength="1000" placeholder="Link para portfólio, por exemplo"></label>
        <button class="btn btn-sm">Pedir verificação</button>
      </form>
    <?php endif ?>
  </div>

  <div class="verify-block">
    <h3><?= icon('home') ?>Morador verificado</h3>
    <?php if ($u['resident_verified_at']): ?>
      <p class="ok-text">Verificado em <?= e(fmt_date($u['resident_verified_at'])) ?></p>
    <?php elseif (!empty($pendingByType['resident'])): ?>
      <p class="muted">Pedido em análise.</p>
    <?php else: ?>
      <p class="small">Seus apoios e preferências passam a ser contados também entre os de "moradores verificados". Envie um comprovante de endereço em <?= e(setting('city_name')) ?>. <strong>Só a moderação vê o documento, e ele é apagado logo depois da análise.</strong> Você pode cobrir os dados que não sejam nome e endereço.</p>
      <form method="post" action="<?= e(url('/conta/verificacao')) ?>" enctype="multipart/form-data" class="form form-tight">
        <?= csrf_field() ?><input type="hidden" name="type" value="resident">
        <label>Comprovante (PDF, JPG ou PNG)<input type="file" name="document" required accept="application/pdf,image/*"></label>
        <button class="btn btn-sm">Enviar para análise</button>
      </form>
    <?php endif ?>
  </div>
</section>

<section class="card">
  <h2>Senha</h2>
  <form method="post" action="<?= e(url('/conta/senha')) ?>" class="form form-tight">
    <?= csrf_field() ?>
    <label>Senha atual<input type="password" name="current" required autocomplete="current-password"></label>
    <label>Nova senha<input type="password" name="password" required minlength="8" autocomplete="new-password"></label>
    <button class="btn btn-sm">Alterar senha</button>
  </form>
</section>

<section class="card">
  <h2>Seus dados (LGPD)</h2>
  <p><a class="btn btn-sm" href="<?= e(url('/conta/dados')) ?>"><?= icon('download') ?>Baixar meus dados (JSON)</a></p>
  <details class="danger-zone">
    <summary>Excluir minha conta</summary>
    <form method="post" action="<?= e(url('/conta/excluir')) ?>" class="form form-tight" data-confirm="Tem certeza? Esta ação não pode ser desfeita.">
      <?= csrf_field() ?>
      <p class="small">Seu nome, e-mail, foto, apoios, preferências e comentários serão apagados. Suas ocorrências e propostas ficam publicadas como "Usuário removido", a não ser que você marque a opção abaixo.</p>
      <label class="check"><input type="checkbox" name="delete_content" value="1"><span>Também apagar minhas ocorrências e propostas. Ocorrências que já têm propostas de outras pessoas ficam, mas anônimas.</span></label>
      <label>Confirme sua senha<input type="password" name="password" required autocomplete="current-password"></label>
      <button class="btn btn-danger btn-sm">Excluir conta definitivamente</button>
    </form>
  </details>
</section>

<form method="post" action="<?= e(url('/sair')) ?>" class="center"><?= csrf_field() ?><button class="btn btn-ghost"><?= icon('logout') ?>Sair</button></form>
</div>
