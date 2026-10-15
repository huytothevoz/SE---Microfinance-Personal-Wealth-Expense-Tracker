<?php
declare(strict_types=1);

namespace WealthFlow;

final class Config
{
    private array $values;

    public function __construct(public readonly string $directory)
    {
        $this->values = require $directory . '/config.example.php';
        if (is_file($directory . '/config.local.php')) {
            $this->values = array_replace($this->values, require $directory . '/config.local.php');
        }
    }

    public function get(string $key): string
    {
        $value = getenv($key);
        return $value === false ? (string) ($this->values[$key] ?? '') : $value;
    }

    public function secureCookies(): bool
    {
        return str_starts_with($this->get('APP_URL'), 'https://');
    }

    public function validate(): void
    {
        if (strlen($this->get('APP_KEY')) < 32) {
            throw new \RuntimeException('APP_KEY must contain at least 32 bytes. Run setup.');
        }
        $url = parse_url($this->get('APP_URL'));
        if (!$url || !isset($url['host'], $url['scheme']) || !in_array($url['scheme'], ['http', 'https'], true)) {
            throw new \RuntimeException('Invalid APP_URL.');
        }
        if (!$this->secureCookies() && ($this->get('APP_ENV') !== 'local' || !in_array($url['host'], ['localhost', '127.0.0.1', '[::1]'], true))) {
            throw new \RuntimeException('HTTPS is required outside local development.');
        }
    }
}
