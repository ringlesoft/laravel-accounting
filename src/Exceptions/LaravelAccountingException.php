<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;
use Throwable;

class LaravelAccountingException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function from(Throwable $e, ?string $message): LaravelAccountingException
    {
        return new self(($message ?? $e->getMessage() ?? 'Unknown Exception'), $e->getCode(), $e);
    }
}
