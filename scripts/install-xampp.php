<?php
declare(strict_types=1);

/** Fresh local deployment. Only invoked explicitly by the two setup scripts. */
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

function writeChecked(string $path, string $content): void
{
    if (file_put_contents($path, $content) === false) throw new RuntimeException('Cannot write: ' . $path);
}

function copyTree(string $source, string $target, string $relative = ''): void
{
    if (!is_dir($target) && !mkdir($target, 0755, true)) throw new RuntimeException('Cannot create: ' . $target);
    foreach (new DirectoryIterator($source) as $entry) {
        if ($entry->isDot()) continue;
        $name = $entry->getFilename();
        $path = ltrim($relative . '/' . $name, '/');
        if (in_array($path, ['backend/config.local.php', 'backend/storage'], true)) continue;
        if ($entry->isLink()) throw new RuntimeException('Source symlinks are not supported: ' . $path);
        if ($entry->isDir()) copyTree($entry->getPathname(), $target . '/' . $name, $path);
        elseif (!copy($entry->getPathname(), $target . '/' . $name)) throw new RuntimeException('Cannot copy: ' . $path);
    }
}

function runChecked(array $command, string $log, ?string $input = null): void
{
    $process = proc_open($command, [0 => ['file', $input ?? (PHP_OS_FAMILY === 'Windows' ? 'NUL' : '/dev/null'), 'r'],
        1 => ['file', $log, 'a'], 2 => ['file', $log, 'a']], $pipes, null, null, ['bypass_shell' => true]);
    if (!is_resource($process) || proc_close($process) !== 0) throw new RuntimeException('Command failed; details: ' . $log);
}

