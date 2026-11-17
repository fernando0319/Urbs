<?php
// Exemplo de configuração. O instalador (install.php) gera o config.php automaticamente.
// Para configurar à mão: copie para config.php e ajuste.
return [
    'base_url' => 'https://participa.suacidade.org.br', // sem barra no final; inclua a subpasta, se houver
    'pretty_urls' => true,          // false se o servidor não tiver mod_rewrite (URLs viram index.php?r=/...)
    'debug' => false,               // true só em desenvolvimento
    'timezone' => 'America/Sao_Paulo',
    'app_key' => 'troque-por-uma-sequencia-aleatoria-longa',
    'source_url' => '',             // link do repositório, exibido no rodapé

    'db' => [
        'driver' => 'mysql',        // 'mysql' ou 'sqlite'
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'urbs',
        'user' => 'urbs',
        'pass' => '',
        // 'path' => __DIR__ . '/storage/urbs.sqlite', // para sqlite
    ],

    'mail' => [
        'driver' => 'mail',         // 'mail' (função do PHP), 'smtp' ou 'log' (grava em storage/logs/mail.log)
        'from' => 'nao-responda@suacidade.org.br',
        'from_name' => 'Urbs',
        'host' => 'smtp.exemplo.com',
        'port' => 587,
        'secure' => 'tls',          // 'tls' (STARTTLS), 'ssl' ou ''
        'user' => '',
        'pass' => '',
    ],

    'uploads' => [
        'max_image_mb' => 10,
        'max_pdf_mb' => 20,
    ],
];
