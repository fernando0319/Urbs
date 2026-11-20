<?php
declare(strict_types=1);

/**
 * Projetos comunitários: uma proposta escolhida pela comunidade vira um projeto com
 * organização responsável, orçamento, autorização, arrecadação externa, ofertas e prestação de contas.
 * A plataforma nunca recebe nem movimenta dinheiro.
 */

function projects_index(): void
{
    $where = is_mod() ? ['1=1'] : ["pj.moderation = 'approved'"];
    $params = [];
    $status = input('status');
    if (array_key_exists($status, project_statuses())) {
        $where[] = 'pj.status = ?';
        $params[] = $status;
    }
    $projects = all(PROJECT_SELECT . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY pj.updated_at DESC LIMIT 100', $params);
    foreach ($projects as &$pj) {
        $pj['totals'] = project_totals((int)$pj['id']);
    }
    unset($pj);
    render('projects/index', ['title' => 'Projetos comunitários', 'projects' => $projects, 'nav' => 'projects']);
}

/** Lê e valida os campos comuns de criação/edição. Retorna [dados, erros]. */
function project_form_data(bool $creating): array
{
    $errors = [];
    $title = input('title');
    $plan = input('plan');
    $duty = input('duty_type');
    $org = input('organization');
    $doc = preg_replace('/\D/', '', input('organization_doc'));
    $goalRaw = input('goal_amount');
    $goal = parse_money($goalRaw);
    $auth = input('auth_status');

    if (mb_strlen($title) < 5 || mb_strlen($title) > 160) $errors[] = 'O título deve ter entre 5 e 160 caracteres.';
    if (mb_strlen($plan) < 30 || mb_strlen($plan) > 20000) $errors[] = 'Descreva o plano de execução com pelo menos 30 caracteres: o que será feito, por quem e em quais etapas.';
    if (!array_key_exists($duty, duty_types())) $errors[] = 'Indique a natureza da melhoria.';
    if (mb_strlen($org) < 3 || mb_strlen($org) > 160) $errors[] = 'Informe a organização responsável (de preferência uma associação com CNPJ).';
    if ($doc !== '' && strlen($doc) !== 14) $errors[] = 'CNPJ inválido: informe os 14 dígitos.';
    if ($goalRaw !== '' && ($goal === null || $goal <= 0)) $errors[] = 'Meta de arrecadação inválida. Use, por exemplo, 12.500,00.';
    if (!array_key_exists($auth, auth_statuses())) $errors[] = 'Informe a situação da autorização.';

    $data = [
        'title' => $title,
        'plan' => $plan,
        'duty_type' => $duty,
        'organization' => $org,
        'organization_doc' => $doc ?: null,
        'goal_amount' => $goal,
        'auth_status' => $auth,
        'auth_body' => mb_substr(input('auth_body'), 0, 160) ?: null,
        'auth_protocol' => mb_substr(input('auth_protocol'), 0, 80) ?: null,
    ];

    if (!$creating) {
        $raisedRaw = input('raised_amount');
        $raised = $raisedRaw === '' ? 0.0 : parse_money($raisedRaw);
        if ($raised === null || $raised < 0) $errors[] = 'Valor arrecadado inválido.';
        $url = input('campaign_url');
        $cleanUrl = clean_url($url);
        if ($url !== '' && !$cleanUrl) $errors[] = 'Link da campanha inválido.';
        $reqAt = input('auth_requested_at');
        $decAt = input('auth_decided_at');
        if ($reqAt !== '' && !clean_date($reqAt)) $errors[] = 'Data do pedido de autorização inválida.';
        if ($decAt !== '' && !clean_date($decAt)) $errors[] = 'Data da resposta inválida.';
        $status = input('status');
        if (!array_key_exists($status, project_statuses())) $errors[] = 'Status inválido.';
        $data += [
            'raised_amount' => $raised ?? 0,
            'campaign_url' => $cleanUrl,
            'payment_info' => mb_substr(input('payment_info'), 0, 2000) ?: null,
            'auth_requested_at' => clean_date($reqAt),
            'auth_decided_at' => clean_date($decAt),
            'auth_notes' => mb_substr(input('auth_notes'), 0, 5000) ?: null,
            'status' => $status,
        ];
    }
    return [$data, $errors];
}

function project_new(string $proposalId): void
{
    $u = require_verified();
    $p = find_proposal((int)$proposalId);
    if (!$p || $p['moderation'] !== 'approved') {
        abort(404);
    }
    if ($p['license'] === 'arr' && (int)$p['user_id'] !== (int)$u['id'] && !is_mod($u)) {
        flash('warn', 'Esta proposta tem "todos os direitos reservados". Só o autor pode transformá-la em projeto comunitário. Fale com o autor nos comentários.');
        redirect('/propostas/' . $p['id']);
    }
    $errors = [];
    if (is_post()) {
        [$data, $errors] = project_form_data(true);
        if (!input('no_money_consent')) $errors[] = 'Confirme que entende que a plataforma não recebe nem administra dinheiro.';
        if (!input('accountability_consent')) $errors[] = 'Confirme que se responsabiliza pelas informações e pela prestação de contas.';
        if (!$errors && !rate_limit('project_create', (string)$u['id'], 3, 86400)) {
            $errors[] = 'Limite de 3 projetos por dia atingido.';
        }
        if (!$errors) {
            $id = insert('projects', $data + [
                'proposal_id' => $p['id'],
                'issue_id' => $p['issue_id'],
                'user_id' => $u['id'],
                'status' => 'preparing',
                'moderation' => is_mod($u) ? 'approved' : 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            project_log($id, 'Projeto criado a partir da proposta "' . $p['title'] . '".');
            flash('ok', is_mod($u)
                ? 'Projeto criado.'
                : 'Projeto enviado! Por envolver dinheiro, todo projeto passa pela moderação antes de aparecer. Enquanto isso, você já pode anexar orçamentos e documentos.');
            redirect('/projetos/' . $id);
        }
        $GLOBALS['URBS_OLD'] = $_POST;
    }
    render('projects/form', [
        'title' => 'Novo projeto comunitário',
        'p' => $p,
        'pj' => null,
        'errors' => $errors,
    ]);
}

function project_show(string $id): void
{
    $pj = find_project((int)$id);
    if (!$pj || !can_view($pj)) {
        abort(404);
    }
    $pid = (int)$pj['id'];
    $myMoney = uid() ? one("SELECT * FROM project_pledges WHERE project_id = ? AND user_id = ? AND kind = 'money' AND status <> 'withdrawn'", [$pid, uid()]) : null;
    render('projects/show', [
        'title' => $pj['title'],
        'pj' => $pj,
        'manage' => can_manage_project($pj),
        'totals' => project_totals($pid),
        'blockers' => fundraising_blockers($pj),
        'documents' => all('SELECT * FROM project_documents WHERE project_id = ? ORDER BY created_at DESC', [$pid]),
        'expenses' => all('SELECT * FROM project_expenses WHERE project_id = ? ORDER BY spent_on DESC, id DESC', [$pid]),
        'updates' => all('SELECT up.*, u.name AS author_name FROM project_updates up LEFT JOIN users u ON u.id = up.user_id
            WHERE up.project_id = ? ORDER BY up.created_at DESC, up.id DESC', [$pid]),
        'pledges' => all("SELECT pl.*, u.name AS author_name, u.deleted_at AS author_deleted_at FROM project_pledges pl
            JOIN users u ON u.id = pl.user_id WHERE pl.project_id = ? AND pl.status <> 'withdrawn' ORDER BY pl.created_at", [$pid]),
        'myMoney' => $myMoney,
        'comments' => load_comments('project', $pid),
        'ogFile' => $pj['proposal_after_file'],
        'description' => excerpt($pj['plan'], 160),
    ]);
}

function project_edit(string $id): void
{
    $u = require_verified();
    $pj = find_project((int)$id);
    if (!$pj || !can_manage_project($pj, $u)) {
        abort(404);
    }
    $errors = [];
    if (is_post()) {
        [$data, $errors] = project_form_data(false);
        if (!$errors && $data['status'] === 'fundraising' && $pj['status'] !== 'fundraising') {
            $blockers = fundraising_blockers(array_merge($pj, $data));
            if ($blockers) {
                $errors[] = 'Ainda não é possível abrir a arrecadação. Falta: ' . implode('; ', $blockers) . '.';
            }
        }
        if (!$errors) {
            $events = [];
            if ($data['organization'] !== $pj['organization'] || $data['organization_doc'] !== $pj['organization_doc']) {
                if ($pj['organization_verified_at']) {
                    $data['organization_verified_at'] = null;
                    $events[] = 'Organização responsável alterada para "' . $data['organization'] . '". A verificação precisa ser refeita pela moderação.';
                } else {
                    $events[] = 'Organização responsável alterada para "' . $data['organization'] . '".';
                }
            }
            if ($data['auth_status'] !== $pj['auth_status']) {
                $events[] = 'Autorização: ' . auth_statuses()[$data['auth_status']] . ($data['auth_body'] ? ' (' . $data['auth_body'] . ')' : '') . '.';
            }
            if (abs((float)$data['raised_amount'] - (float)$pj['raised_amount']) > 0.001) {
                $data['raised_updated_at'] = now();
                $events[] = 'Valor arrecadado informado pelo responsável: ' . money($data['raised_amount']) . '.';
            }
            if ((string)$data['goal_amount'] !== (string)$pj['goal_amount'] && $data['goal_amount'] !== null && (float)$data['goal_amount'] !== (float)$pj['goal_amount']) {
                $events[] = 'Meta alterada para ' . money($data['goal_amount']) . '.';
            }
            if ($data['status'] !== $pj['status']) {
                $events[] = 'Status: ' . project_statuses()[$data['status']] . '.';
                if (is_mod($u) && (int)$pj['user_id'] !== (int)$u['id']) {
                    log_mod('project_status', 'project', (int)$pj['id'], project_statuses()[$data['status']]);
                }
            }
            update('projects', $data + ['updated_at' => now()], 'id = :id', ['id' => $pj['id']]);
            foreach ($events as $ev) {
                project_log((int)$pj['id'], $ev);
            }
            if ($data['status'] === 'fundraising' && $pj['status'] !== 'fundraising') {
                notify_money_pledgers((int)$pj['id'], $data['title']);
            }
            flash('ok', 'Projeto atualizado.');
            redirect('/projetos/' . $pj['id']);
        }
        $GLOBALS['URBS_OLD'] = $_POST;
    }
    render('projects/form', [
        'title' => 'Editar projeto',
        'p' => find_proposal((int)$pj['proposal_id']),
        'pj' => $pj,
        'errors' => $errors,
    ]);
}

/** Quando a arrecadação abre, avisa quem registrou compromisso em dinheiro. */
function notify_money_pledgers(int $projectId, string $title): void
{
    $rows = all("SELECT DISTINCT u.name, u.email FROM project_pledges pl JOIN users u ON u.id = pl.user_id
        WHERE pl.project_id = ? AND pl.kind = 'money' AND pl.status IN ('offered','accepted') AND u.email IS NOT NULL AND u.deleted_at IS NULL", [$projectId]);
    $link = absolute_url('/projetos/' . $projectId);
    foreach ($rows as $r) {
        send_mail($r['email'], 'A arrecadação abriu: ' . $title,
            "Olá, {$r['name']}!\n\nVocê registrou um compromisso de contribuição para o projeto \"{$title}\". A autorização foi obtida e a arrecadação está aberta.\n\nVeja como contribuir:\n{$link}\n\nLembre-se: a plataforma não recebe dinheiro. Confira sempre se os dados de pagamento são da organização responsável indicada no projeto.");
    }
}

function project_require_manage(string $id): array
{
    $u = require_verified();
    $pj = find_project((int)$id);
    if (!$pj || !can_manage_project($pj, $u)) {
        abort(404);
    }
    return $pj;
}

function project_document_add(string $id): void
{
    $pj = project_require_manage($id);
    $kind = input('kind');
    $title = mb_substr(input('title'), 0, 160);
    $amountRaw = input('amount');
    $amount = parse_money($amountRaw);
    $f = uploaded_files('file')[0] ?? null;
    if (!array_key_exists($kind, project_document_kinds()) || mb_strlen($title) < 3 || !$f || ($amountRaw !== '' && $amount === null)) {
        flash('warn', 'Informe o tipo, um título e o arquivo (PDF ou imagem).');
        redirect('/projetos/' . $pj['id'] . '#documentos');
    }
    try {
        $file = project_save_file($f);
    } catch (RuntimeException $ex) {
        flash('warn', $ex->getMessage());
        redirect('/projetos/' . $pj['id'] . '#documentos');
    }
    insert('project_documents', [
        'project_id' => $pj['id'], 'user_id' => uid(), 'kind' => $kind, 'title' => $title, 'amount' => $amount,
        'file' => $file, 'original_name' => mb_substr($f['name'], 0, 190), 'created_at' => now(),
    ]);
    project_log((int)$pj['id'], project_document_kinds()[$kind] . ' anexado: ' . $title . ($amount !== null ? ' (' . money($amount) . ')' : '') . '.');
    flash('ok', 'Documento anexado.');
    redirect('/projetos/' . $pj['id'] . '#documentos');
}

/** PDFs ficam como estão; imagens são recodificadas (remove metadados). */
function project_save_file(array $f): string
{
    $mime = $f['error'] === UPLOAD_ERR_OK ? (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']) : '';
    if ($mime === 'application/pdf') {
        return save_pdf($f);
    }
    $img = save_image($f);
    delete_upload($img['thumb']);
    return $img['file'];
}

function project_document_delete(string $docId): void
{
    $d = one('SELECT * FROM project_documents WHERE id = ?', [(int)$docId]);
    if (!$d) {
        abort(404);
    }
    $pj = project_require_manage((string)$d['project_id']);
    delete_upload($d['file']);
    q('DELETE FROM project_documents WHERE id = ?', [$d['id']]);
    project_log((int)$pj['id'], 'Documento removido: ' . $d['title'] . '.');
    redirect('/projetos/' . $pj['id'] . '#documentos');
}

function project_expense_add(string $id): void
{
    $pj = project_require_manage($id);
    $desc = mb_substr(input('description'), 0, 255);
    $amount = parse_money(input('amount'));
    $date = clean_date(input('spent_on'));
    if (mb_strlen($desc) < 3 || $amount === null || $amount <= 0 || !$date) {
        flash('warn', 'Informe descrição, valor e data da despesa.');
        redirect('/projetos/' . $pj['id'] . '#contas');
    }
    $file = null;
    if ($f = uploaded_files('file')[0] ?? null) {
        try {
            $file = project_save_file($f);
        } catch (RuntimeException $ex) {
            flash('warn', $ex->getMessage());
            redirect('/projetos/' . $pj['id'] . '#contas');
        }
    }
    insert('project_expenses', [
        'project_id' => $pj['id'], 'user_id' => uid(), 'description' => $desc,
        'supplier' => mb_substr(input('supplier'), 0, 160) ?: null, 'amount' => $amount, 'spent_on' => $date,
        'file' => $file, 'created_at' => now(),
    ]);
    project_log((int)$pj['id'], 'Despesa registrada: ' . $desc . ' — ' . money($amount) . ($file ? ' (com comprovante)' : ' (sem comprovante)') . '.');
    flash('ok', 'Despesa registrada.');
    redirect('/projetos/' . $pj['id'] . '#contas');
}

function project_expense_delete(string $expId): void
{
    $e = one('SELECT * FROM project_expenses WHERE id = ?', [(int)$expId]);
    if (!$e) {
        abort(404);
    }
    $pj = project_require_manage((string)$e['project_id']);
    delete_upload($e['file']);
    q('DELETE FROM project_expenses WHERE id = ?', [$e['id']]);
    // A remoção fica registrada no diário: prestação de contas não pode sumir em silêncio.
    project_log((int)$pj['id'], 'Despesa removida pelo responsável: ' . $e['description'] . ' — ' . money($e['amount']) . '.');
    redirect('/projetos/' . $pj['id'] . '#contas');
}

function project_update_add(string $id): void
{
    $pj = project_require_manage($id);
    $body = input('body');
    if (mb_strlen($body) < 3 || mb_strlen($body) > 5000) {
        flash('warn', 'Escreva a atualização (até 5000 caracteres).');
        redirect('/projetos/' . $pj['id'] . '#diario');
    }
    $photo = null;
    if ($f = uploaded_files('photo')[0] ?? null) {
        try {
            $photo = save_image($f);
        } catch (RuntimeException $ex) {
            flash('warn', $ex->getMessage());
            redirect('/projetos/' . $pj['id'] . '#diario');
        }
    }
    insert('project_updates', [
        'project_id' => $pj['id'], 'user_id' => uid(), 'kind' => 'post', 'body' => $body,
        'photo' => $photo['file'] ?? null, 'photo_thumb' => $photo['thumb'] ?? null, 'created_at' => now(),
    ]);
    update('projects', ['updated_at' => now()], 'id = :id', ['id' => $pj['id']]);
    flash('ok', 'Atualização publicada.');
    redirect('/projetos/' . $pj['id'] . '#diario');
}

function project_pledge_add(string $id): void
{
    $u = require_verified();
    $pj = one("SELECT * FROM projects WHERE id = ? AND moderation = 'approved'", [(int)$id]);
    if (!$pj || in_array($pj['status'], ['done', 'cancelled'], true)) {
        abort(404);
    }
    $kind = input('kind');
    $desc = mb_substr(input('description'), 0, 500);
    $amountRaw = input('amount');
    $amount = parse_money($amountRaw);
    if (!array_key_exists($kind, pledge_kinds())) {
        flash('warn', 'Escolha o tipo de contribuição.');
        redirect('/projetos/' . $pj['id'] . '#ofertas');
    }
    if ($kind === 'money' && ($amount === null || $amount <= 0)) {
        flash('warn', 'Informe o valor que pretende contribuir.');
        redirect('/projetos/' . $pj['id'] . '#ofertas');
    }
    if ($kind !== 'money' && mb_strlen($desc) < 5) {
        flash('warn', 'Descreva o que você oferece (ex.: "retroescavadeira por um dia, sábado de manhã").');
        redirect('/projetos/' . $pj['id'] . '#ofertas');
    }
    if (!rate_limit('pledge', (string)$u['id'], 10, 86400)) {
        flash('warn', 'Muitas ofertas em pouco tempo. Tente mais tarde.');
        redirect('/projetos/' . $pj['id'] . '#ofertas');
    }
    if ($kind === 'money') {
        // Um compromisso em dinheiro por pessoa: um novo valor substitui o anterior.
        q("UPDATE project_pledges SET status = 'withdrawn', updated_at = ? WHERE project_id = ? AND user_id = ? AND kind = 'money' AND status <> 'withdrawn'",
            [now(), $pj['id'], $u['id']]);
        $desc = $desc ?: 'Compromisso de contribuição';
    }
    insert('project_pledges', [
        'project_id' => $pj['id'], 'user_id' => $u['id'], 'kind' => $kind, 'description' => $desc,
        'amount' => $kind === 'money' || $amountRaw !== '' ? $amount : null,
        'status' => 'offered', 'created_at' => now(), 'updated_at' => now(),
    ]);
    $owner = one('SELECT name, email FROM users WHERE id = ? AND deleted_at IS NULL', [$pj['user_id']]);
    if ($owner && $owner['email'] && (int)$pj['user_id'] !== (int)$u['id']) {
        send_mail($owner['email'], 'Nova oferta para o projeto ' . $pj['title'],
            "Olá, {$owner['name']}!\n\n{$u['name']} ofereceu: " . pledge_kinds()[$kind] . ($amount ? ' — ' . money($amount) : '') . "\n{$desc}\n\nVeja e responda em:\n" . absolute_url('/projetos/' . $pj['id']) . '#ofertas');
    }
    flash('ok', $kind === 'money'
        ? 'Compromisso registrado. Nenhum dinheiro é cobrado agora: você será avisado por e-mail quando a arrecadação abrir.'
        : 'Oferta registrada. O responsável pelo projeto foi avisado.');
    redirect('/projetos/' . $pj['id'] . '#ofertas');
}

function pledge_status(string $pledgeId): void
{
    $u = require_login();
    $pl = one('SELECT * FROM project_pledges WHERE id = ?', [(int)$pledgeId]);
    if (!$pl) {
        abort(404);
    }
    $pj = find_project((int)$pl['project_id']);
    $status = input('status');
    $isOwner = (int)$pl['user_id'] === (int)$u['id'];
    if ($status === 'withdrawn' && $isOwner) {
        update('project_pledges', ['status' => 'withdrawn', 'updated_at' => now()], 'id = :id', ['id' => $pl['id']]);
        flash('ok', 'Oferta retirada.');
    } elseif (in_array($status, ['accepted', 'delivered', 'declined', 'offered'], true) && $pj && can_manage_project($pj, $u)) {
        update('project_pledges', ['status' => $status, 'updated_at' => now()], 'id = :id', ['id' => $pl['id']]);
        if ($status === 'delivered') {
            $name = (string)val('SELECT name FROM users WHERE id = ?', [$pl['user_id']]);
            project_log((int)$pl['project_id'], 'Contribuição recebida de ' . $name . ': ' . pledge_kinds()[$pl['kind']] . ' — ' . $pl['description'] . '.');
        }
        flash('ok', 'Oferta atualizada.');
    } else {
        abort(403);
    }
    redirect('/projetos/' . $pl['project_id'] . '#ofertas');
}

function project_verify_org(string $id): void
{
    require_mod();
    $pj = one('SELECT * FROM projects WHERE id = ?', [(int)$id]);
    if (!$pj) {
        abort(404);
    }
    $verify = input('verify') === '1';
    update('projects', ['organization_verified_at' => $verify ? now() : null], 'id = :id', ['id' => $pj['id']]);
    log_mod($verify ? 'verify_org' : 'unverify_org', 'project', (int)$pj['id'], $pj['organization'] . ($pj['organization_doc'] ? ' · CNPJ ' . $pj['organization_doc'] : ''));
    project_log((int)$pj['id'], $verify
        ? 'A moderação verificou a organização responsável: ' . $pj['organization'] . '.'
        : 'A moderação removeu a verificação da organização responsável.');
    flash('ok', $verify ? 'Organização verificada.' : 'Verificação removida.');
    redirect('/projetos/' . $pj['id']);
}
