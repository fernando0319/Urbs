<?php
declare(strict_types=1);

function mod_counts(): array
{
    return [
        'queue' => (int)val("SELECT COUNT(*) FROM issues WHERE moderation = 'pending'")
            + (int)val("SELECT COUNT(*) FROM proposals WHERE moderation = 'pending'")
            + (int)val("SELECT COUNT(*) FROM projects WHERE moderation = 'pending'"),
        'reports' => (int)val("SELECT COUNT(*) FROM reports WHERE status = 'open'"),
        'verifications' => (int)val("SELECT COUNT(*) FROM verification_requests WHERE status = 'pending'"),
    ];
}

function mod_queue(): void
{
    require_mod();
    $issues = all(ISSUE_SELECT . " WHERE i.moderation = 'pending' ORDER BY i.created_at");
    foreach ($issues as &$i) {
        $i['photos'] = all('SELECT * FROM issue_photos WHERE issue_id = ? ORDER BY sort', [$i['id']]);
    }
    unset($i);
    $proposals = all(PROPOSAL_SELECT . " WHERE p.moderation = 'pending' ORDER BY p.created_at");
    $projects = all(PROJECT_SELECT . " WHERE pj.moderation = 'pending' ORDER BY pj.created_at");
    render('mod/queue', ['title' => 'Moderação', 'issues' => $issues, 'proposals' => $proposals, 'projects' => $projects, 'counts' => mod_counts(), 'tab' => 'queue']);
}

