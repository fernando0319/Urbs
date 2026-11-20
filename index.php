<?php
declare(strict_types=1);

// Servidor embutido do PHP (php -S): entrega arquivos estáticos diretamente.
if (PHP_SAPI === 'cli-server') {
    $p = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    if (preg_match('#^/(app|storage|scripts)/#', $p) || preg_match('#^/config\.php#', $p)) {
        http_response_code(403);
        exit('Forbidden');
    }
    if ($p !== '/' && is_file(__DIR__ . $p)) {
        return false;
    }
}

require __DIR__ . '/app/bootstrap.php';

foreach (glob(ROOT . '/app/controllers/*.php') as $file) {
    require $file;
}

$routes = [
    ['GET', '/', 'home_page'],
    ['GET', '/mapa', 'map_page'],
    ['GET', '/painel', 'panel_page'],
    ['GET', '/transparencia', 'transparency_page'],
    ['GET', '/(sobre|regras|privacidade|termos|participar)', 'static_page'],
    ['GET', '/offline', 'offline_page'],
    ['GET', '/manifest.webmanifest', 'manifest_page'],

    ['GET', '/api/ocorrencias', 'api_issues'],
    ['GET', '/api/proximas', 'api_near'],

    ['GET', '/ocorrencias', 'issues_index'],
    ['GET|POST', '/ocorrencias/nova', 'issue_new'],
    ['GET', '/ocorrencias/(\d+)', 'issue_show'],
    ['POST', '/ocorrencias/(\d+)/apoiar', 'issue_support'],
    ['POST', '/ocorrencias/(\d+)/status', 'issue_status'],
    ['POST', '/ocorrencias/(\d+)/excluir', 'issue_delete'],
    ['GET', '/ocorrencias/(\d+)/comparar', 'issue_compare'],
    ['POST', '/ocorrencias/(\d+)/preferir', 'issue_prefer'],
    ['GET|POST', '/ocorrencias/(\d+)/propor', 'proposal_new'],

    ['GET', '/propostas', 'proposals_index'],
    ['GET', '/propostas/(\d+)', 'proposal_show'],
    ['POST', '/propostas/(\d+)/excluir', 'proposal_delete'],
    ['GET|POST', '/propostas/(\d+)/editar', 'proposal_edit'],
    ['POST', '/propostas/(\d+)/sugerir', 'suggestion_create'],
    ['POST', '/sugestoes/(\d+)/apoiar', 'suggestion_support'],
    ['POST', '/sugestoes/(\d+)/responder', 'suggestion_reply'],
    ['POST', '/sugestoes/(\d+)/excluir', 'suggestion_delete'],

    ['GET', '/projetos', 'projects_index'],
    ['GET|POST', '/propostas/(\d+)/projeto', 'project_new'],
    ['GET', '/projetos/(\d+)', 'project_show'],
    ['GET|POST', '/projetos/(\d+)/editar', 'project_edit'],
    ['POST', '/projetos/(\d+)/documentos', 'project_document_add'],
    ['POST', '/projetos/documentos/(\d+)/excluir', 'project_document_delete'],
    ['POST', '/projetos/(\d+)/despesas', 'project_expense_add'],
    ['POST', '/projetos/despesas/(\d+)/excluir', 'project_expense_delete'],
    ['POST', '/projetos/(\d+)/atualizacoes', 'project_update_add'],
    ['POST', '/projetos/(\d+)/ofertas', 'project_pledge_add'],
    ['POST', '/ofertas/(\d+)/status', 'pledge_status'],
    ['POST', '/projetos/(\d+)/verificar-organizacao', 'project_verify_org'],

    ['POST', '/comentar', 'comment_create'],
    ['POST', '/comentarios/(\d+)/excluir', 'comment_delete'],
    ['POST', '/denunciar', 'report_create'],
    ['GET', '/perfil/(\d+)', 'profile_show'],

    ['GET|POST', '/entrar', 'auth_login'],
    ['GET|POST', '/cadastro', 'auth_register'],
    ['POST', '/sair', 'auth_logout'],
    ['GET', '/verificar-email', 'auth_verify_email'],
    ['GET|POST', '/esqueci-senha', 'auth_forgot'],
    ['GET|POST', '/redefinir-senha', 'auth_reset'],

    ['GET|POST', '/conta', 'account_page'],
    ['POST', '/conta/reenviar', 'account_resend'],
    ['POST', '/conta/senha', 'account_password'],
    ['POST', '/conta/verificacao', 'account_verification'],
    ['GET', '/conta/dados', 'account_export'],
    ['POST', '/conta/excluir', 'account_delete'],

    ['GET', '/moderacao', 'mod_queue'],
    ['POST', '/moderacao/acao', 'mod_action'],
    ['GET', '/moderacao/denuncias', 'mod_reports'],
    ['GET', '/moderacao/verificacoes', 'mod_verifications'],
    ['POST', '/moderacao/verificacoes/(\d+)', 'mod_verification_decide'],
    ['GET', '/moderacao/arquivo/(\d+)', 'mod_verification_file'],
    ['GET', '/moderacao/usuarios', 'mod_users'],
    ['POST', '/moderacao/usuarios/(\d+)', 'mod_user_action'],
    ['GET|POST', '/moderacao/configuracoes', 'admin_settings'],
    ['POST', '/moderacao/categorias', 'admin_categories'],
];

// Caminho da requisição (com ou sem URLs amigáveis).
if (isset($_GET['r']) && is_string($_GET['r'])) {
    $path = '/' . trim($_GET['r'], '/');
} else {
    $path = (string)parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
    $bp = base_path();
    if ($bp !== '' && str_starts_with($path, $bp)) {
        $path = substr($path, strlen($bp));
    }
    $path = '/' . trim(preg_replace('#^/index\.php#', '', $path), '/');
}
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

foreach ($routes as [$methods, $pattern, $handler]) {
    if (!preg_match('#^' . $pattern . '$#', $path, $m)) {
        continue;
    }
    if (!in_array($method, explode('|', $methods), true)) {
        http_response_code(405);
        exit('Método não permitido');
    }
    if ($method === 'POST') {
        csrf_check();
    }
    array_shift($m);
    $handler(...$m);
    exit;
}

abort(404);
