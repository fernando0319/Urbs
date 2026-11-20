<?php
$a = author_of($pj);
[$dutyLabel, $dutyHint] = duty_types()[$pj['duty_type']] ?? ['', ''];
$goal = $pj['goal_amount'] !== null ? (float)$pj['goal_amount'] : null;
$raised = (float)$pj['raised_amount'];
$pct = $goal ? min(100, round($raised / $goal * 100, 1)) : 0;
$canFund = $pj['status'] === 'fundraising' && !$blockers;
$active = !in_array($pj['status'], ['done', 'cancelled'], true);
$budgets = array_values(array_filter($documents, fn($d) => $d['kind'] === 'budget'));
$otherDocs = array_values(array_filter($documents, fn($d) => $d['kind'] !== 'budget'));
?>
<?php ob_start(); ?>
    <div class="card funding-card">
      <p class="funding-raised"><strong><?= e(money($raised)) ?></strong><?php if ($goal): ?> de <?= e(money($goal)) ?><?php endif ?></p>
      <?php if ($goal): ?>
        <div class="bar bar-lg" role="img" aria-label="<?= $pct ?>% da meta"><span style="width:<?= $pct ?>%"></span></div>
        <p class="small"><strong><?= number_format($pct, 1, ',', '.') ?>%</strong> da meta</p>
      <?php endif ?>
      <p class="muted small"><?= $pj['raised_updated_at'] ? 'Valor informado pela organização em ' . e(fmt_date($pj['raised_updated_at'])) : 'Nenhum valor arrecadado informado ainda' ?>.</p>
      <?php if ($totals['committed'] > 0): ?>
        <p class="small"><?= icon('users') ?><strong><?= e(money($totals['committed'])) ?></strong> em compromissos de <?= plural($totals['committed_people'], 'pessoa', 'pessoas') ?></p>
      <?php endif ?>

      <?php if ($canFund): ?>
        <?php if ($pj['campaign_url']): ?>
          <a class="btn btn-primary btn-block" href="<?= e($pj['campaign_url']) ?>" target="_blank" rel="noopener nofollow">Contribuir na campanha</a>
        <?php endif ?>
        <?php if ($pj['payment_info']): ?>
          <div class="payment-info"><p class="label">Contribuição direta à organização</p><div class="prose small"><?= nl2p($pj['payment_info']) ?></div></div>
        <?php endif ?>
        <p class="muted small">Confira se o recebedor é <strong><?= e($pj['organization']) ?></strong>. A plataforma não recebe nem repassa valores.</p>
      <?php elseif ($active && $pj['status'] !== 'executing'): ?>
        <a class="btn btn-block" href="#ofertas">Registrar compromisso</a>
        <?php if ($blockers): ?><p class="muted small">Arrecadação fechada. Ainda falta: <?= e(implode('; ', $blockers)) ?>.</p><?php endif ?>
      <?php endif ?>
    </div>
<?php $actionCard = ob_get_clean(); ?>
<a class="back" href="<?= e(url('/propostas/' . $pj['proposal_id'])) ?>"><?= icon('arrow-left') ?>Proposta: <?= e($pj['proposal_title']) ?></a>

<?php if ($pj['moderation'] !== 'approved'): ?>
  <div class="flash flash-info"><?= moderation_badge($pj['moderation']) ?> Por envolver dinheiro, todo projeto passa pela moderação. Por enquanto só você e a moderação veem esta página. Você já pode anexar orçamentos e documentos.</div>
<?php endif ?>

