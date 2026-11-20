<?php
$u = current_user();
$siteName = setting('site_name');
$nav = $nav ?? '';
$pageTitle = isset($title) && $title !== $siteName ? $title . ' · ' . $siteName : $siteName;
$counts = is_mod() ? mod_counts() : null;
$modTotal = $counts ? $counts['queue'] + $counts['reports'] + $counts['verifications'] : 0;
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($pageTitle) ?></title>
<meta name="description" content="<?= e($description ?? ('Participação cidadã em ' . setting('city_name') . ': registre problemas, proponha soluções e compare projetos para a cidade.')) ?>">
<meta name="theme-color" content="#ffffff" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#111214" media="(prefers-color-scheme: dark)">
<?php
$shareDesc = $description ?? ('Participação cidadã em ' . setting('city_name') . ': registre problemas, proponha soluções e compare projetos para a cidade.');
$shareImg = share_image_url($ogFile ?? null);
?>
<meta property="og:type" content="<?= !empty($ogFile) ? 'article' : 'website' ?>">
<meta property="og:locale" content="pt_BR">
<meta property="og:site_name" content="<?= e($siteName . ' · ' . setting('city_name')) ?>">
<meta property="og:title" content="<?= e($title ?? $siteName) ?>">
<meta property="og:description" content="<?= e($shareDesc) ?>">
<meta property="og:url" content="<?= e(site_origin() . strtok((string)($_SERVER['REQUEST_URI'] ?? '/'), '#')) ?>">
<meta property="og:image" content="<?= e($shareImg) ?>">
<meta property="og:image:alt" content="<?= e($title ?? $siteName) ?>">
<meta name="twitter:card" content="summary_large_image">
<link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
<link rel="icon" href="<?= e(base_path()) ?>/assets/icons/icon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="<?= e(base_path()) ?>/assets/icons/icon-192.png">
<link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
<?php if (!empty($leaflet) || in_array($nav, ['map', 'new'], true)): ?>
<link rel="stylesheet" href="<?= e(asset('assets/vendor/leaflet/leaflet.css')) ?>">
<script src="<?= e(asset('assets/vendor/leaflet/leaflet.js')) ?>" defer></script>
<?php endif ?>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</head>
<body class="<?= e($bodyClass ?? '') ?>" data-base="<?= e(base_path()) ?>" data-csrf="<?= e(csrf_token()) ?>" data-offline="<?= e(url('/offline')) ?>">
<a class="skip" href="#main">Pular para o conteúdo</a>
<header class="topbar">
  <div class="topbar-inner">
    <a class="brand" href="<?= e(url('/')) ?>">
      <img src="<?= e(base_path()) ?>/assets/icons/icon.svg" alt="" width="28" height="28">
      <span><?= e($siteName) ?><small><?= e(setting('city_name')) ?></small></span>
    </a>
    <nav class="mainnav" aria-label="Principal">
      <a href="<?= e(url('/mapa')) ?>" <?= $nav === 'map' ? 'aria-current="page"' : '' ?>>Mapa</a>
      <a href="<?= e(url('/ocorrencias')) ?>" <?= $nav === 'issues' ? 'aria-current="page"' : '' ?>>Ocorrências</a>
      <a href="<?= e(url('/propostas')) ?>" <?= $nav === 'proposals' ? 'aria-current="page"' : '' ?>>Propostas</a>
      <a href="<?= e(url('/projetos')) ?>" <?= $nav === 'projects' ? 'aria-current="page"' : '' ?>>Projetos</a>
      <a href="<?= e(url('/painel')) ?>" <?= $nav === 'panel' ? 'aria-current="page"' : '' ?>>Painel</a>
      <a href="<?= e(url('/participar')) ?>" <?= $nav === 'participate' ? 'aria-current="page"' : '' ?>>Participar</a>
    </nav>
    <div class="topbar-actions">
      <?php if ($u && is_mod($u)): ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/moderacao')) ?>"><?= icon('shield') ?><span class="hide-sm">Moderação</span><?php if ($modTotal): ?><span class="count"><?= $modTotal ?></span><?php endif ?></a>
      <?php endif ?>
      <a class="btn btn-primary btn-sm hide-sm" href="<?= e(url('/ocorrencias/nova')) ?>"><?= icon('plus') ?>Registrar</a>
      <?php if ($u): ?>
        <a class="account-link hide-sm" href="<?= e(url('/conta')) ?>" title="Minha conta"><?= avatar($u) ?></a>
      <?php else: ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(url('/entrar')) ?>">Entrar</a>
      <?php endif ?>
    </div>
  </div>
</header>

<?php if ($u && !email_verified($u) && $nav !== 'account'): ?>
<div class="notice-bar">Confirme seu e-mail para publicar, apoiar e votar. <a href="<?= e(url('/conta')) ?>">Ver detalhes</a></div>
<?php endif ?>

<main id="main" class="<?= ($bodyClass ?? '') === 'page-map' ? 'main-full' : 'main' ?>">
  <?php foreach (take_flashes() as $f): ?>
    <div class="flash flash-<?= e($f['type']) ?>" role="status"><?= e($f['msg']) ?></div>
  <?php endforeach ?>
  <?= $content ?>
</main>

<?php if (($bodyClass ?? '') !== 'page-map'): ?>
<footer class="footer">
  <div class="footer-inner">
    <p><strong><?= e($siteName) ?></strong> é uma plataforma comunitária e independente de participação cidadã. Os resultados de apoios e preferências não têm caráter vinculante.</p>
    <nav aria-label="Institucional">
      <a href="<?= e(url('/sobre')) ?>">Sobre</a>
      <a href="<?= e(url('/participar')) ?>">Como participar</a>
      <a href="<?= e(url('/projetos')) ?>">Projetos comunitários</a>
      <a href="<?= e(url('/painel')) ?>">Painel</a>
      <a href="<?= e(url('/regras')) ?>">Regras</a>
      <a href="<?= e(url('/transparencia')) ?>">Transparência</a>
      <a href="<?= e(url('/privacidade')) ?>">Privacidade</a>
      <a href="<?= e(url('/termos')) ?>">Termos</a>
      <?php if ($src = config('source_url')): ?><a href="<?= e($src) ?>" rel="noopener">Código aberto</a><?php endif ?>
    </nav>
  </div>
</footer>
<?php endif ?>

<nav class="tabbar" aria-label="Navegação rápida">
  <a href="<?= e(url('/')) ?>" <?= $nav === 'home' ? 'aria-current="page"' : '' ?>><?= icon('home') ?><span>Início</span></a>
  <a href="<?= e(url('/mapa')) ?>" <?= $nav === 'map' ? 'aria-current="page"' : '' ?>><?= icon('map') ?><span>Mapa</span></a>
  <a class="tab-new" href="<?= e(url('/ocorrencias/nova')) ?>" <?= $nav === 'new' ? 'aria-current="page"' : '' ?>><span class="tab-new-btn"><?= icon('plus') ?></span><span>Registrar</span></a>
  <a href="<?= e(url('/propostas')) ?>" <?= $nav === 'proposals' ? 'aria-current="page"' : '' ?>><?= icon('layers') ?><span>Propostas</span></a>
  <a href="<?= e(url($u ? '/conta' : '/entrar')) ?>" <?= $nav === 'account' ? 'aria-current="page"' : '' ?>><?= icon('user') ?><span>Conta</span></a>
</nav>
</body>
</html>