function mod_action(): void
{
    require_mod();
    $type = input('target_type');
    $id = input_int('target_id');
    $action = input('action');
    $note = mb_substr(input('note'), 0, 500);

    if ($action === 'dismiss_report') {
        $r = one('SELECT * FROM reports WHERE id = ?', [input_int('report_id')]);
        if ($r) {
            update('reports', ['status' => 'dismissed', 'resolved_by' => uid(), 'resolved_at' => now()], 'id = :id', ['id' => $r['id']]);
            log_mod('dismiss_report', $r['target_type'], (int)$r['target_id'], $note);
        }
        flash('ok', 'Denúncia arquivada.');
        back('/moderacao/denuncias');
    }

    $table = ['issue' => 'issues', 'proposal' => 'proposals', 'comment' => 'comments', 'project' => 'projects', 'suggestion' => 'proposal_suggestions'][$type] ?? null;
    $map = ['approve' => 'approved', 'reject' => 'rejected', 'hide' => 'hidden', 'restore' => 'approved'];
    if (!$table || !isset($map[$action])) {
        abort(404);
    }
    $row = one("SELECT * FROM $table WHERE id = ?", [$id]);
    if (!$row) {
        abort(404);
    }
    update($table, ['moderation' => $map[$action]], 'id = :id', ['id' => $id]);
    log_mod($action, $type, $id, $note);
    q("UPDATE reports SET status = 'resolved', resolved_by = ?, resolved_at = ? WHERE target_type = ? AND target_id = ? AND status = 'open'",
        [uid(), now(), $type, $id]);

    if ($type === 'issue') {
        recount_issue($id);
    } elseif ($type === 'proposal') {
        recount_issue((int)$row['issue_id']);
        if ($map[$action] === 'approved') {
            q("UPDATE issues SET status = 'discussion', updated_at = ? WHERE id = ? AND status = 'open'", [now(), $row['issue_id']]);
        }
    } elseif ($type === 'comment') {
        recount_target($row['target_type'], (int)$row['target_id']);
    } elseif ($type === 'project') {
        project_log($id, match ($map[$action]) {
            'approved' => 'Projeto aprovado pela moderação.',
            'rejected' => 'Projeto não aprovado pela moderação.' . ($note ? ' Motivo: ' . $note : ''),
            default => 'Projeto ocultado pela moderação.' . ($note ? ' Motivo: ' . $note : ''),
        });
    }

    // Avisa o autor sobre aprovação ou recusa.
    if (in_array($type, ['issue', 'proposal', 'project'], true) && in_array($action, ['approve', 'reject'], true)) {
        $author = one('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [$row['user_id']]);
        if ($author && $author['email']) {
            $link = absolute_url(['issue' => '/ocorrencias/', 'proposal' => '/propostas/', 'project' => '/projetos/'][$type] . $id);
            $what = ['issue' => 'sua publicação', 'proposal' => 'sua proposta', 'project' => 'seu projeto comunitário'][$type];
            $msg = $action === 'approve'
                ? "Olá, {$author['name']}!\n\nA moderação aprovou {$what} \"{$row['title']}\". Ela já está visível para todos:\n\n{$link}"
                : "Olá, {$author['name']}!\n\nA moderação não aprovou {$what} \"{$row['title']}\"." . ($note ? "\n\nMotivo: $note" : '')
                    . "\n\nConsulte as regras de participação em " . absolute_url('/regras');
            send_mail($author['email'], ($action === 'approve' ? 'Publicação aprovada' : 'Publicação não aprovada') . ' — ' . setting('site_name'), $msg);
        }
    }
    flash('ok', 'Ação registrada.');
    back('/moderacao');
}

function mod_reports(): void
{
    require_mod();
    $reports = all("SELECT r.*, u.name AS reporter_name FROM reports r JOIN users u ON u.id = r.user_id
        WHERE r.status = 'open' ORDER BY r.created_at");
    foreach ($reports as &$r) {
        $r['target'] = match ($r['target_type']) {
            'issue' => one('SELECT id, title, moderation, description AS body FROM issues WHERE id = ?', [$r['target_id']]),
            'proposal' => one('SELECT id, title, moderation, summary AS body FROM proposals WHERE id = ?', [$r['target_id']]),
            'comment' => one('SELECT id, target_type, target_id, moderation, body FROM comments WHERE id = ?', [$r['target_id']]),
            'user' => one('SELECT id, name AS title, bio AS body, blocked_at FROM users WHERE id = ?', [$r['target_id']]),
            'project' => one('SELECT id, title, moderation, plan AS body FROM projects WHERE id = ?', [$r['target_id']]),
            'suggestion' => one('SELECT id, proposal_id, moderation, body FROM proposal_suggestions WHERE id = ?', [$r['target_id']]),
            default => null,
        };
    }
    unset($r);
    render('mod/reports', ['title' => 'Denúncias', 'reports' => $reports, 'counts' => mod_counts(), 'tab' => 'reports']);
}

function mod_verifications(): void
{
    require_mod();
    $requests = all("SELECT v.*, u.name, u.email, u.profile_type, u.created_at AS user_created_at FROM verification_requests v
        JOIN users u ON u.id = v.user_id WHERE v.status = 'pending' ORDER BY v.created_at");
    render('mod/verifications', ['title' => 'Verificações', 'requests' => $requests, 'counts' => mod_counts(), 'tab' => 'verifications']);
}

function mod_verification_decide(string $id): void
{
    require_mod();
    $v = one("SELECT * FROM verification_requests WHERE id = ? AND status = 'pending'", [(int)$id]);
    if (!$v) {
        abort(404);
    }
    $approve = input('decision') === 'approve';
    $note = mb_substr(input('note'), 0, 500);
    if ($approve) {
        if ($v['type'] === 'professional') {
            update('users', ['professional_verified_at' => now(), 'registry' => $v['registry']], 'id = :id', ['id' => $v['user_id']]);
        } else {
            update('users', ['resident_verified_at' => now()], 'id = :id', ['id' => $v['user_id']]);
            recount_user_footprint((int)$v['user_id']);
        }
    }
    // O documento enviado é apagado após a análise (minimização de dados — LGPD).
    delete_upload($v['file'], 'storage/private');
    update('verification_requests', [
        'status' => $approve ? 'approved' : 'denied',
        'file' => null,
        'reviewed_by' => uid(),
        'reviewed_at' => now(),
        'notes' => $note ?: $v['notes'],
    ], 'id = :id', ['id' => $v['id']]);
    log_mod($approve ? 'verify_' . $v['type'] : 'deny_verification', 'user', (int)$v['user_id'], $note);

    $u = one('SELECT * FROM users WHERE id = ?', [$v['user_id']]);
    if ($u && $u['email']) {
        $label = $v['type'] === 'professional' ? 'profissional' : 'de morador';
        send_mail($u['email'], 'Verificação ' . $label . ' — ' . setting('site_name'),
            "Olá, {$u['name']}!\n\nSeu pedido de verificação {$label} foi " . ($approve ? 'aprovado. O selo já aparece no seu perfil.' : 'negado.')
            . ($note ? "\n\nObservação da moderação: $note" : ''));
    }
    flash('ok', $approve ? 'Verificação aprovada.' : 'Verificação negada.');
    redirect('/moderacao/verificacoes');
}

function mod_verification_file(string $id): void
{
    require_mod();
    $v = one('SELECT * FROM verification_requests WHERE id = ?', [(int)$id]);
    if (!$v || !$v['file'] || str_contains($v['file'], '..')) {
        abort(404);
    }
    $path = ROOT . '/storage/private/' . $v['file'];
    if (!is_file($path)) {
        abort(404);
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="documento.' . pathinfo($path, PATHINFO_EXTENSION) . '"');
    header('Cache-Control: private, no-store');
    header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; plugin-types application/pdf");
    readfile($path);
    exit;
}

function mod_users(): void
{
    require_mod();
    $where = ['deleted_at IS NULL'];
    $params = [];
    if (($term = input('q')) !== '') {
        $where[] = "(name LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
        $like = like_term($term);
        array_push($params, $like, $like);
    }
    $filter = input('filter');
    if ($filter === 'blocked') $where[] = 'blocked_at IS NOT NULL';
    if ($filter === 'staff') $where[] = "role IN ('moderator','admin')";
    if ($filter === 'verified') $where[] = '(professional_verified_at IS NOT NULL OR resident_verified_at IS NOT NULL)';
    $users = all('SELECT * FROM users WHERE ' . implode(' AND ', $where) . ' ORDER BY created_at DESC LIMIT 100', $params);
    render('mod/users', ['title' => 'Usuários', 'users' => $users, 'counts' => mod_counts(), 'tab' => 'users']);
}

function mod_user_action(string $id): void
{
    $me = require_mod();
    $u = one('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL', [(int)$id]);
    if (!$u) {
        abort(404);
    }
    if ((int)$u['id'] === (int)$me['id']) {
        flash('warn', 'Você não pode alterar a própria conta por aqui.');
        redirect('/moderacao/usuarios');
    }
    if (is_admin($u) && !is_admin($me)) {
        abort(403);
    }
    $action = input('action');
    $note = mb_substr(input('note'), 0, 255);
    switch ($action) {
        case 'block':
            update('users', ['blocked_at' => now(), 'block_reason' => $note ?: null], 'id = :id', ['id' => $u['id']]);
            break;
        case 'unblock':
            update('users', ['blocked_at' => null, 'block_reason' => null], 'id = :id', ['id' => $u['id']]);
            break;
        case 'revoke_professional':
            update('users', ['professional_verified_at' => null], 'id = :id', ['id' => $u['id']]);
            break;
        case 'revoke_resident':
            update('users', ['resident_verified_at' => null], 'id = :id', ['id' => $u['id']]);
            recount_user_footprint((int)$u['id']);
            break;
        case 'role':
            if (!is_admin($me)) {
                abort(403);
            }
            $role = input('role');
            if (!in_array($role, ['user', 'moderator', 'admin'], true)) {
                abort(404);
            }
            update('users', ['role' => $role], 'id = :id', ['id' => $u['id']]);
            $note = $role;
            break;
        default:
            abort(404);
    }
    log_mod($action, 'user', (int)$u['id'], $note);
    flash('ok', 'Usuário atualizado.');
    back('/moderacao/usuarios');
}

function admin_settings(): void
{
    require_admin();
    $keys = array_keys(default_settings());
    if (is_post()) {
        foreach ($keys as $k) {
            if (in_array($k, ['registration_open', 'require_email_verification'], true)) {
                save_setting($k, input($k) ? '1' : '0');
            } elseif (isset($_POST[$k])) {
                save_setting($k, mb_substr(input($k), 0, 5000));
            }
        }
        flash('ok', 'Configurações salvas.');
        redirect('/moderacao/configuracoes');
    }
    $values = [];
    foreach ($keys as $k) {
        $values[$k] = setting($k);
    }
    render('mod/settings', ['title' => 'Configurações', 'values' => $values, 'categories' => categories(false), 'counts' => mod_counts(), 'tab' => 'settings']);
}

function admin_categories(): void
{
    require_admin();
    $id = input_int('id');
    $name = mb_substr(input('name'), 0, 80);
    $color = preg_match('/^#[0-9a-fA-F]{6}$/', input('color')) ? input('color') : '#2563eb';
    $data = ['name' => $name, 'color' => $color, 'sort' => input_int('sort'), 'active' => input('active') ? 1 : 0];
    if ($name === '') {
        flash('warn', 'Informe o nome da categoria.');
    } elseif ($id) {
        update('categories', $data, 'id = :id', ['id' => $id]);
        flash('ok', 'Categoria atualizada.');
    } else {
        insert('categories', $data);
        flash('ok', 'Categoria criada.');
    }
    redirect('/moderacao/configuracoes#categorias');
}
