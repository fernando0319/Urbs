<?php
declare(strict_types=1);

function config(string $key, mixed $default = null): mixed
{
    $cfg = $GLOBALS['URBS_CONFIG'] ?? [];
    foreach (explode('.', $key) as $part) {
        if (!is_array($cfg) || !array_key_exists($part, $cfg)) {
            return $default;
        }
        $cfg = $cfg[$part];
    }
    return $cfg;
}

/** Configurações editáveis pelo painel (tabela settings), com padrões. */
function setting(string $key, mixed $default = null): mixed
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (all('SELECT k, v FROM settings') as $row) {
                $cache[$row['k']] = $row['v'];
            }
        } catch (Throwable) {
        }
    }
    if (array_key_exists($key, $cache)) {
        return $cache[$key];
    }
    return default_settings()[$key] ?? $default;
}

function default_settings(): array
{
    return [
        'site_name' => 'Urbs',
        'city_name' => 'Minha cidade',
        'map_lat' => '-25.5490',
        'map_lng' => '-49.8975',
        'map_zoom' => '15',
        'tile_url' => 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
        'tile_attribution' => '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
        'trust_threshold' => '1',
        'registration_open' => '1',
        'require_email_verification' => '1',
        'contact_email' => '',
        'about_text' => '',
        'participation_text' => '',
    ];
}

function save_setting(string $key, string $value): void
{
    if (one('SELECT k FROM settings WHERE k = ?', [$key])) {
        q('UPDATE settings SET v = ? WHERE k = ?', [$value, $key]);
    } else {
        insert('settings', ['k' => $key, 'v' => $value]);
    }
}

