<section class="hero">
  <p class="eyebrow"><?= e(setting('city_name')) ?></p>
  <h1>O que você mudaria na sua cidade?</h1>
  <p class="lead">Aponte um problema ou uma ideia. Arquitetos, estudantes, ilustradores e qualquer pessoa criativa propõem soluções, e a comunidade compara e escolhe as favoritas.</p>
  <div class="hero-actions">
    <a class="btn btn-primary btn-lg" href="<?= e(url('/ocorrencias/nova')) ?>"><?= icon('plus') ?>Registrar ocorrência</a>
    <a class="btn btn-lg" href="<?= e(url('/mapa')) ?>"><?= icon('map') ?>Ver o mapa</a>
  </div>
</section>

<section class="steps">
  <div class="step"><span class="step-n">1</span><h3>Aponte</h3><p>Registre um problema ou sugestão com foto e localização.</p></div>
  <div class="step"><span class="step-n">2</span><h3>Proponha</h3><p>Qualquer pessoa pode enviar um projeto, croqui ou ilustração com o "antes e depois".</p></div>
  <div class="step"><span class="step-n">3</span><h3>Compare</h3><p>A comunidade compara as propostas para o mesmo lugar e indica sua preferida.</p></div>
</section>

<section class="stats">
  <div><strong><?= $stats['issues'] ?></strong><span>ocorrências</span></div>
  <div><strong><?= $stats['proposals'] ?></strong><span>propostas</span></div>
  <div><strong><?= $stats['people'] ?></strong><span>participantes</span></div>
  <div><strong><?= $stats['done'] ?></strong><span>resolvidas</span></div>
</section>

<section class="section">
  <div class="section-head">
    <h2>Em discussão</h2>
    <a href="<?= e(url('/ocorrencias')) ?>">Ver todas</a>
  </div>
  <?php if ($discussion): ?>
    <div class="grid grid-2"><?php foreach ($discussion as $i) echo partial('partials/issue_card', ['i' => $i]); ?></div>
  <?php else: ?>
    <div class="empty">
      <?= icon('pin', 'icon icon-xl') ?>
      <p>Nada por aqui ainda. Seja a primeira pessoa a registrar algo em <?= e(setting('city_name')) ?>.</p>
      <a class="btn btn-primary" href="<?= e(url('/ocorrencias/nova')) ?>">Registrar ocorrência</a>
    </div>
  <?php endif ?>
</section>

<?php if ($proposals): ?>
<section class="section">
  <div class="section-head">
    <h2>Propostas recentes</h2>
    <a href="<?= e(url('/propostas')) ?>">Ver acervo</a>
  </div>
  <div class="grid grid-3"><?php foreach ($proposals as $p) echo partial('partials/proposal_card', ['p' => $p]); ?></div>
</section>
<?php endif ?>

<section class="callout">
  <div>
    <h2>Você projeta, desenha ou ilustra?</h2>
    <p>Não é preciso ter registro profissional para propor. Arquitetos e engenheiros verificados (CAU/CREA) recebem um selo que dá mais credibilidade, mas um bom croqui de um estudante ou uma ilustração de um morador também mudam a conversa.</p>
  </div>
  <a class="btn btn-primary" href="<?= e(url('/ocorrencias', ['sort' => 'supports'])) ?>">Escolher um lugar para propor</a>
</section>

<section class="callout">
  <div>
    <h2>Participar vai além do aplicativo</h2>
    <p>Ouvidoria, pedido de acesso à informação, audiências do orçamento, conselhos, "Adote uma praça", projeto de lei de iniciativa popular: veja os caminhos legais para cobrar, propor e cuidar da cidade.</p>
  </div>
  <a class="btn" href="<?= e(url('/participar')) ?>">Como participar</a>
</section>
