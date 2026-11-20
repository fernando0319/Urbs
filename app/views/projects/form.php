<?php
/** @var array $p proposta de origem @var ?array $pj projeto (edição) @var array $errors */
$editing = $pj !== null;
$v = fn(string $k, mixed $d = '') => old($k, $editing ? ($pj[$k] ?? $d) : $d);
$moneyVal = fn(string $k) => ($x = $v($k)) !== '' && $x !== null && is_numeric($x) ? number_format((float)$x, 2, ',', '.') : (string)$v($k);
?>
<div class="narrow">
<a class="back" href="<?= e(url($editing ? '/projetos/' . $pj['id'] : '/propostas/' . $p['id'])) ?>"><?= icon('arrow-left') ?><?= e($editing ? $pj['title'] : $p['title']) ?></a>
<h1><?= $editing ? 'Editar projeto comunitário' : 'Transformar em projeto comunitário' ?></h1>
<?php if (!$editing): ?>
<p class="lead-sm">Um projeto comunitário organiza a realização da proposta <strong><?= e($p['title']) ?></strong>: quem é responsável, quanto custa, se há autorização, como contribuir e para onde foi cada real.</p>
<div class="flash flash-info">
  <strong>Importante:</strong> a plataforma <strong>não recebe nem administra dinheiro</strong>. A arrecadação acontece fora daqui (Benfeitoria, Catarse, conta ou Pix da organização responsável), e a plataforma registra o andamento e a prestação de contas.
  A arrecadação só pode ser aberta depois de três passos: a moderação aprova o projeto, verifica a organização responsável e a autorização do órgão responsável pelo espaço é registrada.
  Antes disso, as pessoas podem registrar <em>compromissos</em> de contribuição, sem pagar nada.
</div>
<?php endif ?>
<?= partial('partials/errors', ['errors' => $errors]) ?>

