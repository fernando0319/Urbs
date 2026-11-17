<?php
declare(strict_types=1);

/**
 * Acesso ao banco via PDO. Suporta MySQL/MariaDB e SQLite.
 */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $c = config('db');
    $opts = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $path = $c['path'] ?? (ROOT . '/storage/urbs.sqlite');
        $pdo = new PDO('sqlite:' . $path, null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA journal_mode = WAL');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $c['host'] ?? 'localhost',
            (int)($c['port'] ?? 3306),
            $c['name'] ?? ''
        );
        $pdo = new PDO($dsn, $c['user'] ?? '', $c['pass'] ?? '', $opts);
        $pdo->exec("SET time_zone = '+00:00'");
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function val(string $sql, array $params = []): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function insert(string $table, array $data): int
{
    $cols = array_keys($data);
    $sql = sprintf(
        'INSERT INTO %s (%s) VALUES (%s)',
        $table,
        implode(', ', $cols),
        implode(', ', array_map(fn($c) => ':' . $c, $cols))
    );
    q($sql, $data);
    return (int)db()->lastInsertId();
}

function update(string $table, array $data, string $where, array $whereParams = []): int
{
    $sets = [];
    $params = [];
    foreach ($data as $k => $v) {
        $sets[] = "$k = :set_$k";
        $params["set_$k"] = $v;
    }
    $sql = sprintf('UPDATE %s SET %s WHERE %s', $table, implode(', ', $sets), $where);
    return q($sql, array_merge($params, $whereParams))->rowCount();
}

/** Data/hora atual em UTC no formato do banco. */
function now(int $offsetSeconds = 0): string
{
    return gmdate('Y-m-d H:i:s', time() + $offsetSeconds);
}

/**
 * Converte o esquema MySQL em comandos para o driver informado.
 * Para SQLite: ajusta AUTO_INCREMENT, remove ENGINE e transforma INDEX em CREATE INDEX.
 */
function db_schema_statements(string $driver): array
{
    $sql = file_get_contents(ROOT . '/app/schema/mysql.sql');
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $parts = array_filter(array_map('trim', explode(';', $sql)));
    if ($driver !== 'sqlite') {
        return array_values($parts);
    }
    $out = [];
    foreach ($parts as $stmt) {
        $stmt = preg_replace('/\)\s*ENGINE=.*$/s', ')', $stmt);
        $stmt = str_replace('INT UNSIGNED AUTO_INCREMENT PRIMARY KEY', 'INTEGER PRIMARY KEY AUTOINCREMENT', $stmt);
        $stmt = str_replace(' UNSIGNED', '', $stmt);
        preg_match('/CREATE TABLE IF NOT EXISTS (\w+)/', $stmt, $m);
        $table = $m[1] ?? '';
        $indexes = [];
        $stmt = preg_replace_callback('/,\s*INDEX (\w+) \(([^)]+)\)/', function ($mm) use (&$indexes, $table) {
            $indexes[] = "CREATE INDEX IF NOT EXISTS {$mm[1]} ON {$table} ({$mm[2]})";
            return '';
        }, $stmt);
        $out[] = $stmt;
        array_push($out, ...$indexes);
    }
    return $out;
}

/** Termo de busca para LIKE ? ESCAPE '!' (portável entre MySQL e SQLite). */
function like_term(string $term): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
}
