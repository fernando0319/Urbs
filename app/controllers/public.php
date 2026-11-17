<?php
declare(strict_types=1);

function home_page(): void
{
    $discussion = all(ISSUE_SELECT . " WHERE i.moderation = 'approved' AND i.status IN ('open','discussion')
        ORDER BY i.proposal_count DESC, i.support_count DESC, i.created_at DESC LIMIT 6");
    $proposals = all(PROPOSAL_SELECT . " WHERE p.moderation = 'approved' AND i.moderation = 'approved'
        ORDER BY p.created_at DESC LIMIT 6");
    $stats = [
        'issues' => (int)val("SELECT COUNT(*) FROM issues WHERE moderation = 'approved'"),
        'proposals' => (int)val("SELECT COUNT(*) FROM proposals WHERE moderation = 'approved'"),
        'people' => (int)val('SELECT COUNT(*) FROM users WHERE deleted_at IS NULL AND blocked_at IS NULL'),
        'done' => (int)val("SELECT COUNT(*) FROM issues WHERE moderation = 'approved' AND status = 'done'"),
    ];
    render('home', ['title' => setting('site_name'), 'discussion' => $discussion, 'proposals' => $proposals, 'stats' => $stats, 'nav' => 'home']);
}

function map_page(): void
{
    render('map', ['title' => 'Mapa', 'categories' => categories(), 'nav' => 'map', 'bodyClass' => 'page-map']);
}

function panel_page(): void
{
    $stats = [
        'issues' => (int)val("SELECT COUNT(*) FROM issues WHERE moderation = 'approved'"),
        'proposals' => (int)val("SELECT COUNT(*) FROM proposals WHERE moderation = 'approved'"),
        'supports' => (int)val('SELECT COUNT(*) FROM supports'),
        'done' => (int)val("SELECT COUNT(*) FROM issues WHERE moderation = 'approved' AND status = 'done'"),
    ];
    $byStatus = [];
    foreach (all("SELECT status, COUNT(*) AS n FROM issues WHERE moderation = 'approved' GROUP BY status") as $r) {
        $byStatus[$r['status']] = (int)$r['n'];
    }
    $byCategory = all("SELECT c.name, c.color, COUNT(i.id) AS n FROM categories c
        LEFT JOIN issues i ON i.category_id = c.id AND i.moderation = 'approved'
        GROUP BY c.id, c.name, c.color ORDER BY n DESC");
    $timeline = all("SELECT * FROM (
          SELECT 'issue' AS type, i.id AS id, i.id AS issue_id, i.title AS title, i.created_at AS at, NULL AS status, NULL AS note
            FROM issues i WHERE i.moderation = 'approved'
          UNION ALL
          SELECT 'proposal', p.id, p.issue_id, p.title, p.created_at, NULL, NULL
            FROM proposals p JOIN issues i2 ON i2.id = p.issue_id
            WHERE p.moderation = 'approved' AND i2.moderation = 'approved'
          UNION ALL
          SELECT 'status', s.id, s.issue_id, i3.title, s.created_at, s.status, s.note
            FROM status_updates s JOIN issues i3 ON i3.id = s.issue_id WHERE i3.moderation = 'approved'
        ) t ORDER BY at DESC LIMIT 40");
    render('panel', compact('stats', 'byStatus', 'byCategory', 'timeline') + ['title' => 'Painel público', 'nav' => 'panel']);
}

function transparency_page(): void
{
    $log = all('SELECT l.*, u.name AS mod_name FROM mod_log l JOIN users u ON u.id = l.moderator_id
        ORDER BY l.created_at DESC LIMIT 100');
    render('pages/transparency', ['title' => 'Transparência da moderação', 'log' => $log]);
}

function static_page(string $page): void
{
    $titles = ['sobre' => 'Sobre', 'regras' => 'Regras de participação', 'privacidade' => 'Política de privacidade', 'termos' => 'Termos de uso'];
    render('pages/' . $page, ['title' => $titles[$page]]);
}

function offline_page(): void
{
    render('pages/offline', ['title' => 'Sem conexão']);
}

function profile_show(string $id): void
{
    $u = one('SELECT * FROM users WHERE id = ?', [(int)$id]);
    if (!$u || $u['deleted_at']) {
        abort(404);
    }
    $issues = all(ISSUE_SELECT . " WHERE i.user_id = ? AND i.moderation = 'approved' ORDER BY i.created_at DESC LIMIT 30", [$u['id']]);
    $proposals = all(PROPOSAL_SELECT . " WHERE p.user_id = ? AND p.moderation = 'approved' ORDER BY p.created_at DESC LIMIT 30", [$u['id']]);
    render('profile', ['title' => $u['name'], 'profile' => $u, 'issues' => $issues, 'proposals' => $proposals]);
}

// ---------------------------------------------------------------- API JSON

/** Marcadores do mapa. Parâmetros: bbox=oeste,sul,leste,norte; category; kind; status. */
function api_issues(): void
{
    $where = ["i.moderation = 'approved'"];
    $params = [];
    $bbox = explode(',', input('bbox'));
    if (count($bbox) === 4 && count(array_filter($bbox, 'is_numeric')) === 4) {
        [$w, $s, $e, $n] = array_map('floatval', $bbox);
        $where[] = 'i.lat BETWEEN ? AND ? AND i.lng BETWEEN ? AND ?';
        array_push($params, $s, $n, $w, $e);
    }
    if ($cat = input_int('category')) {
        $where[] = 'i.category_id = ?';
        $params[] = $cat;
    }
    if (array_key_exists($k = input('kind'), issue_kinds())) {
        $where[] = 'i.kind = ?';
        $params[] = $k;
    }
    $status = input('status');
    if (array_key_exists($status, issue_statuses())) {
        $where[] = 'i.status = ?';
        $params[] = $status;
    } elseif ($status !== 'all') {
        $where[] = "i.status <> 'archived'";
    }
    $rows = all(ISSUE_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY i.created_at DESC LIMIT 1000', $params);
    json_out(array_map(fn($r) => [
        'id' => (int)$r['id'],
        'lat' => (float)$r['lat'],
        'lng' => (float)$r['lng'],
        'title' => $r['title'],
        'kind' => $r['kind'],
        'status' => $r['status'],
        'statusLabel' => issue_statuses()[$r['status']] ?? $r['status'],
        'category' => $r['category_name'],
        'color' => $r['category_color'] ?: '#2563eb',
        'supports' => (int)$r['support_count'],
        'proposals' => (int)$r['proposal_count'],
        'thumb' => $r['cover_thumb'] ? upload_url($r['cover_thumb']) : null,
        'url' => url('/ocorrencias/' . $r['id']),
    ], $rows));
}

/** Ocorrências próximas a um ponto (evita duplicadas no cadastro). */
function api_near(): void
{
    $c = validate_coords(input('lat'), input('lng'));
    if (!$c) {
        json_out([]);
    }
    $radius = max(20, min(1000, input_int('r', 150)));
    $rows = issues_near($c[0], $c[1], $radius, 5);
    json_out(array_map(fn($r) => [
        'id' => (int)$r['id'],
        'title' => $r['title'],
        'distance' => $r['distance'],
        'status' => issue_statuses()[$r['status']] ?? $r['status'],
        'supports' => (int)$r['support_count'],
        'url' => url('/ocorrencias/' . $r['id']),
    ], $rows));
}

/** Manifest do PWA, gerado com o nome configurado da plataforma e da cidade. */
function manifest_page(): void
{
    $bp = base_path();
    header('Content-Type: application/manifest+json; charset=utf-8');
    header('Cache-Control: public, max-age=3600');
    echo json_encode([
        'name' => setting('site_name') . ' — ' . setting('city_name'),
        'short_name' => setting('site_name'),
        'description' => 'Participação cidadã: registre problemas, proponha soluções e compare projetos para a cidade.',
        'lang' => 'pt-BR',
        'start_url' => url('/') . (config('pretty_urls', true) ? '?source=pwa' : '&source=pwa'),
        'scope' => $bp . '/',
        'display' => 'standalone',
        'orientation' => 'portrait',
        'background_color' => '#f6f6f4',
        'theme_color' => '#111214',
        'icons' => [
            ['src' => $bp . '/assets/icons/icon-192.png', 'sizes' => '192x192', 'type' => 'image/png'],
            ['src' => $bp . '/assets/icons/icon-512.png', 'sizes' => '512x512', 'type' => 'image/png'],
            ['src' => $bp . '/assets/icons/icon-maskable-512.png', 'sizes' => '512x512', 'type' => 'image/png', 'purpose' => 'maskable'],
        ],
        'shortcuts' => [
            ['name' => 'Registrar ocorrência', 'url' => url('/ocorrencias/nova')],
            ['name' => 'Mapa', 'url' => url('/mapa')],
        ],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit;
}
