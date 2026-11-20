<?php
declare(strict_types=1);

/** Regras de negócio compartilhadas entre as páginas. */

const ISSUE_SELECT = 'SELECT i.*, c.name AS category_name, c.color AS category_color,
    u.name AS author_name, u.avatar AS author_avatar, u.deleted_at AS author_deleted_at,
    u.professional_verified_at AS author_professional_verified_at, u.resident_verified_at AS author_resident_verified_at,
    (SELECT p.thumb FROM issue_photos p WHERE p.issue_id = i.id ORDER BY p.sort, p.id LIMIT 1) AS cover_thumb
  FROM issues i
  LEFT JOIN categories c ON c.id = i.category_id
  JOIN users u ON u.id = i.user_id';

const PROPOSAL_SELECT = 'SELECT p.*, u.name AS author_name, u.avatar AS author_avatar, u.deleted_at AS author_deleted_at,
    u.profile_type AS author_profile_type, u.registry AS author_registry, u.role AS author_role,
    u.professional_verified_at AS author_professional_verified_at, u.resident_verified_at AS author_resident_verified_at,
    i.title AS issue_title, i.address AS issue_address, i.status AS issue_status
  FROM proposals p
  JOIN users u ON u.id = p.user_id
  JOIN issues i ON i.id = p.issue_id';

function find_issue(int $id): ?array
{
    return one(ISSUE_SELECT . ' WHERE i.id = ?', [$id]);
}

function find_proposal(int $id): ?array
{
    return one(PROPOSAL_SELECT . ' WHERE p.id = ?', [$id]);
}

/** Conteúdo visível: aprovado, ou do próprio autor, ou para moderadores. */
function can_view(array $row): bool
{
    return $row['moderation'] === 'approved' || is_mod() || (int)$row['user_id'] === uid();
}

/** Converte prefixo author_ em chaves de usuário para usar com user_badges()/avatar(). */
function author_of(array $row): array
{
    return [
        'name' => $row['author_name'] ?? '',
        'avatar' => $row['author_avatar'] ?? null,
        'deleted_at' => $row['author_deleted_at'] ?? null,
        'professional_verified_at' => $row['author_professional_verified_at'] ?? null,
        'resident_verified_at' => $row['author_resident_verified_at'] ?? null,
        'registry' => $row['author_registry'] ?? null,
        'role' => $row['author_role'] ?? 'user',
    ];
}

