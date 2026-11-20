<?php
declare(strict_types=1);

function proposals_index(): void
{
    $where = ["p.moderation = 'approved'", "i.moderation = 'approved'"];
    $params = [];
    if (($term = input('q')) !== '') {
        $where[] = "(p.title LIKE ? ESCAPE '!' OR p.summary LIKE ? ESCAPE '!' OR i.title LIKE ? ESCAPE '!')";
        $like = like_term($term);
        array_push($params, $like, $like, $like);
    }
    if ($cat = input_int('category')) {
        $where[] = 'i.category_id = ?';
        $params[] = $cat;
    }
    if (input('verified') === '1') {
        $where[] = 'u.professional_verified_at IS NOT NULL';
    }
    $order = input('sort') === 'popular' ? 'p.preference_count DESC, p.created_at DESC' : 'p.created_at DESC';
    $page = max(1, input_int('page', 1));
    $per = 24;
    $base = ' FROM proposals p JOIN users u ON u.id = p.user_id JOIN issues i ON i.id = p.issue_id WHERE ' . implode(' AND ', $where);
    $total = (int)val('SELECT COUNT(*)' . $base, $params);
    $proposals = all(PROPOSAL_SELECT . ' WHERE ' . implode(' AND ', $where) . " ORDER BY $order LIMIT $per OFFSET " . (($page - 1) * $per), $params);
    render('proposals/index', [
        'title' => 'Acervo de propostas',
        'proposals' => $proposals,
        'categories' => categories(),
        'page' => $page,
        'pages' => max(1, (int)ceil($total / $per)),
        'total' => $total,
        'nav' => 'proposals',
    ]);
}

function parse_money(string $v): ?float
{
    $v = preg_replace('/[^\d,.]/', '', $v);
    if ($v === '') {
        return null;
    }
    if (str_contains($v, ',')) {
        $v = str_replace('.', '', $v);
        $v = str_replace(',', '.', $v);
    }
    return is_numeric($v) ? round((float)$v, 2) : null;
}