<div class="detail">
  <div class="detail-main">
    <div class="tags">
      <span class="pill <?= e('pstatus-' . $pj['status']) ?>"><?= e(project_statuses()[$pj['status']] ?? $pj['status']) ?></span>
      <span class="pill <?= $pj['duty_type'] === 'municipal_duty' ? 'pill-warn' : '' ?>"><?= e($dutyLabel) ?></span>
    </div>
    <p class="eyebrow">Projeto comunitário · <?= e($pj['issue_title']) ?></p>
    <h1><?= e($pj['title']) ?></h1>
    <div class="only-mobile"><?= $actionCard ?></div>

    <?php if ($pj['duty_type'] === 'municipal_duty'): ?>
    <div class="flash flash-warn">
      <strong>Esta melhoria é uma obrigação do município.</strong> Ela deveria ser paga com o orçamento público.
      A iniciativa da comunidade não tira essa responsabilidade da prefeitura, e vale continuar cobrando pelos canais oficiais (veja <a href="<?= e(url('/participar')) ?>">como participar</a>).
    </div>
    <?php endif ?>

    <figure class="hero-img"><a href="<?= e(url('/propostas/' . $pj['proposal_id'])) ?>"><img src="<?= e(upload_url($pj['proposal_after_file'])) ?>" alt="Imagem da proposta"></a></figure>

    <section class="section">
      <h2><?= icon('list') ?>Plano de execução</h2>
      <div class="prose"><?= nl2p($pj['plan']) ?></div>
    </section>

    <section class="section" id="autorizacao">
      <h2><?= icon('shield') ?>Autorização</h2>
      <div class="card">
        <p><span class="pill <?= e('auth-' . $pj['auth_status']) ?>"><?= e(auth_statuses()[$pj['auth_status']] ?? '') ?></span></p>
        <dl class="facts">
          <?php if ($pj['auth_body']): ?><dt>Órgão</dt><dd><?= e($pj['auth_body']) ?></dd><?php endif ?>
          <?php if ($pj['auth_protocol']): ?><dt>Protocolo</dt><dd><?= e($pj['auth_protocol']) ?></dd><?php endif ?>
          <?php if ($pj['auth_requested_at']): ?><dt>Pedido em</dt><dd><?= e(fmt_day($pj['auth_requested_at'])) ?></dd><?php endif ?>
          <?php if ($pj['auth_decided_at']): ?><dt>Resposta em</dt><dd><?= e(fmt_day($pj['auth_decided_at'])) ?></dd><?php endif ?>
        </dl>
        <?php if ($pj['auth_notes']): ?><div class="prose small"><?= nl2p($pj['auth_notes']) ?></div><?php endif ?>
        <?php if ($pj['auth_status'] === 'requested' && $totals['committed'] > 0): ?>
          <p class="callout-note"><?= icon('clock') ?>A comunidade já tem <strong><?= e(money($totals['committed'])) ?></strong> em compromissos de contribuição e aguarda a resposta do órgão responsável.</p>
        <?php elseif ($pj['auth_status'] === 'denied'): ?>
          <p class="small">Uma recusa não precisa encerrar o projeto. É possível pedir os fundamentos da decisão, ajustar a proposta e protocolar de novo, ou recorrer pelos canais administrativos. Veja <a href="<?= e(url('/participar')) ?>">como participar</a>.</p>
        <?php endif ?>
      </div>
    </section>

    <section class="section" id="ofertas">
      <h2><?= icon('users') ?>Ofertas e compromissos</h2>
      <p class="muted small">Contribuir não é só com dinheiro: máquina, material, trabalho voluntário ou um estudo técnico também contam. Compromissos em dinheiro são <strong>intenções</strong>: ninguém paga nada aqui, e quem se comprometeu é avisado por e-mail quando a arrecadação abrir.</p>
      <?php if ($pledges): ?>
      <ul class="pledges">
        <?php foreach ($pledges as $pl): $mine = (int)$pl['user_id'] === uid(); ?>
        <li class="pledge">
          <div>
            <strong><?= e(pledge_kinds()[$pl['kind']] ?? $pl['kind']) ?><?= $pl['amount'] !== null ? ' · ' . e(money($pl['amount'])) : '' ?></strong>
            <span class="muted small">por <?= e(display_name($pl, 'author_')) ?> · <?= e(time_ago($pl['created_at'])) ?></span>
            <?php if ($pl['kind'] !== 'money' || $pl['description'] !== 'Compromisso de contribuição'): ?><p class="small"><?= e($pl['description']) ?></p><?php endif ?>
          </div>
          <div class="pledge-side">
            <span class="pill <?= e('pledge-' . $pl['status']) ?>"><?= e(pledge_statuses()[$pl['status']] ?? '') ?></span>
            <?php if ($manage): ?>
            <form method="post" action="<?= e(url('/ofertas/' . $pl['id'] . '/status')) ?>" class="inline-form">
              <?= csrf_field() ?>
              <select name="status" aria-label="Situação da oferta">
                <?php foreach (['offered', 'accepted', 'delivered', 'declined'] as $st): ?><option value="<?= $st ?>" <?= $pl['status'] === $st ? 'selected' : '' ?>><?= e(pledge_statuses()[$st]) ?></option><?php endforeach ?>
              </select>
              <button class="btn btn-sm">Salvar</button>
            </form>
            <?php elseif ($mine): ?>
            <form method="post" action="<?= e(url('/ofertas/' . $pl['id'] . '/status')) ?>" data-confirm="Retirar esta oferta?">
              <?= csrf_field() ?><input type="hidden" name="status" value="withdrawn"><button class="linklike">Retirar</button>
            </form>
            <?php endif ?>
          </div>
        </li>
        <?php endforeach ?>
      </ul>
      <?php else: ?><p class="muted">Nenhuma oferta ainda.</p><?php endif ?>

      <?php if ($active && $pj['moderation'] === 'approved'): ?>
        <?php if (current_user() && email_verified()): ?>
        <details class="card offer-form" <?= $pledges ? '' : 'open' ?>>
          <summary><strong>Quero contribuir</strong></summary>
          <form method="post" action="<?= e(url('/projetos/' . $pj['id'] . '/ofertas')) ?>" class="form form-tight">
            <?= csrf_field() ?>
            <label>Tipo
              <select name="kind" required>
                <?php foreach (pledge_kinds() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach ?>
              </select>
            </label>
            <label>Valor em R$ (obrigatório para dinheiro; estimado para os demais)<input name="amount" inputmode="decimal" placeholder="Ex.: 100,00"></label>
            <label>Descrição<input name="description" maxlength="500" placeholder="Ex.: retroescavadeira com operador, um sábado; 20 sacos de cimento"></label>
            <?php if ($myMoney): ?><p class="hint">Você já tem um compromisso de <?= e(money($myMoney['amount'])) ?>. Um novo valor em dinheiro substitui o anterior.</p><?php endif ?>
            <button class="btn btn-primary btn-sm">Registrar</button>
          </form>
        </details>
        <?php elseif (!current_user()): ?>
          <p><a href="<?= e(url('/entrar')) ?>">Entre</a> para oferecer uma contribuição.</p>
        <?php endif ?>
      <?php endif ?>
    </section>

    <section class="section" id="documentos">
      <h2><?= icon('file') ?>Orçamentos e documentos</h2>
      <?php if (!$documents): ?><p class="muted">Nenhum documento anexado ainda.</p><?php endif ?>
      <?php foreach (['Orçamentos' => $budgets, 'Outros documentos' => $otherDocs] as $label => $list): if (!$list) continue; ?>
      <h3><?= e($label) ?></h3>
      <ul class="file-list">
        <?php foreach ($list as $d): ?>
        <li>
          <a href="<?= e(upload_url($d['file'])) ?>" target="_blank" rel="noopener"><?= icon('file') ?><?= e($d['title']) ?></a>
          <span class="muted small"><?= e(project_document_kinds()[$d['kind']] ?? '') ?><?= $d['amount'] !== null ? ' · ' . e(money($d['amount'])) : '' ?> · <?= e(fmt_date($d['created_at'])) ?></span>
          <?php if ($manage): ?>
          <form method="post" action="<?= e(url('/projetos/documentos/' . $d['id'] . '/excluir')) ?>" data-confirm="Remover este documento? A remoção fica registrada no diário.">
            <?= csrf_field() ?><button class="linklike danger">remover</button>
          </form>
          <?php endif ?>
        </li>
        <?php endforeach ?>
      </ul>
      <?php endforeach ?>
      <?php if ($manage): ?>
      <details class="card">
        <summary><strong>Anexar documento</strong></summary>
        <form method="post" action="<?= e(url('/projetos/' . $pj['id'] . '/documentos')) ?>" enctype="multipart/form-data" class="form form-tight">
          <?= csrf_field() ?>
          <div class="row-2">
            <label>Tipo<select name="kind"><?php foreach (project_document_kinds() as $k => $label): ?><option value="<?= e($k) ?>"><?= e($label) ?></option><?php endforeach ?></select></label>
            <label>Valor (para orçamentos)<input name="amount" inputmode="decimal" placeholder="Ex.: 11.800,00"></label>
          </div>
          <label>Título<input name="title" required maxlength="160" placeholder="Ex.: Orçamento Construtora X"></label>
          <label>Arquivo (PDF ou imagem)<input type="file" name="file" required accept="application/pdf,image/*"></label>
          <button class="btn btn-sm btn-primary">Anexar</button>
        </form>
      </details>
      <?php endif ?>
    </section>

    <section class="section" id="contas">
      <h2><?= icon('chart') ?>Prestação de contas</h2>
      <div class="stats stats-3">
        <div><strong><?= e(money($raised)) ?></strong><span>arrecadado (informado)</span></div>
        <div><strong><?= e(money($totals['spent'])) ?></strong><span>gasto com comprovação</span></div>
        <div><strong><?= e(money($raised - $totals['spent'])) ?></strong><span>saldo</span></div>
      </div>
      <?php if ($expenses): ?>
      <div class="table-wrap">
        <table class="table">
          <thead><tr><th>Data</th><th>Descrição</th><th>Valor</th><th>Comprovante</th><?php if ($manage): ?><th></th><?php endif ?></tr></thead>
          <tbody>
          <?php foreach ($expenses as $x): ?>
          <tr>
            <td><?= e(fmt_day($x['spent_on'])) ?></td>
            <td><?= e($x['description']) ?><?php if ($x['supplier']): ?><br><span class="muted small"><?= e($x['supplier']) ?></span><?php endif ?></td>
            <td><?= e(money($x['amount'])) ?></td>
            <td><?php if ($x['file']): ?><a href="<?= e(upload_url($x['file'])) ?>" target="_blank" rel="noopener">ver nota</a><?php else: ?><span class="pill pill-warn">sem comprovante</span><?php endif ?></td>
            <?php if ($manage): ?><td><form method="post" action="<?= e(url('/projetos/despesas/' . $x['id'] . '/excluir')) ?>" data-confirm="Remover esta despesa? A remoção fica registrada no diário."><?= csrf_field() ?><button class="linklike danger">remover</button></form></td><?php endif ?>
          </tr>
          <?php endforeach ?>
          </tbody>
        </table>
      </div>
      <?php else: ?><p class="muted">Nenhuma despesa registrada.</p><?php endif ?>
      <?php if ($manage): ?>
      <details class="card">
        <summary><strong>Registrar despesa</strong></summary>
        <form method="post" action="<?= e(url('/projetos/' . $pj['id'] . '/despesas')) ?>" enctype="multipart/form-data" class="form form-tight">
          <?= csrf_field() ?>
          <label>Descrição<input name="description" required maxlength="255" placeholder="Ex.: 30 m³ de concreto usinado"></label>
          <div class="row-3">
            <label>Valor (R$)<input name="amount" required inputmode="decimal"></label>
            <label>Data<input type="date" name="spent_on" required value="<?= e(local_dt(now())->format('Y-m-d')) ?>"></label>
            <label>Fornecedor<input name="supplier" maxlength="160"></label>
          </div>
          <label>Nota fiscal ou recibo (PDF ou foto)<input type="file" name="file" accept="application/pdf,image/*"></label>
          <button class="btn btn-sm btn-primary">Registrar</button>
        </form>
      </details>
      <?php endif ?>
    </section>

    <section class="section" id="diario">
      <h2><?= icon('clock') ?>Diário do projeto</h2>
      <?php if ($manage): ?>
      <details class="card">
        <summary><strong>Publicar atualização</strong></summary>
        <form method="post" action="<?= e(url('/projetos/' . $pj['id'] . '/atualizacoes')) ?>" enctype="multipart/form-data" class="form form-tight">
          <?= csrf_field() ?>
          <textarea name="body" required rows="3" maxlength="5000" placeholder="Ex.: Primeira etapa concluída: canteiros preparados."></textarea>
          <label>Foto (opcional)<input type="file" name="photo" accept="image/*" data-compress></label>
          <button class="btn btn-sm btn-primary">Publicar</button>
        </form>
      </details>
      <?php endif ?>
      <ol class="timeline">
        <?php foreach ($updates as $up): ?>
        <li>
          <span class="timeline-date"><?= e(fmt_date($up['created_at'], true)) ?><?= $up['kind'] === 'post' && $up['author_name'] ? ' · ' . e($up['author_name']) : '' ?></span>
          <div class="<?= $up['kind'] === 'event' ? 'muted small' : '' ?>"><?= $up['kind'] === 'event' ? e($up['body']) : nl2p($up['body']) ?></div>
          <?php if ($up['photo']): ?><a href="<?= e(upload_url($up['photo'])) ?>" target="_blank"><img class="timeline-photo" src="<?= e(upload_url($up['photo_thumb'])) ?>" alt="" loading="lazy"></a><?php endif ?>
        </li>
        <?php endforeach ?>
      </ol>
    </section>

    <?php if ($pj['moderation'] === 'approved'): ?>
      <?= partial('partials/comments', ['comments' => $comments, 'type' => 'project', 'id' => (int)$pj['id']]) ?>
    <?php endif ?>
  </div>

  <aside class="detail-side">
    <div class="only-desktop"><?= $actionCard ?></div>

    <div class="card">
      <h3>Responsáveis</h3>
      <p><strong><?= e($pj['organization']) ?></strong>
        <?php if ($pj['organization_verified_at']): ?><span class="badge badge-pro"><?= icon('shield') ?>Verificada</span><?php else: ?><span class="pill pill-warn">Não verificada</span><?php endif ?></p>
      <?php if ($pj['organization_doc']): ?><p class="muted small">CNPJ <?= e(preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $pj['organization_doc'])) ?></p><?php endif ?>
      <div class="author-line"><?= avatar($a) ?><span>Organizado por <?php if (!$a['deleted_at']): ?><a href="<?= e(url('/perfil/' . $pj['user_id'])) ?>"><?= e(display_name($a)) ?></a><?php else: ?>Usuário removido<?php endif ?></span></div>
      <p class="small"><span title="<?= e($dutyHint) ?>"><?= icon('info') ?><?= e($dutyLabel) ?></span></p>
    </div>

    <button type="button" class="btn btn-block" data-share data-title="<?= e($pj['title']) ?>">Compartilhar</button>

    <?php if ($manage): ?>
      <a class="btn btn-block" href="<?= e(url('/projetos/' . $pj['id'] . '/editar')) ?>"><?= icon('pencil') ?>Editar projeto, autorização e valores</a>
    <?php endif ?>

    <?php if (is_mod()): ?>
    <div class="card">
      <h3>Moderação</h3>
      <form method="post" action="<?= e(url('/projetos/' . $pj['id'] . '/verificar-organizacao')) ?>" class="form form-tight">
        <?= csrf_field() ?>
        <p class="small">Confira o CNPJ na Receita Federal e se a organização existe de fato antes de verificar.</p>
        <?php if ($pj['organization_verified_at']): ?>
          <button class="btn btn-sm" name="verify" value="0">Remover verificação da organização</button>
        <?php else: ?>
          <button class="btn btn-sm btn-primary" name="verify" value="1"><?= icon('shield') ?>Verificar organização</button>
        <?php endif ?>
      </form>
      <form method="post" action="<?= e(url('/moderacao/acao')) ?>" class="form form-tight">
        <?= csrf_field() ?><input type="hidden" name="target_type" value="project"><input type="hidden" name="target_id" value="<?= (int)$pj['id'] ?>">
        <input name="note" maxlength="500" placeholder="Motivo (enviado ao responsável / registro público)">
        <?php if ($pj['moderation'] !== 'approved'): ?>
          <button class="btn btn-sm btn-primary" name="action" value="approve">Aprovar projeto</button>
          <button class="btn btn-sm btn-danger" name="action" value="reject">Recusar</button>
        <?php else: ?>
          <button class="btn btn-sm btn-danger" name="action" value="hide" data-confirm="Ocultar este projeto?">Ocultar projeto</button>
        <?php endif ?>
      </form>
    </div>
    <?php endif ?>
    <?php if (!$manage): ?><?= partial('partials/report', ['type' => 'project', 'id' => (int)$pj['id']]) ?><?php endif ?>
  </aside>
</div>
