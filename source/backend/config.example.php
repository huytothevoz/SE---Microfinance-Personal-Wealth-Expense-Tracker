<?php
// Installer creates config.local.php; edit that file, never commit credentials.
return [
    'APP_URL' => 'http://localhost/wealthflow',
    'APP_ENV' => 'local',
    'APP_KEY' => '', // Installer generates a random 256-bit secret.
    'DB_HOST' => '127.0.0.1',
    'DB_PORT' => '3307',
    'DB_NAME' => 'wealthflow',
    'DB_USER' => 'root',
    'DB_PASSWORD' => '',
    // HTTPS deployments MUST set APP_ENV=production and APP_URL=https://...
    'MAIL_TRANSPORT' => 'smtp', // smtp, or log for local preview only
    'MAIL_HOST' => 'smtp.gmail.com',
    'MAIL_PORT' => '587',
    'MAIL_ENCRYPTION' => 'tls', // tls (STARTTLS), ssl (465), none (local SMTP test only)
    'MAIL_USERNAME' => 'wealthflow.voz@gmail.com',
    'MAIL_PASSWORD' => '', // SMTP password / Gmail App Password
    'MAIL_FROM_ADDRESS' => 'wealthflow.voz@gmail.com',
    'MAIL_FROM_NAME' => 'WealthFlow',
];
