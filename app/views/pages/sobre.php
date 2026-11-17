<div class="narrow prose-page">
<h1>Sobre</h1>
<?php if (setting('about_text')): ?>
  <div class="prose"><?= nl2p(setting('about_text')) ?></div>
<?php else: ?>
<p class="lead-sm"><strong><?= e(setting('site_name')) ?></strong> é uma plataforma comunitária e independente de participação cidadã em <?= e(setting('city_name')) ?>.</p>
<h2>Como funciona</h2>
<ol>
  <li><strong>Aponte.</strong> Moradores registram problemas e sugestões no mapa, com foto e localização.</li>
  <li><strong>Proponha.</strong> Arquitetos, engenheiros, urbanistas, estudantes, ilustradores e qualquer pessoa criativa enviam propostas para aquele lugar: projeto técnico, croqui, ilustração ou fotomontagem, com antes e depois.</li>
  <li><strong>Compare.</strong> A comunidade compara as propostas para o mesmo lugar e indica a sua preferida.</li>
</ol>
<p>Todas as propostas formam um acervo permanente de ideias para a cidade, com a autoria sempre preservada.</p>
<h2>Independência</h2>
<p>A plataforma não pertence a governo nem a partido político. Ela não substitui os canais oficiais da prefeitura nem da câmara. O objetivo é organizar e dar visibilidade às ideias da comunidade. Apoios e preferências mostram a opinião de quem participou e <strong>não têm caráter vinculante</strong>.</p>
<h2>Selos</h2>
<p><strong>Profissional verificado</strong>: a moderação conferiu o registro no CAU ou CREA. <strong>Morador verificado</strong>: a moderação conferiu um comprovante de endereço na cidade. Qualquer pessoa pode participar sem selo. Os selos só ajudam a dar contexto.</p>
<?php endif ?>
<h2>Código aberto</h2>
<p>O software é livre e pode ser instalado por qualquer comunidade.<?php if ($src = config('source_url')): ?> O código está em <a href="<?= e($src) ?>" rel="noopener"><?= e($src) ?></a>.<?php endif ?></p>
<?php if (setting('contact_email')): ?><h2>Contato</h2><p><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p><?php endif ?>
</div>
