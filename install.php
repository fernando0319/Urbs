<?php
/**
 * Urbs — instalador web.
 * Envie os arquivos para a hospedagem, acesse /install.php e siga os passos.
 * Depois de instalado, este arquivo se recusa a rodar de novo (pode ser apagado).
 */
declare(strict_types=1);

define('ROOT', __DIR__);

if (is_file(ROOT . '/config.php')) {
    http_response_code(403);
    exit('A plataforma já está instalada. Para reinstalar, remova o arquivo config.php (isso não apaga o banco de dados).');
}

require ROOT . '/app/lib/helpers.php';
require ROOT . '/app/lib/db.php';

function h(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$checks = [
    'PHP 8.1 ou superior' => PHP_VERSION_ID >= 80100,
    'Extensão PDO' => extension_loaded('pdo'),
    'Driver PDO MySQL ou SQLite' => extension_loaded('pdo_mysql') || extension_loaded('pdo_sqlite'),
    'Extensão GD (imagens)' => extension_loaded('gd'),
    'Extensão fileinfo' => extension_loaded('fileinfo'),
    'Extensão mbstring' => extension_loaded('mbstring'),
    'Pasta raiz gravável (para criar config.php)' => is_writable(ROOT),
    'Pasta uploads/ gravável' => is_writable(ROOT . '/uploads'),
    'Pasta storage/ gravável' => is_writable(ROOT . '/storage'),
];
$ok = !in_array(false, $checks, true);

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
$dir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$guessUrl = ($https ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $dir;

$errors = [];
$v = fn(string $k, string $d = '') => isset($_POST[$k]) ? trim((string)$_POST[$k]) : $d;

if ($ok && ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $driver = $v('driver') === 'sqlite' ? 'sqlite' : 'mysql';
    $db = $driver === 'sqlite'
        ? ['driver' => 'sqlite', 'path' => ROOT . '/storage/urbs.sqlite']
        : ['driver' => 'mysql', 'host' => $v('db_host', 'localhost'), 'port' => (int)$v('db_port', '3306'),
           'name' => $v('db_name'), 'user' => $v('db_user'), 'pass' => (string)($_POST['db_pass'] ?? '')];
    $baseUrl = rtrim($v('base_url', $guessUrl), '/');
    $adminName = $v('admin_name');
    $adminEmail = mb_strtolower($v('admin_email'));
    $adminPass = (string)($_POST['admin_pass'] ?? '');

    if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) $errors[] = 'Endereço do site inválido.';
    if (mb_strlen($adminName) < 2) $errors[] = 'Informe o nome do administrador.';
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) $errors[] = 'E-mail do administrador inválido.';
    if (strlen($adminPass) < 10) $errors[] = 'A senha do administrador deve ter pelo menos 10 caracteres.';
    if (!is_numeric($v('map_lat')) || !is_numeric($v('map_lng'))) $errors[] = 'Coordenadas do centro do mapa inválidas.';
    if ($driver === 'mysql' && $db['name'] === '') $errors[] = 'Informe o nome do banco de dados.';

    if (!$errors) {
        $config = [
            'base_url' => $baseUrl,
            'pretty_urls' => $v('pretty_urls') === '1',
            'debug' => false,
            'timezone' => 'America/Sao_Paulo',
            'app_key' => bin2hex(random_bytes(32)),
            'source_url' => $v('source_url'),
            'db' => $db,
            'mail' => [
                'driver' => in_array($v('mail_driver'), ['mail', 'smtp', 'log'], true) ? $v('mail_driver') : 'mail',
                'from' => $v('mail_from') ?: $adminEmail,
                'from_name' => $v('site_name', 'Urbs'),
                'host' => $v('smtp_host'),
                'port' => (int)$v('smtp_port', '587'),
                'secure' => $v('smtp_secure', 'tls'),
                'user' => $v('smtp_user'),
                'pass' => (string)($_POST['smtp_pass'] ?? ''),
            ],
            'uploads' => ['max_image_mb' => 10, 'max_pdf_mb' => 20],
        ];
        $GLOBALS['URBS_CONFIG'] = $config;
        try {
            $pdo = db();
            foreach (db_schema_statements($driver) as $stmt) {
                $pdo->exec($stmt);
            }
            if ((int)val('SELECT COUNT(*) FROM categories') === 0) {
                $cats = [
                    ['Calçadas e acessibilidade', '#7c3aed'], ['Ruas e trânsito', '#475569'], ['Iluminação', '#eab308'],
                    ['Praças e áreas verdes', '#16a34a'], ['Limpeza e lixo', '#92400e'], ['Mobilidade e ciclovias', '#0891b2'],
                    ['Patrimônio e cultura', '#db2777'], ['Segurança', '#dc2626'], ['Água e drenagem', '#2563eb'], ['Outros', '#6b7280'],
                ];
                foreach ($cats as $i => [$name, $color]) {
                    insert('categories', ['name' => $name, 'color' => $color, 'sort' => $i * 10, 'active' => 1]);
                }
            }
            foreach ([
                'site_name' => $v('site_name', 'Urbs'),
                'city_name' => $v('city_name'),
                'map_lat' => $v('map_lat'),
                'map_lng' => $v('map_lng'),
                'map_zoom' => $v('map_zoom', '15'),
                'contact_email' => $adminEmail,
                'schema_version' => '2',
            ] as $k => $val) {
                save_setting($k, $val);
            }
            if (one('SELECT id FROM users WHERE email = ?', [$adminEmail])) {
                q("UPDATE users SET role = 'admin', password_hash = ? WHERE email = ?", [password_hash($adminPass, PASSWORD_DEFAULT), $adminEmail]);
            } else {
                insert('users', [
                    'name' => $adminName, 'email' => $adminEmail, 'password_hash' => password_hash($adminPass, PASSWORD_DEFAULT),
                    'role' => 'admin', 'profile_type' => 'citizen', 'email_verified_at' => now(), 'consent_at' => now(), 'created_at' => now(),
                ]);
            }
            $php = "<?php\n// Gerado pelo instalador em " . date('c') . ". Não versione este arquivo.\nreturn " . var_export($config, true) . ";\n";
            if (file_put_contents(ROOT . '/config.php', $php, LOCK_EX) === false) {
                throw new RuntimeException('Não foi possível gravar config.php. Verifique as permissões da pasta.');
            }
            @chmod(ROOT . '/config.php', 0640);
            $done = $baseUrl;
        } catch (Throwable $e) {
            $errors[] = 'Erro ao preparar o banco: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalar Urbs</title>
<link rel="stylesheet" href="assets/css/app.css">
<style>body{padding-bottom:0}.wrap{max-width:720px;margin:0 auto;padding:2rem 1rem}.req li{list-style:none}.req{padding:0}.yes{color:var(--ok)}.no{color:var(--danger)}</style>
</head>
<body>
<div class="wrap">
  <h1>Instalar Urbs</h1>
  <?php if (!empty($done)): ?>
    <div class="flash flash-ok"><strong>Pronto!</strong> A plataforma foi instalada.</div>
    <ol>
      <li>Entre com o e-mail e a senha de administrador que você acabou de definir.</li>
      <li>Ajuste o mapa e os textos em <em>Moderação → Configurações</em>.</li>
      <li>Por segurança, apague o arquivo <code>install.php</code> do servidor.</li>
    </ol>
    <p><a class="btn btn-primary btn-lg" href="<?= h($done) ?>/">Abrir a plataforma</a></p>
  <?php else: ?>
    <h2>1. Requisitos</h2>
    <ul class="req">
      <?php foreach ($checks as $label => $pass): ?><li class="<?= $pass ? 'yes' : 'no' ?>"><?= $pass ? '✔' : '✘' ?> <?= h($label) ?></li><?php endforeach ?>
    </ul>
    <?php if (!$ok): ?><div class="flash flash-warn">Corrija os itens acima (geralmente no painel da hospedagem) e recarregue a página.</div><?php else: ?>

    <?php if ($errors): ?><div class="flash flash-warn"><ul><?php foreach ($errors as $er): ?><li><?= h($er) ?></li><?php endforeach ?></ul></div><?php endif ?>

    <form method="post" class="form">
      <fieldset>
        <legend>2. Sua instância</legend>
        <div class="row-2">
          <label>Nome da plataforma<input name="site_name" required value="<?= h($v('site_name', 'Urbs')) ?>"></label>
          <label>Cidade<input name="city_name" required value="<?= h($v('city_name', 'Porto Amazonas')) ?>"></label>
        </div>
        <label>Endereço do site<input name="base_url" required value="<?= h($v('base_url', $guessUrl)) ?>"><small class="hint">Com https://, sem barra no final. Se instalou numa subpasta, inclua a subpasta.</small></label>
        <div class="row-3">
          <label>Latitude do centro<input name="map_lat" required value="<?= h($v('map_lat', '-25.5490')) ?>"></label>
          <label>Longitude do centro<input name="map_lng" required value="<?= h($v('map_lng', '-49.8975')) ?>"></label>
          <label>Zoom<input name="map_zoom" type="number" min="3" max="19" value="<?= h($v('map_zoom', '15')) ?>"></label>
        </div>
        <small class="hint">Para achar as coordenadas, abra o OpenStreetMap, clique com o botão direito no centro da cidade e escolha "Mostrar endereço".</small>
        <label class="check"><input type="checkbox" name="pretty_urls" value="1" <?= $v('pretty_urls', '1') === '1' ? 'checked' : '' ?>><span>URLs amigáveis (exige Apache com mod_rewrite, presente na maioria das hospedagens). Se as páginas derem erro 404, reinstale com esta opção desmarcada.</span></label>
        <label>Link do código-fonte (opcional)<input name="source_url" value="<?= h($v('source_url')) ?>" placeholder="https://github.com/seu-usuario/urbs"></label>
      </fieldset>

      <fieldset>
        <legend>3. Banco de dados</legend>
        <label class="check"><input type="radio" name="driver" value="mysql" <?= $v('driver', 'mysql') === 'mysql' ? 'checked' : '' ?>><span><strong>MySQL / MariaDB</strong> (recomendado; crie o banco e o usuário no painel da hospedagem)</span></label>
        <label class="check"><input type="radio" name="driver" value="sqlite" <?= $v('driver') === 'sqlite' ? 'checked' : '' ?> <?= extension_loaded('pdo_sqlite') ? '' : 'disabled' ?>><span><strong>SQLite</strong> (arquivo único, sem servidor de banco; bom para testes e cidades pequenas)</span></label>
        <div class="row-2">
          <label>Servidor<input name="db_host" value="<?= h($v('db_host', 'localhost')) ?>"></label>
          <label>Porta<input name="db_port" value="<?= h($v('db_port', '3306')) ?>"></label>
          <label>Nome do banco<input name="db_name" value="<?= h($v('db_name')) ?>"></label>
          <label>Usuário<input name="db_user" value="<?= h($v('db_user')) ?>" autocomplete="off"></label>
        </div>
        <label>Senha do banco<input type="password" name="db_pass" autocomplete="off"></label>
      </fieldset>

      <fieldset>
        <legend>4. E-mail</legend>
        <label>Forma de envio
          <select name="mail_driver">
            <option value="mail" <?= $v('mail_driver', 'mail') === 'mail' ? 'selected' : '' ?>>Função mail() do PHP (padrão da maioria das hospedagens)</option>
            <option value="smtp" <?= $v('mail_driver') === 'smtp' ? 'selected' : '' ?>>SMTP (Gmail, Brevo, provedor da hospedagem…)</option>
            <option value="log" <?= $v('mail_driver') === 'log' ? 'selected' : '' ?>>Não enviar; gravar em storage/logs/mail.log (testes)</option>
          </select>
        </label>
        <label>Remetente<input type="email" name="mail_from" value="<?= h($v('mail_from')) ?>" placeholder="nao-responda@seudominio.com.br"></label>
        <div class="row-2">
          <label>Servidor SMTP<input name="smtp_host" value="<?= h($v('smtp_host')) ?>"></label>
          <label>Porta SMTP<input name="smtp_port" value="<?= h($v('smtp_port', '587')) ?>"></label>
          <label>Segurança
            <select name="smtp_secure"><option value="tls">STARTTLS (587)</option><option value="ssl" <?= $v('smtp_secure') === 'ssl' ? 'selected' : '' ?>>SSL (465)</option><option value="" <?= $v('smtp_secure', 'tls') === '' ? 'selected' : '' ?>>Nenhuma</option></select>
          </label>
          <label>Usuário SMTP<input name="smtp_user" value="<?= h($v('smtp_user')) ?>" autocomplete="off"></label>
        </div>
        <label>Senha SMTP<input type="password" name="smtp_pass" autocomplete="off"></label>
      </fieldset>

      <fieldset>
        <legend>5. Conta de administrador</legend>
        <label>Seu nome<input name="admin_name" required value="<?= h($v('admin_name')) ?>"></label>
        <label>E-mail<input type="email" name="admin_email" required value="<?= h($v('admin_email')) ?>"></label>
        <label>Senha (mínimo 10 caracteres)<input type="password" name="admin_pass" required minlength="10" autocomplete="new-password"></label>
      </fieldset>

      <button class="btn btn-primary btn-lg btn-block">Instalar</button>
    </form>
    <?php endif ?>
  <?php endif ?>
</div>
</body>
</html>
