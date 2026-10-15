<?php
declare(strict_types=1);

namespace WealthFlow\Http;

final class ApiException extends \RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status = 400, public readonly array $fields = [])
    {
        parent::__construct($message);
    }
}
