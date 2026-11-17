<?php
declare(strict_types=1);

function current_user(): ?array
{
    static $user = false;
    if ($user !== false) {
        return $user;
    }
    $id = (int)($_SESSION['uid'] ?? 0);
    $user = $id ? one('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$id]) : null;
    if ($user && $user['blocked_at']) {
        unset($_SESSION['uid']);
        $user = null;
    }
    return $user;
}

function uid(): int
{
    return (int)(current_user()['id'] ?? 0);
}

function login_user(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['uid'] = (int)$user['id'];
    update('users', ['last_login_at' => now()], 'id = :id', ['id' => $user['id']]);
}

function logout_user(): void
{
    $_SESSION = [];
    session_regenerate_id(true);
}

function is_mod(?array $u = null): bool
{
    $u ??= current_user();
    return $u && in_array($u['role'], ['moderator', 'admin'], true);
}

function is_admin(?array $u = null): bool
{
    $u ??= current_user();
    return $u && $u['role'] === 'admin';
}

function email_verified(?array $u = null): bool
{
    $u ??= current_user();
    if (!$u) {
        return false;
    }
    return !setting('require_email_verification', '1') || !empty($u['email_verified_at']);
}

function require_login(): array
{
    $u = current_user();
    if (!$u) {
        $_SESSION['after_login'] = $_SERVER['REQUEST_URI'] ?? url('/');
        flash('info', 'Entre na sua conta para continuar.');
        redirect('/entrar');
    }
    return $u;
}

/** Exige login e e-mail confirmado (necessário para publicar, apoiar e votar). */
function require_verified(): array
{
    $u = require_login();
    if (!email_verified($u)) {
        flash('warn', 'Confirme seu e-mail para participar. Verifique sua caixa de entrada ou peça um novo link na sua conta.');
        redirect('/conta');
    }
    return $u;
}

function require_mod(): array
{
    $u = require_login();
    if (!is_mod($u)) {
        abort(403);
    }
    return $u;
}

function require_admin(): array
{
    $u = require_login();
    if (!is_admin($u)) {
        abort(403);
    }
    return $u;
}

/**
 * Conteúdo de usuário confiável é publicado direto; o restante vai para a fila de moderação.
 * Confiável: moderador, profissional verificado, morador verificado ou quem já teve N publicações aprovadas.
 */
function user_is_trusted(array $u): bool
{
    if (is_mod($u) || $u['professional_verified_at'] || $u['resident_verified_at']) {
        return true;
    }
    $threshold = (int)setting('trust_threshold', '1');
    if ($threshold <= 0) {
        return true;
    }
    $approved = (int)val("SELECT COUNT(*) FROM issues WHERE user_id = ? AND moderation = 'approved'", [$u['id']])
        + (int)val("SELECT COUNT(*) FROM proposals WHERE user_id = ? AND moderation = 'approved'", [$u['id']]);
    return $approved >= $threshold;
}

function initial_moderation(array $u): string
{
    return user_is_trusted($u) ? 'approved' : 'pending';
}

/** Cria token de uso único e retorna o valor em texto (só o hash fica no banco). */
function create_token(int $userId, string $type, int $ttlSeconds): string
{
    $token = bin2hex(random_bytes(24));
    q('UPDATE tokens SET used_at = ? WHERE user_id = ? AND type = ? AND used_at IS NULL', [now(), $userId, $type]);
    insert('tokens', [
        'user_id' => $userId,
        'type' => $type,
        'token_hash' => hash('sha256', $token),
        'expires_at' => now($ttlSeconds),
    ]);
    return $token;
}

function consume_token(string $token, string $type): ?int
{
    $row = one(
        'SELECT * FROM tokens WHERE token_hash = ? AND type = ? AND used_at IS NULL AND expires_at > ?',
        [hash('sha256', $token), $type, now()]
    );
    if (!$row) {
        return null;
    }
    update('tokens', ['used_at' => now()], 'id = :id', ['id' => $row['id']]);
    return (int)$row['user_id'];
}

function send_verification_email(array $user): void
{
    $token = create_token((int)$user['id'], 'verify_email', 86400 * 3);
    $link = absolute_url('/verificar-email', ['t' => $token]);
    send_mail(
        $user['email'],
        'Confirme seu e-mail — ' . setting('site_name'),
        "Olá, {$user['name']}!\n\nPara confirmar seu e-mail e começar a participar, acesse:\n\n{$link}\n\nO link vale por 3 dias. Se você não criou esta conta, ignore esta mensagem."
    );
}

/** Selos exibidos ao lado do nome. */
function user_badges(array $u): string
{
    $out = '';
    if (!empty($u['professional_verified_at'])) {
        $title = 'Profissional verificado' . (!empty($u['registry']) ? ' — ' . $u['registry'] : '');
        $out .= '<span class="badge badge-pro" title="' . e($title) . '">' . icon('shield') . 'Profissional verificado</span>';
    }
    if (!empty($u['resident_verified_at'])) {
        $out .= '<span class="badge badge-res" title="Morador com endereço verificado">' . icon('home') . 'Morador verificado</span>';
    }
    if (($u['role'] ?? '') === 'moderator' || ($u['role'] ?? '') === 'admin') {
        $out .= '<span class="badge badge-mod">Moderação</span>';
    }
    return $out;
}

function display_name(array $row, string $prefix = ''): string
{
    $deleted = $row[$prefix . 'deleted_at'] ?? null;
    return $deleted ? 'Usuário removido' : (string)($row[$prefix . 'name'] ?? 'Usuário');
}
