<?php

declare(strict_types=1);

namespace WealthFlow\Http;

final class Request
{
    private string $method;
    private string $path;
    /** @var array<string, mixed> */
    private array $queryParams;
    /** @var array<string, mixed> */
    private array $body;
    /** @var array<string, string> */
    private array $headers;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $rawPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';
        $this->path = rawurldecode($rawPath);
        $this->queryParams = $_GET;
        $this->headers = $this->collectHeaders();
        $this->body = $this->parseBody();
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    /** @return array<string, mixed> */
    public function getQueryParams(): array
    {
        return $this->queryParams;
    }

    public function getQuery(string $key, mixed $default = null): mixed
    {
        return $this->queryParams[$key] ?? $default;
    }

    /** @return array<string, mixed> */
    public function getBody(): array
    {
        return $this->body;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->body[$key] ?? $this->queryParams[$key] ?? $default;
    }

    public function getHeader(string $name, ?string $default = null): ?string
    {
        $normalized = strtolower(str_replace('-', '_', $name));
        return $this->headers[$normalized] ?? $default;
    }

    public function getIdempotencyKey(): ?string
    {
        return $this->getHeader('Idempotency-Key') ?: ($this->body['idempotencyKey'] ?? null);
    }

    /** @return array<string, string> */
    private function collectHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $val) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(substr($key, 5));
                $headers[$name] = (string) $val;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true)) {
                $headers[strtolower($key)] = (string) $val;
            }
        }
        return $headers;
    }

    /** @return array<string, mixed> */
    private function parseBody(): array
    {
        if (in_array($this->method, ['POST', 'PUT', 'PATCH'], true)) {
            $input = file_get_contents('php://input', false, null, 0, 16385);
            if ($input !== false && strlen($input) > 16384) {
                throw new ApiException('BODY_TOO_LARGE', 'Dữ liệu quá lớn.', 413);
            }
            if ($input !== false && trim($input) !== '') {
                try {
                    $json = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                    if (is_array($json)) {
                        return $json;
                    }
                } catch (\JsonException) {
                    throw new ApiException('INVALID_JSON', 'Dữ liệu JSON không hợp lệ.', 400);
                }
            }
            return $_POST;
        }
        return [];
    }
}
