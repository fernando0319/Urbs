<?php
declare(strict_types=1);

function issues_index(): void
{
    $where = ["i.moderation = 'approved'"];
    $params = [];
    $status = input('status');
    if (array_key_exists($status, issue_statuses())) {
        $where[] = 'i.status = ?';
        $params[] = $status;
    } else {
        $where[] = "i.status <> 'archived'";
    }
    if ($cat = input_int('category')) {
        $where[] = 'i.category_id = ?';
        $params[] = $cat;
    }
    if (array_key_exists($k = input('kind'), issue_kinds())) {
        $where[] = 'i.kind = ?';
        $params[] = $k;
    }
    if (($term = input('q')) !== '') {
        $where[] = "(i.title LIKE ? ESCAPE '!' OR i.description LIKE ? ESCAPE '!' OR i.address LIKE ? ESCAPE '!')";
        $like = like_term($term);
        array_push($params, $like, $like, $like);
    }
    $order = match (input('sort')) {
        'supports' => 'i.support_count DESC, i.created_at DESC',
        'proposals' => 'i.proposal_count DESC, i.created_at DESC',
        default => 'i.created_at DESC',
    };
    $page = max(1, input_int('page', 1));
    $per = 20;
    $total = (int)val('SELECT COUNT(*) FROM issues i WHERE ' . implode(' AND ', $where), $params);
    $issues = all(ISSUE_SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
    render('issues/index', [
        'title' => 'Ocorrências e sugestões',
        'issues' => $issues,
        'categories' => categories(),
        'page' => $page,
        'pages' => max(1, (int)ceil($total / $per)),
        'total' => $total,
        'nav' => 'issues',
    ]);
}

function issue_new(): void
{
    $u = require_verified();
    $errors = [];
    if (is_post()) {
        $kind = input('kind');
        $categoryId = input_int('category_id');
        $title = input('title');
        $description = input('description');
        $address = mb_substr(input('address'), 0, 255);
        $coords = validate_coords(input('lat'), input('lng'));
        $photos = uploaded_files('photos');

        if (!array_key_exists($kind, issue_kinds())) $errors[] = 'Escolha se é um problema ou uma sugestão.';
        if (!one('SELECT id FROM categories WHERE id = ? AND active = 1', [$categoryId])) $errors[] = 'Escolha uma categoria.';
        if (mb_strlen($title) < 5 || mb_strlen($title) > 160) $errors[] = 'O título deve ter entre 5 e 160 caracteres.';
        if (mb_strlen($description) < 10 || mb_strlen($description) > 5000) $errors[] = 'Descreva com pelo menos 10 caracteres (máximo 5000).';
        if (!$coords) $errors[] = 'Marque o local no mapa.';
        if (count($photos) > 5) $errors[] = 'Envie no máximo 5 fotos.';
        if ($photos && !input('photo_consent')) $errors[] = 'Confirme que as fotos não expõem rostos, placas de veículos ou dados pessoais.';
        if (!input('rules_consent')) $errors[] = 'Aceite as regras de participação.';
        if (!$errors && !rate_limit('issue_create', (string)$u['id'], 10, 86400)) {
            $errors[] = 'Você atingiu o limite de 10 publicações por dia. Tente amanhã.';
        }

        $saved = [];
        if (!$errors) {
            try {
                foreach ($photos as $f) {
                    $saved[] = save_image($f);
                }
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
                foreach ($saved as $s) {
                    delete_upload($s['file']);
                    delete_upload($s['thumb']);
                }
            }
        }

        if (!$errors) {
            $moderation = initial_moderation($u);
            $id = insert('issues', [
                'user_id' => $u['id'],
                'kind' => $kind,
                'category_id' => $categoryId,
                'title' => $title,
                'description' => $description,
                'lat' => $coords[0],
                'lng' => $coords[1],
                'address' => $address ?: null,
                'status' => 'open',
                'moderation' => $moderation,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($saved as $i => $s) {
                insert('issue_photos', ['issue_id' => $id, 'file' => $s['file'], 'thumb' => $s['thumb'], 'sort' => $i]);
            }
            flash('ok', $moderation === 'approved'
                ? 'Publicado! Agora qualquer pessoa pode apoiar e propor soluções.'
                : 'Recebido! Como é sua primeira publicação, ela passa por uma moderação rápida antes de aparecer para todos.');
            redirect('/ocorrencias/' . $id);
        }
        remember_old();
        $GLOBALS['URBS_OLD'] = $_SESSION['old'];
        unset($_SESSION['old']);
    }
    render('issues/new', [
        'title' => 'Registrar ocorrência',
        'categories' => categories(),
        'errors' => $errors,
        'nav' => 'new',
        'lat' => input('lat'),
        'lng' => input('lng'),
    ]);
}

function issue_show(string $id): void
{
    $issue = find_issue((int)$id);
    if (!$issue || !can_view($issue)) {
        abort(404);
    }
    $photos = all('SELECT * FROM issue_photos WHERE issue_id = ? ORDER BY sort, id', [$issue['id']]);
    $visibility = is_mod() ? '1=1' : "(p.moderation = 'approved' OR p.user_id = " . uid() . ')';
    $proposals = all(PROPOSAL_SELECT . " WHERE p.issue_id = ? AND $visibility ORDER BY p.preference_count DESC, p.created_at", [$issue['id']]);
    $comments = load_comments('issue', (int)$issue['id']);
    $timeline = all('SELECT s.*, u.name AS author_name FROM status_updates s JOIN users u ON u.id = s.user_id
        WHERE s.issue_id = ? ORDER BY s.created_at DESC', [$issue['id']]);
    $supported = uid() && one('SELECT 1 FROM supports WHERE user_id = ? AND issue_id = ?', [uid(), $issue['id']]);
    render('issues/show', [
        'title' => $issue['title'],
        'issue' => $issue,
        'photos' => $photos,
        'proposals' => $proposals,
        'comments' => $comments,
        'timeline' => $timeline,
        'projects' => all(PROJECT_SELECT . " WHERE pj.issue_id = ? AND pj.moderation = 'approved' ORDER BY pj.created_at", [$issue['id']]),
        'supported' => (bool)$supported,
        'leaflet' => true,
        'ogFile' => $photos[0]['file'] ?? null,
        'description' => excerpt($issue['description'], 160),
    ]);
}

function issue_support(string $id): void
{
    $u = require_verified();
    $issue = one("SELECT id FROM issues WHERE id = ? AND moderation = 'approved'", [(int)$id]);
    if (!$issue) {
        abort(404);
    }
    $exists = one('SELECT 1 FROM supports WHERE user_id = ? AND issue_id = ?', [$u['id'], $issue['id']]);
    if ($exists) {
        q('DELETE FROM supports WHERE user_id = ? AND issue_id = ?', [$u['id'], $issue['id']]);
    } else {
        insert('supports', ['user_id' => $u['id'], 'issue_id' => $issue['id'], 'created_at' => now()]);
    }
    recount_issue((int)$issue['id']);
    $count = (int)val('SELECT support_count FROM issues WHERE id = ?', [$issue['id']]);
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        json_out(['supported' => !$exists, 'count' => $count]);
    }
    back('/ocorrencias/' . $issue['id']);
}

function issue_status(string $id): void
{
    require_mod();
    $issue = one('SELECT * FROM issues WHERE id = ?', [(int)$id]);
    $status = input('status');
    if (!$issue || !array_key_exists($status, issue_statuses())) {
        abort(404);
    }
    $note = mb_substr(input('note'), 0, 500);
    update('issues', ['status' => $status, 'updated_at' => now()], 'id = :id', ['id' => $issue['id']]);
    insert('status_updates', [
        'issue_id' => $issue['id'],
        'user_id' => uid(),
        'status' => $status,
        'note' => $note ?: null,
        'created_at' => now(),
    ]);
    log_mod('status', 'issue', (int)$issue['id'], issue_statuses()[$status] . ($note ? " — $note" : ''));
    flash('ok', 'Status atualizado.');
    redirect('/ocorrencias/' . $issue['id']);
}

function issue_delete(string $id): void
{
    $u = require_login();
    $issue = one('SELECT * FROM issues WHERE id = ?', [(int)$id]);
    if (!$issue) {
        abort(404);
    }
    $others = (int)val('SELECT COUNT(*) FROM proposals WHERE issue_id = ? AND user_id <> ?', [$issue['id'], $u['id']]);
    $others += (int)val('SELECT COUNT(*) FROM projects WHERE issue_id = ? AND user_id <> ?', [$issue['id'], $u['id']]);
    $isAuthor = (int)$issue['user_id'] === (int)$u['id'];
    if (!is_mod($u) && !($isAuthor && $others === 0)) {
        flash('warn', 'Esta ocorrência já tem propostas de outras pessoas e não pode mais ser excluída pelo autor. Fale com a moderação.');
        redirect('/ocorrencias/' . $issue['id']);
    }
    if (!$isAuthor) {
        log_mod('hide', 'issue', (int)$issue['id'], 'Excluída: ' . $issue['title']);
    }
    delete_issue((int)$issue['id']);
    flash('ok', 'Ocorrência excluída.');
    redirect('/ocorrencias');
}

function issue_compare(string $id): void
{
    $issue = find_issue((int)$id);
    if (!$issue || $issue['moderation'] !== 'approved') {
        abort(404);
    }
    $proposals = all(PROPOSAL_SELECT . " WHERE p.issue_id = ? AND p.moderation = 'approved' ORDER BY p.created_at", [$issue['id']]);
    if (!$proposals) {
        flash('info', 'Ainda não há propostas para comparar aqui. Que tal enviar a primeira?');
        redirect('/ocorrencias/' . $issue['id']);
    }
    $mine = uid() ? val('SELECT proposal_id FROM preferences WHERE user_id = ? AND issue_id = ?', [uid(), $issue['id']]) : null;
    $total = array_sum(array_column($proposals, 'preference_count'));
    $totalVerified = array_sum(array_column($proposals, 'preference_verified_count'));
    $photo = one('SELECT * FROM issue_photos WHERE issue_id = ? ORDER BY sort, id LIMIT 1', [$issue['id']]);
    render('issues/compare', [
        'title' => 'Comparar propostas — ' . $issue['title'],
        'issue' => $issue,
        'proposals' => $proposals,
        'mine' => $mine ? (int)$mine : null,
        'total' => $total,
        'totalVerified' => $totalVerified,
        'photo' => $photo,
        'bodyClass' => 'page-wide',
    ]);
}

function issue_prefer(string $id): void
{
    $u = require_verified();
    $issue = one("SELECT id FROM issues WHERE id = ? AND moderation = 'approved'", [(int)$id]);
    if (!$issue) {
        abort(404);
    }
    $previous = val('SELECT proposal_id FROM preferences WHERE user_id = ? AND issue_id = ?', [$u['id'], $issue['id']]);
    $proposalId = input_int('proposal_id');
    q('DELETE FROM preferences WHERE user_id = ? AND issue_id = ?', [$u['id'], $issue['id']]);
    if ($proposalId && $proposalId !== (int)$previous) {
        $p = one("SELECT id FROM proposals WHERE id = ? AND issue_id = ? AND moderation = 'approved'", [$proposalId, $issue['id']]);
        if (!$p) {
            abort(404);
        }
        insert('preferences', ['user_id' => $u['id'], 'issue_id' => $issue['id'], 'proposal_id' => $proposalId, 'created_at' => now()]);
        flash('ok', 'Sua preferência foi registrada. Você pode mudar quando quiser.');
        recount_proposal($proposalId);
    } else {
        flash('info', 'Preferência removida.');
    }
    if ($previous) {
        recount_proposal((int)$previous);
    }
    redirect('/ocorrencias/' . $issue['id'] . '/comparar');
}

// ---------------------------------------------------------------- Comentários e denúncias

function load_comments(string $type, int $id): array
{
    $vis = is_mod() ? "c.moderation <> 'deleted'" : "(c.moderation = 'approved' OR c.user_id = " . uid() . ')';
    return all("SELECT c.*, u.name AS author_name, u.avatar AS author_avatar, u.deleted_at AS author_deleted_at,
            u.professional_verified_at AS author_professional_verified_at, u.resident_verified_at AS author_resident_verified_at,
            u.role AS author_role, u.registry AS author_registry
        FROM comments c JOIN users u ON u.id = c.user_id
        WHERE c.target_type = ? AND c.target_id = ? AND $vis ORDER BY c.created_at", [$type, $id]);
}

function comment_target(string $type, int $id): ?array
{
    return match ($type) {
        'issue' => one("SELECT id, user_id FROM issues WHERE id = ? AND moderation = 'approved'", [$id]),
        'proposal' => one("SELECT id, user_id, issue_id FROM proposals WHERE id = ? AND moderation = 'approved'", [$id]),
        'project' => one("SELECT id, user_id FROM projects WHERE id = ? AND moderation = 'approved'", [$id]),
        default => null,
    };
}

function target_url(string $type, int $id): string
{
    return match ($type) {
        'proposal' => '/propostas/' . $id,
        'project' => '/projetos/' . $id,
        default => '/ocorrencias/' . $id,
    };
}

function comment_create(): void
{
    $u = require_verified();
    $type = input('target_type');
    $id = input_int('target_id');
    $target = comment_target($type, $id);
    if (!$target) {
        abort(404);
    }
    $body = input('body');
    if (mb_strlen($body) < 2 || mb_strlen($body) > 2000) {
        flash('warn', 'O comentário deve ter entre 2 e 2000 caracteres.');
        back(target_url($type, $id));
    }
    if (!rate_limit('comment', (string)$u['id'], 30, 3600)) {
        flash('warn', 'Muitos comentários em pouco tempo. Aguarde um pouco.');
        back(target_url($type, $id));
    }
    insert('comments', [
        'user_id' => $u['id'],
        'target_type' => $type,
        'target_id' => $id,
        'body' => $body,
        'moderation' => 'approved',
        'created_at' => now(),
    ]);
    recount_target($type, $id);
    redirect(target_url($type, $id) . '#comentarios');
}

function comment_delete(string $id): void
{
    $u = require_login();
    $c = one('SELECT * FROM comments WHERE id = ?', [(int)$id]);
    if (!$c) {
        abort(404);
    }
    $isAuthor = (int)$c['user_id'] === (int)$u['id'];
    if (!$isAuthor && !is_mod($u)) {
        abort(403);
    }
    if ($isAuthor) {
        q('DELETE FROM comments WHERE id = ?', [$c['id']]);
    } else {
        update('comments', ['moderation' => 'hidden'], 'id = :id', ['id' => $c['id']]);
        log_mod('hide', 'comment', (int)$c['id'], input('note'));
    }
    recount_target($c['target_type'], (int)$c['target_id']);
    flash('ok', 'Comentário removido.');
    redirect(target_url($c['target_type'], (int)$c['target_id']) . '#comentarios');
}

function report_create(): void
{
    $u = require_login();
    $type = input('target_type');
    $id = input_int('target_id');
    $reason = input('reason');
    $table = ['issue' => 'issues', 'proposal' => 'proposals', 'comment' => 'comments', 'user' => 'users', 'project' => 'projects', 'suggestion' => 'proposal_suggestions'][$type] ?? null;
    if (!$table || !one("SELECT id FROM $table WHERE id = ?", [$id]) || !array_key_exists($reason, report_reasons())) {
        flash('warn', 'Escolha um motivo para a denúncia.');
        back();
    }
    $dup = one("SELECT id FROM reports WHERE user_id = ? AND target_type = ? AND target_id = ? AND status = 'open'", [$u['id'], $type, $id]);
    if (!$dup && rate_limit('report', (string)$u['id'], 20, 86400)) {
        insert('reports', [
            'user_id' => $u['id'],
            'target_type' => $type,
            'target_id' => $id,
            'reason' => $reason,
            'details' => mb_substr(input('details'), 0, 1000) ?: null,
            'status' => 'open',
            'created_at' => now(),
        ]);
    }
    flash('ok', 'Denúncia enviada. Obrigado por ajudar a manter o espaço saudável.');
    back();
}
