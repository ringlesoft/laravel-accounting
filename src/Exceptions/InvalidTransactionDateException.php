<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class InvalidTransactionDateException extends Exception
{
    protected $message = 'Invalid transaction date. Date must fall within an open fiscal period.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
