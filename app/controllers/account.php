<?php
declare(strict_types=1);

function account_page(): void
{
    $u = require_login();
    $errors = [];
    if (is_post()) {
        $name = input('name');
        $bio = mb_substr(input('bio'), 0, 1000);
        $type = input('profile_type');
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Informe seu nome (2 a 120 caracteres).';
        if (!array_key_exists($type, profile_types())) $errors[] = 'Tipo de perfil inválido.';
        $data = ['name' => $name, 'bio' => $bio ?: null, 'profile_type' => $type];
        if (!$errors && ($f = uploaded_files('avatar')[0] ?? null)) {
            try {
                $img = save_image($f);
                delete_upload($img['file']);
                delete_upload($u['avatar']);
                $data['avatar'] = $img['thumb'];
            } catch (RuntimeException $ex) {
                $errors[] = $ex->getMessage();
            }
        }
        if (!$errors) {
            update('users', $data, 'id = :id', ['id' => $u['id']]);
            flash('ok', 'Perfil atualizado.');
            redirect('/conta');
        }
    }
    $requests = all('SELECT * FROM verification_requests WHERE user_id = ? ORDER BY created_at DESC', [$u['id']]);
    $pendingIssues = all("SELECT id, title, moderation FROM issues WHERE user_id = ? AND moderation <> 'approved' ORDER BY created_at DESC", [$u['id']]);
    $pendingProposals = all("SELECT id, title, moderation FROM proposals WHERE user_id = ? AND moderation <> 'approved' ORDER BY created_at DESC", [$u['id']]);
    render('account', [
        'title' => 'Minha conta',
        'u' => $u,
        'errors' => $errors,
        'requests' => $requests,
        'pendingIssues' => $pendingIssues,
        'pendingProposals' => $pendingProposals,
        'nav' => 'account',
    ]);
}

function account_resend(): void
{
    $u = require_login();
    if ($u['email_verified_at']) {
        redirect('/conta');
    }
    if (rate_limit('resend', (string)$u['id'], 3, 3600)) {
        send_verification_email($u);
        flash('ok', 'Enviamos um novo link para ' . $u['email'] . '.');
    } else {
        flash('warn', 'Aguarde um pouco antes de pedir outro link.');
    }
    redirect('/conta');
}

