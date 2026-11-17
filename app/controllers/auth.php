<?php
declare(strict_types=1);

function auth_login(): void
{
    if (current_user()) {
        redirect('/');
    }
    $error = null;
    if (is_post()) {
        $email = mb_strtolower(input('email'));
        $password = (string)($_POST['password'] ?? '');
        if (!rate_limit('login', client_ip() . '|' . $email, 8, 900)) {
            $error = 'Muitas tentativas. Aguarde 15 minutos.';
        } else {
            $u = one('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [$email]);
            if (!$u || !$u['password_hash'] || !password_verify($password, $u['password_hash'])) {
                $error = 'E-mail ou senha incorretos.';
            } elseif ($u['blocked_at']) {
                $error = 'Esta conta está bloqueada' . ($u['block_reason'] ? ': ' . $u['block_reason'] : '.') . ' Fale com a moderação se achar que é um engano.';
            } else {
                if (password_needs_rehash($u['password_hash'], PASSWORD_DEFAULT)) {
                    update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $u['id']]);
                }
                login_user($u);
                $to = $_SESSION['after_login'] ?? url('/');
                unset($_SESSION['after_login']);
                header('Location: ' . (str_starts_with($to, '/') && !str_starts_with($to, '//') ? $to : url('/')));
                exit;
            }
        }
        $GLOBALS['URBS_OLD'] = ['email' => $email];
    }
    render('auth/login', ['title' => 'Entrar', 'error' => $error]);
}

function auth_register(): void
{
    if (current_user()) {
        redirect('/');
    }
    if (!setting('registration_open', '1')) {
        render('pages/error', ['title' => 'Cadastro fechado', 'code' => 403, 'message' => 'Novos cadastros estão temporariamente fechados.']);
        return;
    }
    $errors = [];
    if (is_post()) {
        $name = input('name');
        $email = mb_strtolower(input('email'));
        $password = (string)($_POST['password'] ?? '');
        $type = input('profile_type');

        if (input('website') !== '') { // honeypot: campo invisível para robôs
            redirect('/');
        }
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $errors[] = 'Informe seu nome (2 a 120 caracteres).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errors[] = 'E-mail inválido.';
        if (strlen($password) < 8) $errors[] = 'A senha deve ter pelo menos 8 caracteres.';
        if (!array_key_exists($type, profile_types())) $errors[] = 'Escolha como você se identifica.';
        if (!input('consent')) $errors[] = 'É preciso aceitar os termos e a política de privacidade.';
        if (!$errors && one('SELECT id FROM users WHERE email = ?', [$email])) {
            $errors[] = 'Já existe uma conta com este e-mail. Tente entrar ou recuperar a senha.';
        }
        if (!$errors && !rate_limit('register', client_ip(), 3, 86400)) {
            $errors[] = 'Limite de cadastros a partir desta conexão atingido. Tente amanhã.';
        }
        if (!$errors) {
            $id = insert('users', [
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
                'role' => 'user',
                'profile_type' => $type,
                'consent_at' => now(),
                'created_at' => now(),
            ]);
            $u = one('SELECT * FROM users WHERE id = ?', [$id]);
            send_verification_email($u);
            login_user($u);
            flash('ok', 'Conta criada! Enviamos um link de confirmação para ' . $email . '.');
            redirect('/conta');
        }
        $GLOBALS['URBS_OLD'] = ['name' => $name, 'email' => $email, 'profile_type' => $type];
    }
    render('auth/register', ['title' => 'Criar conta', 'errors' => $errors]);
}

function auth_logout(): void
{
    logout_user();
    redirect('/');
}

function auth_verify_email(): void
{
    $userId = consume_token(input('t'), 'verify_email');
    if (!$userId) {
        flash('warn', 'Link inválido ou expirado. Peça um novo na sua conta.');
        redirect(current_user() ? '/conta' : '/entrar');
    }
    update('users', ['email_verified_at' => now()], 'id = :id', ['id' => $userId]);
    flash('ok', 'E-mail confirmado! Você já pode publicar, apoiar e votar.');
    redirect(current_user() ? '/' : '/entrar');
}

function auth_forgot(): void
{
    $sent = false;
    if (is_post()) {
        $email = mb_strtolower(input('email'));
        if (rate_limit('forgot', client_ip(), 5, 3600)) {
            $u = one('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL AND blocked_at IS NULL', [$email]);
            if ($u) {
                $token = create_token((int)$u['id'], 'reset_password', 3600 * 2);
                send_mail($u['email'], 'Redefinir senha — ' . setting('site_name'),
                    "Olá, {$u['name']}!\n\nPara criar uma nova senha, acesse:\n\n" . absolute_url('/redefinir-senha', ['t' => $token])
                    . "\n\nO link vale por 2 horas. Se você não pediu, ignore esta mensagem.");
            }
        }
        $sent = true; // mesma resposta sempre, para não revelar quais e-mails existem
    }
    render('auth/forgot', ['title' => 'Recuperar senha', 'sent' => $sent]);
}

function auth_reset(): void
{
    $token = input('t');
    $error = null;
    if (is_post()) {
        $password = (string)($_POST['password'] ?? '');
        if (strlen($password) < 8) {
            $error = 'A senha deve ter pelo menos 8 caracteres.';
        } elseif ($userId = consume_token($token, 'reset_password')) {
            update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT), 'email_verified_at' => now()],
                'id = :id AND email_verified_at IS NULL', ['id' => $userId]);
            update('users', ['password_hash' => password_hash($password, PASSWORD_DEFAULT)], 'id = :id', ['id' => $userId]);
            flash('ok', 'Senha alterada. Entre com a nova senha.');
            redirect('/entrar');
        } else {
            $error = 'Link inválido ou expirado. Peça um novo.';
        }
    }
    render('auth/reset', ['title' => 'Nova senha', 'token' => $token, 'error' => $error]);
}