<form method="post" class="form">
  <?= csrf_field() ?>
  <fieldset>
    <legend>O projeto</legend>
    <label>Título
      <input name="title" required minlength="5" maxlength="160" value="<?= e($v('title', 'Projeto: ' . $p['title'])) ?>">
    </label>
    <label>Plano de execução
      <textarea name="plan" required minlength="30" rows="7" placeholder="O que será feito, em quais etapas, quem executa (empresa, prefeitura, mutirão), prazo estimado e quem cuida da manutenção depois."><?= e($v('plan')) ?></textarea>
    </label>
  </fieldset>

  <fieldset>
    <legend>Natureza da melhoria</legend>
    <p class="hint">Essa classificação aparece em destaque no projeto. Ela evita que a comunidade pague, sem perceber, por algo que já é dever da prefeitura.</p>
    <?php foreach (duty_types() as $k => [$label, $hint]): ?>
    <label class="check">
      <input type="radio" name="duty_type" value="<?= e($k) ?>" required <?= $v('duty_type') === $k ? 'checked' : '' ?>>
      <span><strong><?= e($label) ?></strong>: <?= e($hint) ?></span>
    </label>
    <?php endforeach ?>
  </fieldset>

  <fieldset>
    <legend>Organização responsável</legend>
    <p class="hint">Quem recebe o dinheiro e responde pelo projeto. O ideal é uma associação de moradores ou outra entidade com CNPJ. Evite arrecadar em conta pessoal.</p>
    <div class="row-2">
      <label>Nome da organização<input name="organization" required maxlength="160" value="<?= e($v('organization')) ?>" placeholder="Ex.: Associação de Moradores do Centro"></label>
      <label>CNPJ (recomendado)<input name="organization_doc" inputmode="numeric" maxlength="18" value="<?= e($v('organization_doc')) ?>" placeholder="00.000.000/0000-00"></label>
    </div>
    <?php if ($editing && $pj['organization_verified_at']): ?><p class="small ok-text"><?= icon('shield') ?>Organização verificada pela moderação. Alterar nome ou CNPJ exige nova verificação.</p><?php endif ?>
  </fieldset>

  <fieldset>
    <legend>Autorização</legend>
    <p class="hint">Obras em ruas, praças e prédios públicos exigem autorização do órgão responsável (normalmente a prefeitura). Em terreno particular, basta a permissão do dono.</p>
    <label>Situação
      <select name="auth_status" required>
        <?php foreach (auth_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $v('auth_status', 'not_requested') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
      </select>
    </label>
    <div class="row-2">
      <label>Órgão responsável<input name="auth_body" maxlength="160" value="<?= e($v('auth_body')) ?>" placeholder="Ex.: Secretaria de Obras"></label>
      <label>Número do protocolo<input name="auth_protocol" maxlength="80" value="<?= e($v('auth_protocol')) ?>"></label>
    </div>
    <?php if ($editing): ?>
    <div class="row-2">
      <label>Data do pedido<input type="date" name="auth_requested_at" value="<?= e($v('auth_requested_at')) ?>"></label>
      <label>Data da resposta<input type="date" name="auth_decided_at" value="<?= e($v('auth_decided_at')) ?>"></label>
    </div>
    <label>Observações (condições impostas, motivo de recusa, próximos passos)
      <textarea name="auth_notes" rows="3"><?= e($v('auth_notes')) ?></textarea>
    </label>
    <p class="hint">Anexe o protocolo e a resposta oficial na seção "Documentos" do projeto.</p>
    <?php endif ?>
  </fieldset>

  <fieldset>
    <legend>Financiamento</legend>
    <label>Meta em R$ (baseada nos orçamentos)<input name="goal_amount" inputmode="decimal" value="<?= e($moneyVal('goal_amount')) ?>" placeholder="Ex.: 12.500,00"></label>
    <?php if ($editing): ?>
    <label>Valor já arrecadado em R$<input name="raised_amount" inputmode="decimal" value="<?= e($moneyVal('raised_amount')) ?>">
      <small class="hint">Atualize sempre que conferir o extrato da organização. A data da atualização aparece no projeto.</small>
    </label>
    <label>Link da campanha (Benfeitoria, Catarse, outra plataforma)<input name="campaign_url" value="<?= e($v('campaign_url')) ?>" placeholder="https://"></label>
    <label>Outras formas de contribuir (Pix ou conta da organização)
      <textarea name="payment_info" rows="3" placeholder="Ex.: Pix (CNPJ) 00.000.000/0000-00, Associação de Moradores do Centro"><?= e($v('payment_info')) ?></textarea>
      <small class="hint">Só aparece publicamente com a arrecadação aberta. Use apenas contas em nome da organização responsável.</small>
    </label>
    <?php endif ?>
  </fieldset>

  <?php if ($editing): ?>
  <fieldset>
    <legend>Andamento</legend>
    <label>Status
      <select name="status">
        <?php foreach (project_statuses() as $k => $label): ?><option value="<?= e($k) ?>" <?= $v('status') === $k ? 'selected' : '' ?>><?= e($label) ?></option><?php endforeach ?>
      </select>
    </label>
    <?php if ($blockers = fundraising_blockers($pj)): ?>
      <p class="hint">Para abrir a arrecadação ainda falta: <?= e(implode('; ', $blockers)) ?>.</p>
    <?php endif ?>
  </fieldset>
  <?php else: ?>
  <label class="check"><input type="checkbox" name="no_money_consent" value="1" required <?= old('no_money_consent') ? 'checked' : '' ?>><span>Entendo que a plataforma não recebe, não guarda e não repassa dinheiro, e que a arrecadação é de responsabilidade da organização indicada.</span></label>
  <label class="check"><input type="checkbox" name="accountability_consent" value="1" required <?= old('accountability_consent') ? 'checked' : '' ?>><span>Responsabilizo-me pela veracidade das informações e por publicar a prestação de contas (despesas e comprovantes).</span></label>
  <?php endif ?>

  <button class="btn btn-primary btn-lg btn-block"><?= $editing ? 'Salvar alterações' : 'Criar projeto' ?></button>
</form>
</div>