function account_password(): void
{
    $u = require_login();
    $current = (string)($_POST['current'] ?? '');
    $new = (string)($_POST['password'] ?? '');
    if (!password_verify($current, (string)$u['password_hash'])) {
        flash('warn', 'Senha atual incorreta.');
    } elseif (strlen($new) < 8) {
        flash('warn', 'A nova senha deve ter pelo menos 8 caracteres.');
    } else {
        update('users', ['password_hash' => password_hash($new, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
        session_regenerate_id(true);
        flash('ok', 'Senha alterada.');
    }
    redirect('/conta');
}

function account_verification(): void
{
    $u = require_verified();
    $type = input('type');
    if (!in_array($type, ['professional', 'resident'], true)) {
        abort(404);
    }
    if (one("SELECT id FROM verification_requests WHERE user_id = ? AND type = ? AND status = 'pending'", [$u['id'], $type])) {
        flash('info', 'Você já tem um pedido em análise.');
        redirect('/conta');
    }
    $registry = mb_substr(input('registry'), 0, 60);
    $notes = mb_substr(input('notes'), 0, 1000);
    $file = null;
    if ($type === 'professional' && mb_strlen($registry) < 4) {
        flash('warn', 'Informe o número do registro no CAU ou CREA (ex.: CAU A123456-7).');
        redirect('/conta');
    }
    $up = uploaded_files('document')[0] ?? null;
    if ($type === 'resident' && !$up) {
        flash('warn', 'Envie um comprovante de endereço. Ele só é visto pela moderação e é apagado depois da análise.');
        redirect('/conta');
    }
    if ($up) {
        try {
            $file = save_private_file($up);
        } catch (RuntimeException $ex) {
            flash('warn', $ex->getMessage());
            redirect('/conta');
        }
    }
    insert('verification_requests', [
        'user_id' => $u['id'],
        'type' => $type,
        'registry' => $registry ?: null,
        'notes' => $notes ?: null,
        'file' => $file,
        'status' => 'pending',
        'created_at' => now(),
    ]);
    flash('ok', 'Pedido enviado. A moderação vai analisar em breve.');
    redirect('/conta');
}

/** LGPD: exportação dos dados pessoais em JSON. */
function account_export(): void
{
    $u = require_login();
    $data = [
        'exportado_em' => gmdate('c'),
        'conta' => array_diff_key($u, array_flip(['password_hash'])),
        'ocorrencias' => all('SELECT * FROM issues WHERE user_id = ?', [$u['id']]),
        'propostas' => all('SELECT * FROM proposals WHERE user_id = ?', [$u['id']]),
        'comentarios' => all('SELECT * FROM comments WHERE user_id = ?', [$u['id']]),
        'apoios' => all('SELECT * FROM supports WHERE user_id = ?', [$u['id']]),
        'preferencias' => all('SELECT * FROM preferences WHERE user_id = ?', [$u['id']]),
        'denuncias_feitas' => all('SELECT * FROM reports WHERE user_id = ?', [$u['id']]),
        'pedidos_de_verificacao' => all('SELECT id, type, registry, notes, status, created_at, reviewed_at FROM verification_requests WHERE user_id = ?', [$u['id']]),
    ];
    header('Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="meus-dados-urbs.json"');
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** LGPD: exclusão de conta. Dados pessoais são apagados; publicações podem ser removidas ou ficar anônimas. */
function account_delete(): void
{
    $u = require_login();
    if (!password_verify((string)($_POST['password'] ?? ''), (string)$u['password_hash'])) {
        flash('warn', 'Senha incorreta. A conta não foi excluída.');
        redirect('/conta');
    }
    $issueIds = array_column(all('SELECT DISTINCT issue_id FROM supports WHERE user_id = ?', [$u['id']]), 'issue_id');
    $proposalIds = array_column(all('SELECT DISTINCT proposal_id FROM preferences WHERE user_id = ?', [$u['id']]), 'proposal_id');
    $commentTargets = all('SELECT DISTINCT target_type, target_id FROM comments WHERE user_id = ?', [$u['id']]);

    q('DELETE FROM supports WHERE user_id = ?', [$u['id']]);
    q('DELETE FROM preferences WHERE user_id = ?', [$u['id']]);
    q('DELETE FROM comments WHERE user_id = ?', [$u['id']]);
    q('DELETE FROM tokens WHERE user_id = ?', [$u['id']]);
    foreach (all('SELECT file FROM verification_requests WHERE user_id = ?', [$u['id']]) as $vr) {
        delete_upload($vr['file'], 'storage/private');
    }
    q('DELETE FROM verification_requests WHERE user_id = ?', [$u['id']]);

    if (input('delete_content')) {
        foreach (all('SELECT id FROM proposals WHERE user_id = ?', [$u['id']]) as $p) {
            delete_proposal((int)$p['id']);
        }
        foreach (all('SELECT id FROM issues WHERE user_id = ?', [$u['id']]) as $i) {
            // Ocorrências com propostas de outras pessoas ficam (anônimas) para não apagar o trabalho delas.
            if (!(int)val('SELECT COUNT(*) FROM proposals WHERE issue_id = ?', [$i['id']])) {
                delete_issue((int)$i['id']);
            }
        }
    }
    delete_upload($u['avatar']);
    update('users', [
        'name' => 'Usuário removido',
        'email' => null,
        'password_hash' => null,
        'bio' => null,
        'avatar' => null,
        'registry' => null,
        'professional_verified_at' => null,
        'resident_verified_at' => null,
        'deleted_at' => now(),
    ], 'id = :id', ['id' => $u['id']]);

    foreach ($issueIds as $id) {
        if (one('SELECT id FROM issues WHERE id = ?', [$id])) recount_issue((int)$id);
    }
    foreach ($proposalIds as $id) {
        if (one('SELECT id FROM proposals WHERE id = ?', [$id])) recount_proposal((int)$id);
    }
    foreach ($commentTargets as $t) {
        $exists = one('SELECT id FROM ' . ($t['target_type'] === 'issue' ? 'issues' : 'proposals') . ' WHERE id = ?', [$t['target_id']]);
        if ($exists) {
            $t['target_type'] === 'issue' ? recount_issue((int)$t['target_id']) : recount_proposal((int)$t['target_id']);
        }
    }
    logout_user();
    flash('ok', 'Sua conta foi excluída e seus dados pessoais foram apagados.');
    redirect('/');
}