$backup = null;
$dbReset = false;
$oldMoved = false;
$newMoved = false;
$configChanged = false;
try {
    $xampp = realpath($argv[1] ?? '');
    $port = filter_var($argv[2] ?? '8080', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
    if (!$xampp || !$port) throw new RuntimeException('Usage: php scripts/install-xampp.php XAMPP_PATH PORT');
    $xampp = str_replace('\\', '/', $xampp);
    $windows = PHP_OS_FAMILY === 'Windows';
    $apacheConfig = $xampp . ($windows ? '/apache/conf/httpd.conf' : '/etc/httpd.conf');
    $apache = $xampp . ($windows ? '/apache/bin/httpd.exe' : '/bin/httpd');
    $mysqlBin = $xampp . ($windows ? '/mysql/bin/mysql.exe' : '/bin/mysql');
    $dumpBin = $xampp . ($windows ? '/mysql/bin/mysqldump.exe' : '/bin/mysqldump');
    $source = dirname(__DIR__) . '/source';
    $htdocs = $xampp . '/htdocs';
    if (!is_file($apacheConfig) || !is_file($dumpBin) || !is_file($source . '/backend/vendor/autoload.php')) {
        throw new RuntimeException('XAMPP / Composer dependencies are missing.');
    }
    if (is_link($htdocs) || (file_exists($htdocs) && !is_dir($htdocs))) throw new RuntimeException('htdocs must be a real directory, not a symlink.');
    $values = require $source . '/backend/config.example.php';
    $existingConfig = $htdocs . '/wealthflow/backend/config.local.php';
    if (is_file($existingConfig)) $values = array_replace($values, require $existingConfig);
    elseif (is_file($source . '/backend/config.local.php')) $values = array_replace($values, require $source . '/backend/config.local.php');
    foreach (array_keys($values) as $key) {
        if (getenv($key) !== false) $values[$key] = getenv($key);
    }
    $values['APP_URL'] = 'http://localhost:' . $port;
    $values['APP_ENV'] = 'local';
    $values['APP_KEY'] = bin2hex(random_bytes(32));
    $name = $values['DB_NAME'];
    // Reset only a WealthFlow database. Never reset MySQL system / other project DBs.
    if (!preg_match('/^wealthflow(?:_[A-Za-z0-9_]+)?$/D', $name)) throw new RuntimeException('DB_NAME must be wealthflow or wealthflow_*.');
    if (!in_array($values['DB_HOST'], ['localhost', '127.0.0.1', '::1'], true)) throw new RuntimeException('Fresh XAMPP installer only resets a local database.');
    $backupRoot = $xampp . '/wealthflow-backups';
    if (is_link($backupRoot)) throw new RuntimeException('Backup directory must not be a symlink.');
    $backup = $backupRoot . '/' . date('Ymd-His') . '-' . bin2hex(random_bytes(3));
    if (!mkdir($backup, 0700, true)) throw new RuntimeException('Cannot create backup directory.');
    chmod($backupRoot, 0700);
    $stage = $backup . '/new-htdocs/wealthflow';
    copyTree($source, $stage);
    mkdir($stage . '/backend/storage', 0770, true);
    writeChecked($stage . '/backend/config.local.php', "<?php\nreturn " . var_export($values, true) . ";\n");
    chmod($stage . '/backend/config.local.php', 0640);
    // Keep this process and the child migration on exactly the same configuration.
    foreach ($values as $key => $value) putenv($key . '=' . $value);
    $config = require $stage . '/backend/bootstrap.php';
    $config->validate();
    $db = WealthFlow\Database::connect($config, false);
    $exists = $db->prepare('SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ?');
    $exists->execute([$name]);
    $hadDatabase = (bool) $exists->fetchColumn();
    $credentials = $backup . '/mysql-client.cnf';
    $escape = static fn(string $s): string => '"' . strtr($s, ["\\" => "\\\\", '"' => '\\"', "\n" => '\\n', "\r" => '\\r']) . '"';
    writeChecked($credentials, "[client]\n" . 'host=' . $escape($values['DB_HOST']) . "\nport=" . $values['DB_PORT']
        . "\nuser=" . $escape($values['DB_USER']) . "\npassword=" . $escape($values['DB_PASSWORD']) . "\n");
    chmod($credentials, 0600);
    if ($hadDatabase) {
        runChecked([$dumpBin, '--defaults-extra-file=' . $credentials, '--protocol=tcp', '--single-transaction', '--routines', '--triggers',
            '--databases', $name, '--result-file=' . $backup . '/database.sql'], $backup . '/install.log');
        if (filesize($backup . '/database.sql') === 0) throw new RuntimeException('Database backup is empty.');
    }
    $originalApache = file_get_contents($apacheConfig);
    writeChecked($backup . '/httpd.conf', $originalApache);
    $newRoot = $htdocs . '/wealthflow';
    if (str_contains($newRoot, '"') || str_contains($newRoot, "\n")) throw new RuntimeException('Unsupported XAMPP path.');
    $updatedApache = preg_replace('/^DocumentRoot\s+"[^"]+"\s*$/m', 'DocumentRoot "' . $newRoot . '"', $originalApache, -1, $rootCount);
    $updatedApache = preg_replace('/^Listen\s+(?:[0-9.]+:)?\d+\s*$/m', 'Listen 127.0.0.1:' . $port, $updatedApache, -1, $listenCount);
    if ($rootCount !== 1 || $listenCount !== 1) throw new RuntimeException('Expected one main DocumentRoot and Listen; configure this custom Apache installation manually.');
    $updatedApache = preg_replace('/^#\s*(LoadModule\s+rewrite_module\s+.+)$/m', '$1', $updatedApache);
    if (!preg_match('/^\s*LoadModule\s+rewrite_module\b/m', $updatedApache)) {
        throw new RuntimeException('Apache mod_rewrite is not enabled in httpd.conf.');
    }
    // Replace only our managed block on subsequent runs.
    $updatedApache = preg_replace('/\n# BEGIN WEALTHFLOW\n.*?# END WEALTHFLOW\n/s', "\n", $updatedApache);
    $updatedApache .= "\n# BEGIN WEALTHFLOW\n<Directory \"{$newRoot}\">\n"
        . "    Options -Indexes +FollowSymLinks\n"
        . "    AllowOverride None\n"
        . "    Require all granted\n"
        . "    RewriteEngine On\n"
        . "    RewriteRule ^(?:backend|database)(?:/|$) - [F,END,NC]\n"
        . "    RewriteRule (^|/)\\. - [F,END]\n"
        . "    RewriteRule ^api/(.*)$ backend/public/index.php?route=$1 [END,QSA]\n"
        . "    RewriteRule ^app\\.html$ backend/app.php [END]\n"
        . "    RewriteRule ^(?:index\\.html|app\\.php)$ - [END]\n"
        . "    RewriteRule ^frontend/(?:css|js|assets)/[A-Za-z0-9_./-]+\\.(?:css|js|svg|png|jpe?g)$ - [END]\n"
        . "    RewriteRule ^$ - [END]\n"
        . "    RewriteRule . - [F,END]\n"
        . "</Directory>\n# END WEALTHFLOW\n";
    echo "Fresh install: replacing ALL of {$htdocs}; resetting database {$name}.\nBackup: {$backup}\n";
    // Database backup is complete before any reset; source is fully staged first.
    $db->exec("DROP DATABASE IF EXISTS `{$name}`");
    $dbReset = true;
    runChecked([PHP_BINARY, $stage . '/backend/bin/setup.php'], $backup . '/install.log');
    if (is_dir($htdocs)) {
        if (!rename($htdocs, $backup . '/htdocs')) throw new RuntimeException('Cannot move old htdocs. Close applications holding files and retry.');
        $oldMoved = true;
    }
    if (!rename($backup . '/new-htdocs', $htdocs)) throw new RuntimeException('Cannot activate new htdocs.');
    $newMoved = true;
    $configChanged = true;
    writeChecked($apacheConfig, $updatedApache);
    runChecked([$apache, '-t', '-f', $apacheConfig], $backup . '/install.log');
    unlink($credentials);
    echo "Installed: {$newRoot}\nWebsite: {$values['APP_URL']}/\nSMTP config: {$newRoot}/backend/config.local.php\n";
    echo "Old htdocs and database are recoverable in: {$backup}\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'Installation failed: ' . $error->getMessage() . PHP_EOL);
    try {
        if ($configChanged) writeChecked($apacheConfig, $originalApache);
        if ($newMoved && !rename($htdocs, $backup . '/failed-new-htdocs')) throw new RuntimeException('Cannot move failed deployment.');
        if ($oldMoved && !rename($backup . '/htdocs', $htdocs)) throw new RuntimeException('Cannot restore htdocs.');
        if ($dbReset) {
            $db->exec("DROP DATABASE IF EXISTS `{$name}`");
            if ($hadDatabase) runChecked([$mysqlBin, '--defaults-extra-file=' . $credentials, '--protocol=tcp', '--binary-mode'], $backup . '/restore.log', $backup . '/database.sql');
        }
        if (isset($credentials) && is_file($credentials)) unlink($credentials);
        fwrite(STDERR, "Previous installation restored where applicable.\n");
    } catch (Throwable $restoreError) {
        fwrite(STDERR, 'Automatic restore failed; recover from backup: ' . $backup . PHP_EOL);
    }
    if ($backup) fwrite(STDERR, 'Backup / diagnostics: ' . $backup . PHP_EOL);
    exit(1);
}
