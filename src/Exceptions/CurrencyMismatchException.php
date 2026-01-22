<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class CurrencyMismatchException extends LaravelAccountingException
{
    protected $message = 'Currency mismatch detected in transaction.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
