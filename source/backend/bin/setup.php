<?php
declare(strict_types=1);

// CLI only. Never delete application data; SQL migrations are recorded after success.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
$directory = dirname(__DIR__);
try {
    if (PHP_VERSION_ID < 80200) throw new RuntimeException('PHP 8.2+ is required. Use XAMPP PHP.');
    foreach (['pdo_mysql', 'mbstring', 'openssl'] as $extension) {
        if (!extension_loaded($extension)) throw new RuntimeException("Missing PHP extension: {$extension}");
    }
    $local = $directory . '/config.local.php';
    if (!is_file($local) && !in_array('--no-config', $argv, true)) {
        $values = require $directory . '/config.example.php';
        foreach (array_keys($values) as $key) {
            if (getenv($key) !== false) $values[$key] = getenv($key);
        }
        if ($values['APP_KEY'] === '') $values['APP_KEY'] = bin2hex(random_bytes(32));
        $handle = fopen($local, 'x');
        if (!$handle) throw new RuntimeException('Cannot create config.local.php.');
        fwrite($handle, "<?php\nreturn " . var_export($values, true) . ";\n");
        fclose($handle);
        chmod($local, 0640);
        echo "Created backend/config.local.php. Configure SMTP there.\n";
    }
    $config = require $directory . '/bootstrap.php';
    $config->validate();
    $db = WealthFlow\Database::connect($config, false);
    $name = $config->get('DB_NAME');
    $db->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $db->exec("USE `{$name}`");
    // Serialize installers. MariaDB/MySQL DDL implicitly commits, so each file is rerunnable.
    if ((int) $db->query("SELECT GET_LOCK('wealthflow_setup_{$name}', 20)")->fetchColumn() !== 1) {
        throw new RuntimeException('Another installer is running.');
    }
    $db->exec('CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(100) PRIMARY KEY, applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB');
    foreach (glob(dirname($directory) . '/database/[0-9]*.sql') as $file) {
        $version = basename($file);
        $check = $db->prepare('SELECT version FROM schema_migrations WHERE version = ?');
        $check->execute([$version]);
        if ($check->fetch()) { echo "Already applied: {$version}\n"; continue; }
        $sql = file_get_contents($file);
        // The legacy draft contains a hard-coded CREATE/USE wealthflow.
        $sql = preg_replace('/^(?:CREATE DATABASE[^;]*;|USE\s+wealthflow;)\s*$/mi', '', $sql);
        $db->exec($sql);
        $db->prepare('INSERT INTO schema_migrations (version) VALUES (?)')->execute([$version]);
        echo "Applied: {$version}\n";
    }
    $db->query("SELECT RELEASE_LOCK('wealthflow_setup_{$name}')");
    echo 'Ready: ' . rtrim($config->get('APP_URL'), '/') . "/\n";
} catch (Throwable $error) {
    // Do not print connection strings or credentials.
    fwrite(STDERR, 'Setup failed (' . get_class($error) . '). Check PHP 8.2+, Composer, MySQL, DB settings and permissions in config.local.php.' . PHP_EOL);
    exit(1);
}
