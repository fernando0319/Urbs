<?php
declare(strict_types=1);

/** Renderiza uma view dentro do layout. */
function render(string $view, array $vars = [], string $layout = 'layout'): void
{
    $content = partial($view, $vars);
    if ($layout === '') {
        echo $content;
        return;
    }
    echo partial($layout, $vars + ['content' => $content]);
}

function partial(string $view, array $vars = []): string
{
    $file = ROOT . '/app/views/' . $view . '.php';
    if (!is_file($file)) {
        throw new RuntimeException("View não encontrada: $view");
    }
    extract($vars, EXTR_SKIP);
    ob_start();
    include $file;
    return (string)ob_get_clean();
}

/** Ícones SVG inline (traço), sem dependência externa. */
function icon(string $name, string $class = 'icon'): string
{
    static $paths = [
        'map' => '<path d="M9 4 3 6v14l6-2 6 2 6-2V4l-6 2-6-2Z"/><path d="M9 4v14M15 6v14"/>',
        'pin' => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21Z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'home' => '<path d="M4 11 12 4l8 7"/><path d="M6 10v10h12V10"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/>',
        'users' => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c1-3.5 3.5-5.5 6.5-5.5s5.5 2 6.5 5.5"/><path d="M16 4.6a3.5 3.5 0 0 1 0 6.8M18 14.8c1.7.7 2.9 2.4 3.5 5.2"/>',
        'thumb' => '<path d="M7 11v9H4v-9h3Z"/><path d="M7 11l4-7c1.7 0 2.7 1.3 2.3 3L12.7 10H19a2 2 0 0 1 2 2.3l-1.2 6A2 2 0 0 1 17.8 20H7"/>',
        'shield' => '<path d="M12 3 5 6v5c0 4.5 3 8.3 7 10 4-1.7 7-5.5 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/>',
        'flag' => '<path d="M5 21V4M5 4h11l-2 4 2 4H5"/>',
        'message' => '<path d="M4 5h16v11H9l-5 4V5Z"/>',
        'file' => '<path d="M14 3H6v18h12V7l-4-4Z"/><path d="M14 3v4h4"/>',
        'image' => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 17-5-5-9 8"/>',
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'check' => '<path d="m5 12 5 5 9-10"/>',
        'x' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'gps' => '<circle cx="12" cy="12" r="3"/><circle cx="12" cy="12" r="7"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'list' => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'compare' => '<rect x="3" y="4" width="7" height="16" rx="1.5"/><rect x="14" y="4" width="7" height="16" rx="1.5"/>',
        'pencil' => '<path d="M4 20h4L19 9l-4-4L4 16v4Z"/><path d="m13 7 4 4"/>',
        'layers' => '<path d="m12 3 9 5-9 5-9-5 9-5Z"/><path d="m3 13 9 5 9-5"/>',
        'logout' => '<path d="M14 4h5v16h-5"/><path d="M10 8l-4 4 4 4M6 12h10"/>',
        'cog' => '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
        'download' => '<path d="M12 4v11M7 10l5 5 5-5M5 20h14"/>',
        'chart' => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
        'arrow-left' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    ];
    $p = $paths[$name] ?? $paths['info'];
    return '<svg class="' . e($class) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

function status_badge(string $status): string
{
    $label = issue_statuses()[$status] ?? $status;
    return '<span class="pill ' . status_class($status) . '">' . e($label) . '</span>';
}

function moderation_badge(string $m): string
{
    return match ($m) {
        'pending' => '<span class="pill pill-warn">Aguardando moderação</span>',
        'rejected' => '<span class="pill pill-danger">Não aprovado</span>',
        'hidden' => '<span class="pill pill-danger">Oculto pela moderação</span>',
        default => '',
    };
}

function avatar(array $u, string $prefix = '', string $size = 'sm'): string
{
    $name = display_name($u, $prefix);
    $file = $u[$prefix . 'avatar'] ?? null;
    if ($file && empty($u[$prefix . 'deleted_at'])) {
        return '<img class="avatar avatar-' . $size . '" src="' . e(upload_url($file)) . '" alt="">';
    }
    $initial = mb_strtoupper(mb_substr($name, 0, 1));
    $hue = crc32($name) % 360;
    return '<span class="avatar avatar-' . $size . '" style="--h:' . $hue . '" aria-hidden="true">' . e($initial) . '</span>';
}
