<?php

/**
 * Example configuration file for LibreMailApi with SMTP support
 * Copy this file to config.php and modify the settings according to your needs
 */

return [
    'api' => [
        'base_url' => getenv('LIBREMAIL_API_BASE_URL') ?: 'http://localhost:8080',
        'version' => getenv('LIBREMAIL_API_VERSION') ?: 'v3',
        'default_domain' => getenv('LIBREMAIL_DEFAULT_DOMAIN'),
        'auth' => [
            'username' => getenv('LIBREMAIL_API_USER'),
            'password' => getenv('LIBREMAIL_API_KEY'),
        ],
    ],
    'smtp' => [
        'enabled'   => filter_var(getenv('LIBREMAIL_SMTP_ENABLED') ?: 'false', FILTER_VALIDATE_BOOL),
        'host'      => getenv('LIBREMAIL_SMTP_HOST') ?: 'smtp.gmail.com',
        'port'      => (int) (getenv('LIBREMAIL_SMTP_PORT') ?: 587),
        'encryption'=> getenv('LIBREMAIL_SMTP_ENCRYPTION') ?: 'tls',
        'auth'      => filter_var(getenv('LIBREMAIL_SMTP_AUTH') ?: 'true', FILTER_VALIDATE_BOOL),
        'username'  => getenv('LIBREMAIL_SMTP_USERNAME') ?: 'your-email@gmail.com',
        'password'  => getenv('LIBREMAIL_SMTP_PASSWORD') ?: 'your-app-password',
        'from_name' => getenv('LIBREMAIL_FROM_NAME') ?: 'LibreMailApi',
        'from_email'=> getenv('LIBREMAIL_FROM_EMAIL') ?: 'your-email@gmail.com',
        'timeout'   => (int) (getenv('LIBREMAIL_SMTP_TIMEOUT') ?: 30),
        'debug'     => filter_var(getenv('LIBREMAIL_SMTP_DEBUG') ?: 'false', FILTER_VALIDATE_BOOL),
        'verify_peer'      => filter_var(getenv('LIBREMAIL_SMTP_VERIFY_PEER') ?: 'true', FILTER_VALIDATE_BOOL),
        'allow_self_signed'=> filter_var(getenv('LIBREMAIL_SMTP_ALLOW_SELF_SIGNED') ?: 'false', FILTER_VALIDATE_BOOL),
    ],
    'storage' => [
        'path' => __DIR__ . '/../storage',
        'retention_days' => 30
    ],
    'limits' => [
        'max_recipients' => 1000,
        'max_attachment_size' => 25 * 1024 * 1024, // 25MB
        'max_message_size' => 25 * 1024 * 1024
    ],
    'features' => [
        'tracking' => true,
        'dkim' => true,
        'testmode' => true,
        'templates' => true
    ],
    'logging' => [
        'enabled' => true,
        'level' => 'info',
        'file' => __DIR__ . '/../logs/libre-mail-api.log'
    ],
];

>