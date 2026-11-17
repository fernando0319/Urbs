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
        'ogFile' => $p['after_file'],
        'description' => $p['summary'],
    ]);
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
    if (!$isAuthor) {
        log_mod('hide', 'proposal', (int)$p['id'], 'Excluída: ' . $p['title']);
    }
    delete_proposal((int)$p['id']);
    flash('ok', 'Proposta excluída.');
    redirect('/ocorrencias/' . $p['issue_id']);
}