function proposal_new(string $issueId): void
{
    $u = require_verified();
    $issue = find_issue((int)$issueId);
    if (!$issue || $issue['moderation'] !== 'approved') {
        abort(404);
    }
    $photos = all('SELECT * FROM issue_photos WHERE issue_id = ? ORDER BY sort, id', [$issue['id']]);
    $errors = [];

    if (is_post()) {
        $title = input('title');
        $summary = input('summary');
        $description = input('description');
        $costRaw = input('estimated_cost');
        $cost = parse_money($costRaw);
        $license = input('license');
        $beforePhotoId = input_int('before_photo');
        $beforeUp = uploaded_files('before')[0] ?? null;
        $afterUp = uploaded_files('after')[0] ?? null;
        $gallery = uploaded_files('gallery');
        $pdfs = uploaded_files('pdfs');

        if (mb_strlen($title) < 5 || mb_strlen($title) > 160) $errors[] = 'O título deve ter entre 5 e 160 caracteres.';
        if (mb_strlen($summary) < 10 || mb_strlen($summary) > 300) $errors[] = 'O resumo deve ter entre 10 e 300 caracteres.';
        if (mb_strlen($description) < 30 || mb_strlen($description) > 20000) $errors[] = 'Descreva a proposta com pelo menos 30 caracteres.';
        if ($costRaw !== '' && $cost === null) $errors[] = 'Custo estimado inválido. Use, por exemplo, 15.000,00.';
        if (!array_key_exists($license, licenses())) $errors[] = 'Escolha uma licença para o seu trabalho.';
        if (!$afterUp) $errors[] = 'Envie a imagem do "depois": ilustração, render, croqui ou fotomontagem da proposta.';
        if (count($gallery) > 8) $errors[] = 'Envie no máximo 8 imagens extras.';
        if (count($pdfs) > 5) $errors[] = 'Envie no máximo 5 PDFs.';
        if (!input('authorship')) $errors[] = 'Confirme que você é o autor (ou tem autorização) do material enviado.';
        if (!input('disclaimer')) $errors[] = 'Confirme que entende que a proposta é conceitual.';
        if (!$errors && !rate_limit('proposal_create', (string)$u['id'], 5, 86400)) {
            $errors[] = 'Limite de 5 propostas por dia atingido. Tente amanhã.';
        }

        $created = [];
        $cleanup = function () use (&$created) {
            foreach ($created as $f) {
                delete_upload($f);
            }
        };
        $before = $after = null;
        $galleryImgs = [];
        $pdfFiles = [];
        if (!$errors) {
            try {
                $after = save_image($afterUp);
                array_push($created, $after['file'], $after['thumb']);
                if ($beforeUp) {
                    $before = save_image($beforeUp);
                    array_push($created, $before['file'], $before['thumb']);
                } elseif ($beforePhotoId) {
                    $ph = one('SELECT * FROM issue_photos WHERE id = ? AND issue_id = ?', [$beforePhotoId, $issue['id']]);
                    if ($ph) {
                        [$rel, $abs] = upload_dir();
                        $name = bin2hex(random_bytes(10)) . '.jpg';
                        if (copy(ROOT . '/uploads/' . $ph['file'], "$abs/$name")) {
                            $before = ['file' => "$rel/$name", 'thumb' => null];
                            $created[] = "$rel/$name";
                        }
                    }
                }
                foreach ($gallery as $g) {
                    $img = save_image($g);
                    array_push($created, $img['file'], $img['thumb']);
                    $galleryImgs[] = $img + ['name' => $g['name'], 'size' => $g['size']];
                }
                foreach ($pdfs as $pdf) {
                    $path = save_pdf($pdf);
                    $created[] = $path;
                    $pdfFiles[] = ['file' => $path, 'name' => $pdf['name'], 'size' => $pdf['size']];
                }
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
                $cleanup();
            }
        }

        if (!$errors) {
            if ($before && $before['thumb']) {
                delete_upload($before['thumb']);
            }
            $moderation = initial_moderation($u);
            $id = insert('proposals', [
                'issue_id' => $issue['id'],
                'user_id' => $u['id'],
                'title' => $title,
                'summary' => $summary,
                'description' => $description,
                'estimated_cost' => $cost,
                'cost_notes' => mb_substr(input('cost_notes'), 0, 255) ?: null,
                'license' => $license,
                'before_file' => $before['file'] ?? null,
                'after_file' => $after['file'],
                'cover_thumb' => $after['thumb'],
                'moderation' => $moderation,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            foreach ($galleryImgs as $i => $g) {
                insert('proposal_files', [
                    'proposal_id' => $id, 'kind' => 'image', 'file' => $g['file'], 'thumb' => $g['thumb'],
                    'original_name' => mb_substr($g['name'], 0, 190), 'size' => (int)$g['size'], 'sort' => $i,
                ]);
            }
            foreach ($pdfFiles as $i => $p) {
                insert('proposal_files', [
                    'proposal_id' => $id, 'kind' => 'pdf', 'file' => $p['file'], 'thumb' => null,
                    'original_name' => mb_substr($p['name'], 0, 190), 'size' => (int)$p['size'], 'sort' => $i,
                ]);
            }
            if ($moderation === 'approved') {
                recount_issue((int)$issue['id']);
                if ($issue['status'] === 'open') {
                    update('issues', ['status' => 'discussion', 'updated_at' => now()], 'id = :id', ['id' => $issue['id']]);
                }
            }
            flash('ok', $moderation === 'approved'
                ? 'Proposta publicada! A comunidade já pode ver e comparar.'
                : 'Proposta recebida! Ela passa por uma moderação rápida antes de aparecer para todos.');
            redirect('/propostas/' . $id);
        }
        $GLOBALS['URBS_OLD'] = $_POST;
    }

    render('proposals/new', [
        'title' => 'Nova proposta — ' . $issue['title'],
        'issue' => $issue,
        'photos' => $photos,
        'errors' => $errors,
    ]);
}

function proposal_show(string $id): void
{
    $p = find_proposal((int)$id);
    if (!$p || !can_view($p)) {
        abort(404);
    }
    $files = all('SELECT * FROM proposal_files WHERE proposal_id = ? ORDER BY kind, sort, id', [$p['id']]);
    $siblings = all("SELECT id, title, cover_thumb, preference_count FROM proposals
        WHERE issue_id = ? AND id <> ? AND moderation = 'approved' ORDER BY created_at", [$p['issue_id'], $p['id']]);
    $mine = uid() ? val('SELECT proposal_id FROM preferences WHERE user_id = ? AND issue_id = ?', [uid(), $p['issue_id']]) : null;
    render('proposals/show', [
        'title' => $p['title'],
        'p' => $p,
        'images' => array_values(array_filter($files, fn($f) => $f['kind'] === 'image')),
        'pdfs' => array_values(array_filter($files, fn($f) => $f['kind'] === 'pdf')),
        'siblings' => $siblings,
        'comments' => load_comments('proposal', (int)$p['id']),
        'preferred' => $mine && (int)$mine === (int)$p['id'],
        'suggestions' => load_suggestions((int)$p['id']),
        'revisions' => all('SELECT * FROM proposal_revisions WHERE proposal_id = ? ORDER BY created_at DESC, id DESC', [$p['id']]),
        'projects' => all((is_mod() ? PROJECT_SELECT . ' WHERE pj.proposal_id = ?' : PROJECT_SELECT . " WHERE pj.proposal_id = ? AND (pj.moderation = 'approved' OR pj.user_id = " . uid() . ')') . ' ORDER BY pj.created_at', [$p['id']]),
        'ogFile' => $p['after_file'],
        'description' => $p['summary'],
    ]);
}

function load_suggestions(int $proposalId): array
{
    $vis = is_mod() ? "s.moderation <> 'deleted'" : "(s.moderation = 'approved' OR s.user_id = " . uid() . ')';
    $rows = all("SELECT s.*, u.name AS author_name, u.avatar AS author_avatar, u.deleted_at AS author_deleted_at,
            u.professional_verified_at AS author_professional_verified_at, u.resident_verified_at AS author_resident_verified_at,
            u.role AS author_role, u.registry AS author_registry
        FROM proposal_suggestions s JOIN users u ON u.id = s.user_id
        WHERE s.proposal_id = ? AND $vis ORDER BY s.support_count DESC, s.created_at", [$proposalId]);
    if (uid() && $rows) {
        $mine = array_column(all('SELECT suggestion_id FROM suggestion_supports WHERE user_id = ?', [uid()]), 'suggestion_id');
        $mine = array_flip(array_map('intval', $mine));
        foreach ($rows as &$r) {
            $r['supported'] = isset($mine[(int)$r['id']]);
        }
        unset($r);
    }
    return $rows;
}

function proposal_delete(string $id): void
{
    $u = require_login();
    $p = one('SELECT * FROM proposals WHERE id = ?', [(int)$id]);
    if (!$p) {
        abort(404);
    }
    $isAuthor = (int)$p['user_id'] === (int)$u['id'];
    if (!$isAuthor && !is_mod($u)) {
        abort(403);
    }
    if (!is_mod($u) && (int)val('SELECT COUNT(*) FROM projects WHERE proposal_id = ? AND user_id <> ?', [$p['id'], $u['id']])) {
        flash('warn', 'Esta proposta virou um projeto comunitário organizado por outras pessoas e não pode mais ser excluída pelo autor. Fale com a moderação.');
        redirect('/propostas/' . $p['id']);
    }
    if (!$isAuthor) {
        log_mod('hide', 'proposal', (int)$p['id'], 'Excluída: ' . $p['title']);
    }
    delete_proposal((int)$p['id']);
    flash('ok', 'Proposta excluída.');
    redirect('/ocorrencias/' . $p['issue_id']);
}

// ---------------------------------------------------------------- Sugestões de alteração

function suggestion_create(string $proposalId): void
{
    $u = require_verified();
    $p = one("SELECT * FROM proposals WHERE id = ? AND moderation = 'approved'", [(int)$proposalId]);
    if (!$p) {
        abort(404);
    }
    $back = '/propostas/' . $p['id'] . '#sugestoes';
    if ((int)$p['user_id'] === (int)$u['id']) {
        flash('info', 'Para mudar sua própria proposta, use "Editar proposta".');
        redirect($back);
    }
    $body = input('body');
    if (mb_strlen($body) < 10 || mb_strlen($body) > 2000) {
        flash('warn', 'Descreva a sugestão com 10 a 2000 caracteres.');
        redirect($back);
    }
    if (!rate_limit('suggestion', (string)$u['id'], 20, 86400)) {
        flash('warn', 'Muitas sugestões em pouco tempo. Tente mais tarde.');
        redirect($back);
    }
    $img = null;
    if ($f = uploaded_files('image')[0] ?? null) {
        try {
            $img = save_image($f);
        } catch (RuntimeException $ex) {
            flash('warn', $ex->getMessage());
            redirect($back);
        }
    }
    insert('proposal_suggestions', [
        'proposal_id' => $p['id'], 'user_id' => $u['id'], 'body' => $body,
        'image' => $img['file'] ?? null, 'image_thumb' => $img['thumb'] ?? null,
        'status' => 'open', 'moderation' => 'approved', 'created_at' => now(),
    ]);
    $author = one('SELECT name, email FROM users WHERE id = ? AND deleted_at IS NULL', [$p['user_id']]);
    if ($author && $author['email']) {
        send_mail($author['email'], 'Nova sugestão para sua proposta "' . $p['title'] . '"',
            "Olá, {$author['name']}!\n\n{$u['name']} sugeriu uma alteração na sua proposta:\n\n\"" . excerpt($body, 500) . "\"\n\nVocê pode responder, marcar como em análise ou incorporar numa nova versão:\n" . absolute_url('/propostas/' . $p['id']) . '#sugestoes');
    }
    flash('ok', 'Sugestão enviada ao autor da proposta.');
    redirect($back);
}

function suggestion_support(string $id): void
{
    $u = require_verified();
    $s = one("SELECT * FROM proposal_suggestions WHERE id = ? AND moderation = 'approved'", [(int)$id]);
    if (!$s) {
        abort(404);
    }
    if (one('SELECT 1 FROM suggestion_supports WHERE user_id = ? AND suggestion_id = ?', [$u['id'], $s['id']])) {
        q('DELETE FROM suggestion_supports WHERE user_id = ? AND suggestion_id = ?', [$u['id'], $s['id']]);
    } else {
        insert('suggestion_supports', ['user_id' => $u['id'], 'suggestion_id' => $s['id'], 'created_at' => now()]);
    }
    recount_suggestion((int)$s['id']);
    redirect('/propostas/' . $s['proposal_id'] . '#sugestao-' . $s['id']);
}

function suggestion_reply(string $id): void
{
    $u = require_login();
    $s = one('SELECT s.*, p.user_id AS proposal_author, p.title AS proposal_title FROM proposal_suggestions s
        JOIN proposals p ON p.id = s.proposal_id WHERE s.id = ?', [(int)$id]);
    if (!$s || ((int)$s['proposal_author'] !== (int)$u['id'] && !is_mod($u))) {
        abort(403);
    }
    $status = input('status');
    if (!array_key_exists($status, suggestion_statuses())) {
        abort(404);
    }
    $reply = mb_substr(input('reply'), 0, 1000);
    update('proposal_suggestions', ['status' => $status, 'author_reply' => $reply ?: null, 'replied_at' => now()], 'id = :id', ['id' => $s['id']]);
    notify_suggester($s, $status, $reply);
    flash('ok', 'Resposta registrada.');
    redirect('/propostas/' . $s['proposal_id'] . '#sugestao-' . $s['id']);
}

function notify_suggester(array $s, string $status, string $reply): void
{
    $who = one('SELECT name, email FROM users WHERE id = ? AND deleted_at IS NULL', [$s['user_id']]);
    if (!$who || !$who['email']) {
        return;
    }
    send_mail($who['email'], 'Sua sugestão: ' . suggestion_statuses()[$status],
        "Olá, {$who['name']}!\n\nO autor da proposta \"{$s['proposal_title']}\" respondeu à sua sugestão.\n\nSituação: " . suggestion_statuses()[$status]
        . ($reply ? "\nResposta: $reply" : '') . "\n\n" . absolute_url('/propostas/' . $s['proposal_id']) . '#sugestao-' . $s['id']);
}

function suggestion_delete(string $id): void
{
    $u = require_login();
    $s = one('SELECT * FROM proposal_suggestions WHERE id = ?', [(int)$id]);
    if (!$s) {
        abort(404);
    }
    if ((int)$s['user_id'] === (int)$u['id']) {
        delete_suggestion((int)$s['id']);
    } elseif (is_mod($u)) {
        update('proposal_suggestions', ['moderation' => 'hidden'], 'id = :id', ['id' => $s['id']]);
        log_mod('hide', 'suggestion', (int)$s['id'], input('note'));
    } else {
        abort(403);
    }
    flash('ok', 'Sugestão removida.');
    redirect('/propostas/' . $s['proposal_id'] . '#sugestoes');
}

// ---------------------------------------------------------------- Edição pelo autor (nova versão)

function proposal_edit(string $id): void
{
    $u = require_verified();
    $p = find_proposal((int)$id);
    if (!$p || (int)$p['user_id'] !== (int)$u['id']) {
        abort(404);
    }
    $files = all('SELECT * FROM proposal_files WHERE proposal_id = ? ORDER BY kind, sort, id', [$p['id']]);
    $openSuggestions = all("SELECT s.*, u.name AS author_name FROM proposal_suggestions s JOIN users u ON u.id = s.user_id
        WHERE s.proposal_id = ? AND s.moderation = 'approved' AND s.status IN ('open','considering') ORDER BY s.support_count DESC, s.created_at", [$p['id']]);
    $errors = [];

    if (is_post()) {
        $title = input('title');
        $summary = input('summary');
        $description = input('description');
        $costRaw = input('estimated_cost');
        $cost = parse_money($costRaw);
        $note = input('note');
        $afterUp = uploaded_files('after')[0] ?? null;
        $beforeUp = uploaded_files('before')[0] ?? null;
        $gallery = uploaded_files('gallery');
        $pdfs = uploaded_files('pdfs');
        $remove = array_map('intval', (array)($_POST['remove_files'] ?? []));
        $incorporated = array_map('intval', (array)($_POST['incorporated'] ?? []));
        $validSugg = array_map('intval', array_column($openSuggestions, 'id'));
        $incorporated = array_values(array_intersect($incorporated, $validSugg));

        if (mb_strlen($title) < 5 || mb_strlen($title) > 160) $errors[] = 'O título deve ter entre 5 e 160 caracteres.';
        if (mb_strlen($summary) < 10 || mb_strlen($summary) > 300) $errors[] = 'O resumo deve ter entre 10 e 300 caracteres.';
        if (mb_strlen($description) < 30 || mb_strlen($description) > 20000) $errors[] = 'A descrição precisa de pelo menos 30 caracteres.';
        if ($costRaw !== '' && $cost === null) $errors[] = 'Custo estimado inválido.';
        if (mb_strlen($note) < 5 || mb_strlen($note) > 1000) $errors[] = 'Explique o que mudou nesta versão (5 a 1000 caracteres). A explicação fica visível no histórico.';
        $keptImages = count(array_filter($files, fn($f) => $f['kind'] === 'image' && !in_array((int)$f['id'], $remove, true)));
        $keptPdfs = count(array_filter($files, fn($f) => $f['kind'] === 'pdf' && !in_array((int)$f['id'], $remove, true)));
        if ($keptImages + count($gallery) > 8) $errors[] = 'No máximo 8 imagens complementares. Remova alguma antes de enviar novas.';
        if ($keptPdfs + count($pdfs) > 5) $errors[] = 'No máximo 5 PDFs. Remova algum antes de enviar novos.';

        $created = [];
        $after = $before = null;
        $newFiles = [];
        if (!$errors) {
            try {
                if ($afterUp) {
                    $after = save_image($afterUp);
                    array_push($created, $after['file'], $after['thumb']);
                }
                if ($beforeUp) {
                    $before = save_image($beforeUp);
                    delete_upload($before['thumb']);
                    $created[] = $before['file'];
                }
                foreach ($gallery as $g) {
                    $img = save_image($g);
                    array_push($created, $img['file'], $img['thumb']);
                    $newFiles[] = ['kind' => 'image', 'file' => $img['file'], 'thumb' => $img['thumb'], 'name' => $g['name'], 'size' => $g['size']];
                }
                foreach ($pdfs as $pdf) {
                    $path = save_pdf($pdf);
                    $created[] = $path;
                    $newFiles[] = ['kind' => 'pdf', 'file' => $path, 'thumb' => null, 'name' => $pdf['name'], 'size' => $pdf['size']];
                }
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
                foreach ($created as $c) delete_upload($c);
            }
        }

        if (!$errors) {
            $data = [
                'title' => $title, 'summary' => $summary, 'description' => $description,
                'estimated_cost' => $cost, 'cost_notes' => mb_substr(input('cost_notes'), 0, 255) ?: null,
                'updated_at' => now(),
            ];
            $prevAfter = null;
            if ($after) {
                // A imagem anterior fica guardada no histórico, para mostrar a evolução da proposta.
                $prevAfter = $p['after_file'];
                delete_upload($p['cover_thumb']);
                $data['after_file'] = $after['file'];
                $data['cover_thumb'] = $after['thumb'];
            }
            if ($before) {
                delete_upload($p['before_file']);
                $data['before_file'] = $before['file'];
            }
            update('proposals', $data, 'id = :id', ['id' => $p['id']]);
            foreach ($files as $f) {
                if (in_array((int)$f['id'], $remove, true)) {
                    delete_upload($f['file']);
                    delete_upload($f['thumb']);
                    q('DELETE FROM proposal_files WHERE id = ?', [$f['id']]);
                }
            }
            $sort = (int)val('SELECT COALESCE(MAX(sort), 0) FROM proposal_files WHERE proposal_id = ?', [$p['id']]);
            foreach ($newFiles as $nf) {
                insert('proposal_files', [
                    'proposal_id' => $p['id'], 'kind' => $nf['kind'], 'file' => $nf['file'], 'thumb' => $nf['thumb'],
                    'original_name' => mb_substr($nf['name'], 0, 190), 'size' => (int)$nf['size'], 'sort' => ++$sort,
                ]);
            }
            insert('proposal_revisions', [
                'proposal_id' => $p['id'], 'user_id' => $u['id'], 'note' => $note,
                'prev_after_file' => $prevAfter, 'incorporated' => $incorporated ? implode(',', $incorporated) : null,
                'created_at' => now(),
            ]);
            foreach ($openSuggestions as $s) {
                if (in_array((int)$s['id'], $incorporated, true)) {
                    update('proposal_suggestions', ['status' => 'incorporated', 'replied_at' => now(),
                        'author_reply' => mb_substr('Incorporada na nova versão: ' . $note, 0, 1000)], 'id = :id', ['id' => $s['id']]);
                    notify_suggester($s + ['proposal_title' => $title], 'incorporated', $note);
                }
            }
            flash('ok', 'Nova versão publicada. Quem já indicou preferência por esta proposta continua contando para ela.');
            redirect('/propostas/' . $p['id']);
        }
        $GLOBALS['URBS_OLD'] = $_POST;
    }

    render('proposals/edit', [
        'title' => 'Editar proposta',
        'p' => $p,
        'files' => $files,
        'openSuggestions' => $openSuggestions,
        'errors' => $errors,
    ]);
}
