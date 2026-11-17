<?php
declare(strict_types=1);

/**
 * Envio de e-mail sem dependências.
 * Drivers: "log" (grava em storage/logs/mail.log, para desenvolvimento),
 * "mail" (função mail() do PHP, comum em hospedagem compartilhada) e "smtp".
 */
function send_mail(string $to, string $subject, string $body): bool
{
    $cfg = config('mail', []);
    $driver = $cfg['driver'] ?? 'log';
    $from = $cfg['from'] ?? ('no-reply@' . (parse_url((string)config('base_url'), PHP_URL_HOST) ?: 'localhost'));
    $fromName = $cfg['from_name'] ?? setting('site_name', 'Urbs');

    try {
        if ($driver === 'log') {
            $line = sprintf("[%s] Para: %s\nAssunto: %s\n\n%s\n%s\n", date('c'), $to, $subject, $body, str_repeat('-', 60));
            file_put_contents(ROOT . '/storage/logs/mail.log', $line, FILE_APPEND | LOCK_EX);
            return true;
        }
        $encSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encFrom = '=?UTF-8?B?' . base64_encode($fromName) . '?= <' . $from . '>';
        if ($driver === 'mail') {
            $headers = "From: $encFrom\r\nMIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit";
            return mail($to, $encSubject, $body, $headers);
        }
        return smtp_send($cfg, $from, $encFrom, $to, $encSubject, $body);
    } catch (Throwable $e) {
        error_log('Falha ao enviar e-mail: ' . $e->getMessage());
        return false;
    }
}

function smtp_send(array $cfg, string $from, string $encFrom, string $to, string $encSubject, string $body): bool
{
    $host = $cfg['host'] ?? 'localhost';
    $port = (int)($cfg['port'] ?? 587);
    $secure = $cfg['secure'] ?? 'tls'; // tls (STARTTLS), ssl ou ''
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = stream_socket_client($remote, $errno, $errstr, 15);
    if (!$fp) {
        throw new RuntimeException("SMTP: $errstr ($errno)");
    }
    stream_set_timeout($fp, 15);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $c, array $ok) use ($fp, $read): string {
        if ($c !== '') {
            fwrite($fp, $c . "\r\n");
        }
        $r = $read();
        if (!in_array((int)substr($r, 0, 3), $ok, true)) {
            throw new RuntimeException('SMTP inesperado: ' . trim($r));
        }
        return $r;
    };
    $ehloHost = parse_url((string)config('base_url'), PHP_URL_HOST) ?: 'localhost';
    $cmd('', [220]);
    $cmd("EHLO $ehloHost", [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            throw new RuntimeException('SMTP: falha no STARTTLS');
        }
        $cmd("EHLO $ehloHost", [250]);
    }
    if (!empty($cfg['user'])) {
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode($cfg['user']), [334]);
        $cmd(base64_encode($cfg['pass'] ?? ''), [235]);
    }
    $cmd("MAIL FROM:<$from>", [250]);
    $cmd("RCPT TO:<$to>", [250, 251]);
    $cmd('DATA', [354]);
    $headers = [
        'Date: ' . date('r'),
        "From: $encFrom",
        "To: <$to>",
        "Subject: $encSubject",
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $ehloHost . '>',
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
    ];
    $msg = implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body));
    $cmd($msg . "\r\n.", [250]);
    $cmd('QUIT', [221]);
    fclose($fp);
    return true;
}