function recount_issue(int $id): void
{
    $s = (int)val('SELECT COUNT(*) FROM supports WHERE issue_id = ?', [$id]);
    $sv = (int)val('SELECT COUNT(*) FROM supports s JOIN users u ON u.id = s.user_id
        WHERE s.issue_id = ? AND u.resident_verified_at IS NOT NULL', [$id]);
    $p = (int)val("SELECT COUNT(*) FROM proposals WHERE issue_id = ? AND moderation = 'approved'", [$id]);
    $c = (int)val("SELECT COUNT(*) FROM comments WHERE target_type = 'issue' AND target_id = ? AND moderation = 'approved'", [$id]);
    update('issues', [
        'support_count' => $s,
        'support_verified_count' => $sv,
        'proposal_count' => $p,
        'comment_count' => $c,
    ], 'id = :id', ['id' => $id]);
}

function recount_proposal(int $id): void
{
    $pr = (int)val('SELECT COUNT(*) FROM preferences WHERE proposal_id = ?', [$id]);
    $pv = (int)val('SELECT COUNT(*) FROM preferences f JOIN users u ON u.id = f.user_id
        WHERE f.proposal_id = ? AND u.resident_verified_at IS NOT NULL', [$id]);
    $c = (int)val("SELECT COUNT(*) FROM comments WHERE target_type = 'proposal' AND target_id = ? AND moderation = 'approved'", [$id]);
    update('proposals', [
        'preference_count' => $pr,
        'preference_verified_count' => $pv,
        'comment_count' => $c,
    ], 'id = :id', ['id' => $id]);
}

/** Recalcula tudo que envolve um usuário (após verificação de morador ou exclusão de conta). */
function recount_user_footprint(int $userId): void
{
    foreach (all('SELECT issue_id FROM supports WHERE user_id = ?', [$userId]) as $r) {
        recount_issue((int)$r['issue_id']);
    }
    foreach (all('SELECT proposal_id FROM preferences WHERE user_id = ?', [$userId]) as $r) {
        recount_proposal((int)$r['proposal_id']);
    }
}

function log_mod(string $action, string $type, int $targetId, string $note = ''): void
{
    insert('mod_log', [
        'moderator_id' => uid(),
        'action' => $action,
        'target_type' => $type,
        'target_id' => $targetId,
        'note' => mb_substr($note, 0, 500) ?: null,
        'created_at' => now(),
    ]);
}

function mod_action_label(string $a): string
{
    return [
        'approve' => 'aprovou',
        'reject' => 'recusou',
        'hide' => 'ocultou',
        'restore' => 'restaurou',
        'status' => 'mudou o status de',
        'block' => 'bloqueou',
        'unblock' => 'desbloqueou',
        'verify_professional' => 'verificou o registro profissional de',
        'verify_resident' => 'verificou o endereço de',
        'deny_verification' => 'negou a verificação de',
        'revoke_professional' => 'removeu o selo profissional de',
        'revoke_resident' => 'removeu o selo de morador de',
        'role' => 'alterou o papel de',
        'dismiss_report' => 'arquivou uma denúncia sobre',
        'verify_org' => 'verificou a organização responsável pelo',
        'unverify_org' => 'removeu a verificação da organização do',
        'project_status' => 'mudou o status do',
    ][$a] ?? $a;
}

function target_label(string $t): string
{
    return ['issue' => 'ocorrência', 'proposal' => 'proposta', 'comment' => 'comentário', 'user' => 'usuário', 'project' => 'projeto comunitário', 'suggestion' => 'sugestão'][$t] ?? $t;
}

function categories(bool $onlyActive = true): array
{
    return all('SELECT * FROM categories' . ($onlyActive ? ' WHERE active = 1' : '') . ' ORDER BY sort, name');
}

/** Distância em metros (haversine). */
function distance_m(float $lat1, float $lng1, float $lat2, float $lng2): float
{
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return 2 * $r * asin(min(1, sqrt($a)));
}

/** Ocorrências aprovadas num raio (filtro por bounding box no banco, distância exata no PHP). */
function issues_near(float $lat, float $lng, int $radius, int $limit = 10): array
{
    $dLat = $radius / 111320;
    $dLng = $radius / (111320 * max(0.01, cos(deg2rad($lat))));
    $rows = all(
        "SELECT id, title, status, lat, lng, support_count FROM issues
         WHERE moderation = 'approved' AND lat BETWEEN ? AND ? AND lng BETWEEN ? AND ?",
        [$lat - $dLat, $lat + $dLat, $lng - $dLng, $lng + $dLng]
    );
    foreach ($rows as &$r) {
        $r['distance'] = (int)round(distance_m($lat, $lng, (float)$r['lat'], (float)$r['lng']));
    }
    unset($r);
    $rows = array_filter($rows, fn($r) => $r['distance'] <= $radius);
    usort($rows, fn($a, $b) => $a['distance'] <=> $b['distance']);
    return array_slice(array_values($rows), 0, $limit);
}

function validate_coords(string $lat, string $lng): ?array
{
    if (!is_numeric($lat) || !is_numeric($lng)) {
        return null;
    }
    $la = (float)$lat;
    $ln = (float)$lng;
    if ($la < -90 || $la > 90 || $ln < -180 || $ln > 180 || ($la == 0.0 && $ln == 0.0)) {
        return null;
    }
    return [round($la, 7), round($ln, 7)];
}

/** Remove uma proposta e seus arquivos. */
function delete_proposal(int $id): void
{
    $p = one('SELECT * FROM proposals WHERE id = ?', [$id]);
    if (!$p) {
        return;
    }
    foreach (all('SELECT * FROM proposal_files WHERE proposal_id = ?', [$id]) as $f) {
        delete_upload($f['file']);
        delete_upload($f['thumb']);
    }
    delete_upload($p['before_file']);
    delete_upload($p['after_file']);
    delete_upload($p['cover_thumb']);
    foreach (all('SELECT id FROM projects WHERE proposal_id = ?', [$id]) as $pj) {
        delete_project((int)$pj['id']);
    }
    foreach (all('SELECT id FROM proposal_suggestions WHERE proposal_id = ?', [$id]) as $s) {
        delete_suggestion((int)$s['id']);
    }
    foreach (all('SELECT prev_after_file FROM proposal_revisions WHERE proposal_id = ? AND prev_after_file IS NOT NULL', [$id]) as $r) {
        delete_upload($r['prev_after_file']);
    }
    q('DELETE FROM proposal_revisions WHERE proposal_id = ?', [$id]);
    q('DELETE FROM proposal_files WHERE proposal_id = ?', [$id]);
    q('DELETE FROM preferences WHERE proposal_id = ?', [$id]);
    q("DELETE FROM comments WHERE target_type = 'proposal' AND target_id = ?", [$id]);
    q('DELETE FROM proposals WHERE id = ?', [$id]);
    recount_issue((int)$p['issue_id']);
}

/** Remove uma ocorrência, suas fotos e propostas. */
function delete_issue(int $id): void
{
    foreach (all('SELECT id FROM proposals WHERE issue_id = ?', [$id]) as $p) {
        delete_proposal((int)$p['id']);
    }
    foreach (all('SELECT * FROM issue_photos WHERE issue_id = ?', [$id]) as $ph) {
        delete_upload($ph['file']);
        delete_upload($ph['thumb']);
    }
    q('DELETE FROM issue_photos WHERE issue_id = ?', [$id]);
    q('DELETE FROM supports WHERE issue_id = ?', [$id]);
    q('DELETE FROM preferences WHERE issue_id = ?', [$id]);
    q('DELETE FROM status_updates WHERE issue_id = ?', [$id]);
    q("DELETE FROM comments WHERE target_type = 'issue' AND target_id = ?", [$id]);
    q('DELETE FROM issues WHERE id = ?', [$id]);
}

// ---------------------------------------------------------------- Projetos comunitários

const PROJECT_SELECT = 'SELECT pj.*, u.name AS author_name, u.avatar AS author_avatar, u.deleted_at AS author_deleted_at,
    u.professional_verified_at AS author_professional_verified_at, u.resident_verified_at AS author_resident_verified_at,
    u.role AS author_role, u.registry AS author_registry,
    p.title AS proposal_title, p.cover_thumb AS cover_thumb, p.after_file AS proposal_after_file, p.license AS proposal_license,
    i.title AS issue_title, i.address AS issue_address
  FROM projects pj
  JOIN users u ON u.id = pj.user_id
  JOIN proposals p ON p.id = pj.proposal_id
  JOIN issues i ON i.id = pj.issue_id';

function find_project(int $id): ?array
{
    return one(PROJECT_SELECT . ' WHERE pj.id = ?', [$id]);
}

/** Responsável pelo projeto ou moderação. */
function can_manage_project(array $pj, ?array $u = null): bool
{
    $u ??= current_user();
    return $u && ((int)$pj['user_id'] === (int)$u['id'] || is_mod($u));
}

/**
 * A arrecadação só pode ser aberta quando o projeto está aprovado pela moderação,
 * a organização foi verificada e a autorização foi obtida (ou não é necessária).
 * Retorna a lista do que falta (vazia = pode arrecadar).
 */
function fundraising_blockers(array $pj): array
{
    $missing = [];
    if ($pj['moderation'] !== 'approved') $missing[] = 'aprovação do projeto pela moderação';
    if (!$pj['organization_verified_at']) $missing[] = 'verificação da organização responsável pela moderação';
    if (!in_array($pj['auth_status'], ['approved', 'not_required'], true)) $missing[] = 'autorização do órgão responsável pelo espaço';
    if (!$pj['campaign_url'] && !trim((string)$pj['payment_info'])) $missing[] = 'link da campanha ou dados para contribuição';
    return $missing;
}

/** Registra um evento no diário do projeto (mudanças de status, autorização etc.). */
function project_log(int $projectId, string $body, string $kind = 'event'): void
{
    insert('project_updates', [
        'project_id' => $projectId,
        'user_id' => uid() ?: 0,
        'kind' => $kind,
        'body' => mb_substr($body, 0, 2000),
        'created_at' => now(),
    ]);
    update('projects', ['updated_at' => now()], 'id = :id', ['id' => $projectId]);
}

function recount_project(int $id): void
{
    $c = (int)val("SELECT COUNT(*) FROM comments WHERE target_type = 'project' AND target_id = ? AND moderation = 'approved'", [$id]);
    update('projects', ['comment_count' => $c], 'id = :id', ['id' => $id]);
}

/** Totais de compromissos em dinheiro (intenções, não pagamentos) e de despesas comprovadas. */
function project_totals(int $id): array
{
    return [
        'committed' => (float)val("SELECT COALESCE(SUM(amount), 0) FROM project_pledges WHERE project_id = ? AND kind = 'money' AND status IN ('offered','accepted','delivered')", [$id]),
        'committed_people' => (int)val("SELECT COUNT(DISTINCT user_id) FROM project_pledges WHERE project_id = ? AND kind = 'money' AND status IN ('offered','accepted','delivered')", [$id]),
        'spent' => (float)val('SELECT COALESCE(SUM(amount), 0) FROM project_expenses WHERE project_id = ?', [$id]),
    ];
}

function delete_project(int $id): void
{
    foreach (all('SELECT file FROM project_documents WHERE project_id = ?', [$id]) as $d) delete_upload($d['file']);
    foreach (all('SELECT file FROM project_expenses WHERE project_id = ? AND file IS NOT NULL', [$id]) as $d) delete_upload($d['file']);
    foreach (all('SELECT photo, photo_thumb FROM project_updates WHERE project_id = ? AND photo IS NOT NULL', [$id]) as $d) {
        delete_upload($d['photo']);
        delete_upload($d['photo_thumb']);
    }
    foreach (['project_documents', 'project_expenses', 'project_updates', 'project_pledges'] as $t) {
        q("DELETE FROM $t WHERE project_id = ?", [$id]);
    }
    q("DELETE FROM comments WHERE target_type = 'project' AND target_id = ?", [$id]);
    q('DELETE FROM projects WHERE id = ?', [$id]);
}

// ---------------------------------------------------------------- Sugestões de alteração em propostas

function recount_suggestion(int $id): void
{
    $n = (int)val('SELECT COUNT(*) FROM suggestion_supports WHERE suggestion_id = ?', [$id]);
    update('proposal_suggestions', ['support_count' => $n], 'id = :id', ['id' => $id]);
}

function delete_suggestion(int $id): void
{
    $s = one('SELECT * FROM proposal_suggestions WHERE id = ?', [$id]);
    if (!$s) return;
    delete_upload($s['image']);
    delete_upload($s['image_thumb']);
    q('DELETE FROM suggestion_supports WHERE suggestion_id = ?', [$id]);
    q('DELETE FROM proposal_suggestions WHERE id = ?', [$id]);
}

/** Recalcula os contadores do alvo de um comentário. */
function recount_target(string $type, int $id): void
{
    match ($type) {
        'issue' => recount_issue($id),
        'proposal' => recount_proposal($id),
        'project' => recount_project($id),
        default => null,
    };
}