function e(mixed $s): string
{
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function base_path(): string
{
    return rtrim((string)parse_url((string)config('base_url', ''), PHP_URL_PATH), '/');
}

/** Gera URL interna. Com pretty_urls desligado usa index.php?r=. */
function url(string $path = '/', array $query = []): string
{
    $path = '/' . ltrim($path, '/');
    if (config('pretty_urls', true)) {
        $u = base_path() . $path;
        return $query ? $u . '?' . http_build_query($query) : $u;
    }
    return base_path() . '/index.php?' . http_build_query(['r' => $path] + $query);
}

function absolute_url(string $path = '/', array $query = []): string
{
    $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', (string)config('base_url', ''));
    return $origin . url($path, $query);
}

/** Esquema + domínio do site (ex.: https://participa.cidade.org), sem caminho. */
function site_origin(): string
{
    return (string)preg_replace('#^(https?://[^/]+).*$#', '$1', (string)config('base_url', ''));
}

/**
 * URL absoluta da imagem de prévia para redes sociais. Usa a versão média (_o, 1200px)
 * quando existe; sem imagem, usa o cartão padrão do site.
 */
function share_image_url(?string $uploadRel): string
{
    if ($uploadRel && !str_contains($uploadRel, '..')) {
        $medium = (string)preg_replace('/\.jpg$/', '_o.jpg', $uploadRel);
        $use = is_file(ROOT . '/uploads/' . $medium) ? $medium : $uploadRel;
        return site_origin() . upload_url($use);
    }
    return site_origin() . base_path() . '/assets/icons/share.png';
}

function asset(string $path): string
{
    $file = ROOT . '/' . ltrim($path, '/');
    $v = is_file($file) ? substr(md5((string)filemtime($file)), 0, 8) : '0';
    return base_path() . '/' . ltrim($path, '/') . '?v=' . $v;
}

function upload_url(?string $file): string
{
    return $file ? base_path() . '/uploads/' . $file : '';
}

function redirect(string $to): never
{
    header('Location: ' . (str_starts_with($to, 'http') ? $to : url($to)));
    exit;
}

function back(string $fallback = '/'): never
{
    $ref = $_SERVER['HTTP_REFERER'] ?? '';
    $origin = preg_replace('#^(https?://[^/]+).*$#', '$1', (string)config('base_url', ''));
    if ($ref && $origin && str_starts_with($ref, $origin)) {
        header('Location: ' . $ref);
        exit;
    }
    redirect($fallback);
}

function flash(string $type, string $msg): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function take_flashes(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/** Guarda valores do formulário para reexibir após erro de validação. */
function remember_old(): void
{
    $_SESSION['old'] = array_diff_key($_POST, array_flip(['password', 'password2', '_csrf']));
}

function old(string $key, mixed $default = ''): mixed
{
    return $GLOBALS['URBS_OLD'][$key] ?? $default;
}

function input(string $key, string $default = ''): string
{
    $v = $_POST[$key] ?? $_GET[$key] ?? $default;
    if (!is_string($v)) {
        return $default;
    }
    // Garante UTF-8 válido e remove caracteres de controle (exceto quebra de linha e tab).
    $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
    $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $v) ?? '';
    return trim($v);
}

function input_int(string $key, int $default = 0): int
{
    $v = $_POST[$key] ?? $_GET[$key] ?? null;
    return is_numeric($v) ? (int)$v : $default;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function abort(int $code, string $message = ''): never
{
    http_response_code($code);
    $titles = [403 => 'Acesso negado', 404 => 'Página não encontrada', 413 => 'Arquivos grandes demais', 419 => 'Sessão expirada', 429 => 'Muitas tentativas'];
    render('pages/error', [
        'title' => $titles[$code] ?? 'Erro',
        'code' => $code,
        'message' => $message ?: ($titles[$code] ?? 'Algo deu errado.'),
    ]);
    exit;
}

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function client_ip(): string
{
    return (string)($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
}

// ---------------------------------------------------------------- CSRF

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void
{
    // POST maior que post_max_size chega sem corpo: avisa em vez de acusar sessão expirada.
    if (!$_POST && !$_FILES && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        abort(413, 'Os arquivos enviados passam do limite do servidor (' . ini_get('post_max_size') . '). Envie menos arquivos ou arquivos menores.');
    }
    $sent = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!is_string($sent) || !hash_equals(csrf_token(), $sent)) {
        abort(419, 'Sua sessão expirou. Volte, recarregue a página e tente de novo.');
    }
}

// ---------------------------------------------------------------- Limite de tentativas

/** Retorna true se a ação ainda está dentro do limite e registra a tentativa. */
function rate_limit(string $action, string $key, int $max, int $windowSeconds): bool
{
    $hash = hash('sha256', $action . '|' . $key . '|' . config('app_key', ''));
    $count = (int)val(
        'SELECT COUNT(*) FROM attempts WHERE action = ? AND akey = ? AND created_at > ?',
        [$action, $hash, now(-$windowSeconds)]
    );
    if ($count >= $max) {
        return false;
    }
    insert('attempts', ['action' => $action, 'akey' => $hash, 'created_at' => now()]);
    if (random_int(1, 50) === 1) {
        q('DELETE FROM attempts WHERE created_at < ?', [now(-86400 * 2)]);
    }
    return true;
}

// ---------------------------------------------------------------- Formatação

function local_dt(?string $utc): ?DateTimeImmutable
{
    if (!$utc) {
        return null;
    }
    return (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
        ->setTimezone(new DateTimeZone(config('timezone', 'America/Sao_Paulo')));
}

function fmt_date(?string $utc, bool $withTime = false): string
{
    $d = local_dt($utc);
    return $d ? $d->format($withTime ? 'd/m/Y H:i' : 'd/m/Y') : '';
}

function time_ago(?string $utc): string
{
    if (!$utc) {
        return '';
    }
    $diff = time() - strtotime($utc . ' UTC');
    if ($diff < 60) return 'agora';
    if ($diff < 3600) return 'há ' . intdiv($diff, 60) . ' min';
    if ($diff < 86400) return 'há ' . intdiv($diff, 3600) . ' h';
    if ($diff < 86400 * 30) {
        $d = intdiv($diff, 86400);
        return 'há ' . $d . ($d === 1 ? ' dia' : ' dias');
    }
    return fmt_date($utc);
}

function money(mixed $v): string
{
    if ($v === null || $v === '') {
        return '';
    }
    return 'R$ ' . number_format((float)$v, 2, ',', '.');
}

function plural(int $n, string $one, string $many): string
{
    return $n . ' ' . ($n === 1 ? $one : $many);
}

function excerpt(string $text, int $len = 140): string
{
    $text = trim(preg_replace('/\s+/', ' ', $text));
    return mb_strlen($text) > $len ? mb_substr($text, 0, $len - 1) . '…' : $text;
}

/** Texto do usuário com quebras de linha e links clicáveis, já escapado. */
function nl2p(string $text): string
{
    $html = e($text);
    $html = preg_replace('#(https?://[^\s<]+)#', '<a href="$1" rel="nofollow ugc noopener" target="_blank">$1</a>', $html);
    $paras = preg_split('/\n{2,}/', str_replace("\r", '', $html));
    return implode('', array_map(fn($p) => '<p>' . nl2br(trim($p)) . '</p>', $paras));
}

// ---------------------------------------------------------------- Rótulos

function issue_statuses(): array
{
    return [
        'open' => 'Aberta',
        'discussion' => 'Em discussão',
        'in_progress' => 'Em andamento',
        'done' => 'Resolvida',
        'archived' => 'Arquivada',
    ];
}

function issue_kinds(): array
{
    return ['problem' => 'Problema', 'idea' => 'Sugestão'];
}

function profile_types(): array
{
    return [
        'citizen' => 'Morador(a)',
        'professional' => 'Arquiteto(a), engenheiro(a) ou urbanista',
        'student' => 'Estudante',
        'illustrator' => 'Ilustrador(a) ou designer',
        'association' => 'Associação ou coletivo',
    ];
}

function licenses(): array
{
    return [
        'cc-by' => ['CC BY 4.0', 'Livre para usar e adaptar, citando o autor.', 'https://creativecommons.org/licenses/by/4.0/deed.pt-br'],
        'cc-by-sa' => ['CC BY-SA 4.0', 'Livre para usar e adaptar, citando o autor e mantendo a mesma licença.', 'https://creativecommons.org/licenses/by-sa/4.0/deed.pt-br'],
        'cc-by-nc' => ['CC BY-NC 4.0', 'Uso e adaptação sem fins comerciais, citando o autor.', 'https://creativecommons.org/licenses/by-nc/4.0/deed.pt-br'],
        'cc-by-nc-nd' => ['CC BY-NC-ND 4.0', 'Apenas compartilhar, sem alterações e sem fins comerciais.', 'https://creativecommons.org/licenses/by-nc-nd/4.0/deed.pt-br'],
        'cc0' => ['CC0 (domínio público)', 'Sem restrições.', 'https://creativecommons.org/publicdomain/zero/1.0/deed.pt-br'],
        'arr' => ['Todos os direitos reservados', 'Qualquer uso depende de autorização do autor.', ''],
    ];
}

function report_reasons(): array
{
    return [
        'spam' => 'Spam ou propaganda',
        'offensive' => 'Ofensivo ou discurso de ódio',
        'personal_data' => 'Expõe pessoas, placas ou dados pessoais',
        'political' => 'Propaganda político-partidária',
        'false' => 'Informação falsa',
        'copyright' => 'Plágio ou violação de autoria',
        'other' => 'Outro motivo',
    ];
}

function status_class(string $status): string
{
    return 'status-' . preg_replace('/[^a-z_]/', '', $status);
}

// ---------------------------------------------------------------- Projetos comunitários

function project_statuses(): array
{
    return [
        'preparing' => 'Em preparação',
        'fundraising' => 'Arrecadando',
        'executing' => 'Em execução',
        'done' => 'Concluído',
        'cancelled' => 'Cancelado',
    ];
}

/** Natureza da melhoria: evita que a comunidade pague, sem saber, por uma obrigação do município. */
function duty_types(): array
{
    return [
        'municipal_duty' => ['Obrigação do município', 'Serviço que a prefeitura já deveria prestar com o orçamento público (ex.: tapar buracos, iluminação pública). Contribuir não tira essa responsabilidade dela.'],
        'complementary' => ['Melhoria complementar', 'Vai além do serviço básico (ex.: paisagismo, mobiliário diferenciado, arte urbana).'],
        'independent' => ['Iniciativa comunitária', 'Projeto da própria comunidade, em espaço autorizado ou privado (ex.: horta comunitária, mutirão em área cedida).'],
    ];
}

function auth_statuses(): array
{
    return [
        'not_requested' => 'Ainda não solicitada',
        'requested' => 'Solicitada, aguardando resposta',
        'approved' => 'Autorizada',
        'denied' => 'Negada',
        'not_required' => 'Não é necessária',
    ];
}

function pledge_kinds(): array
{
    return [
        'money' => 'Dinheiro (compromisso)',
        'machine' => 'Máquina ou equipamento',
        'material' => 'Material',
        'labor' => 'Trabalho voluntário',
        'service' => 'Serviço profissional',
        'other' => 'Outro',
    ];
}

function pledge_statuses(): array
{
    return ['offered' => 'Oferecido', 'accepted' => 'Aceito', 'delivered' => 'Entregue', 'declined' => 'Não necessário', 'withdrawn' => 'Retirado'];
}

function project_document_kinds(): array
{
    return ['budget' => 'Orçamento', 'authorization' => 'Autorização ou protocolo', 'technical' => 'Documento técnico (ART/RRT, projeto)', 'other' => 'Outro'];
}

function suggestion_statuses(): array
{
    return ['open' => 'Aberta', 'considering' => 'Em análise pelo autor', 'incorporated' => 'Incorporada', 'declined' => 'Não incorporada'];
}

/** Valida e normaliza uma URL http(s) informada pelo usuário. */
function clean_url(string $u): ?string
{
    $u = trim($u);
    if ($u === '') {
        return null;
    }
    if (!preg_match('#^https?://#i', $u)) {
        $u = 'https://' . $u;
    }
    return filter_var($u, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $u) ? mb_substr($u, 0, 500) : null;
}

/** Data AAAA-MM-DD válida ou null. */
function clean_date(string $d): ?string
{
    $dt = DateTimeImmutable::createFromFormat('!Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d ? $d : null;
}

function fmt_day(?string $ymd): string
{
    return $ymd ? (DateTimeImmutable::createFromFormat('!Y-m-d', substr($ymd, 0, 10))?->format('d/m/Y') ?? '') : '';
}
