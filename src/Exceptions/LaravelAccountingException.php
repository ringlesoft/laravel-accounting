<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class LaravelAccountingException extends Exception
{
    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function from(Exception $e, ?string $message): LaravelAccountingException
    {
        return new self(($message ?? $e->getMessage() ?? 'Unknown Exception'), $e->getCode(), $e);
    }
}
