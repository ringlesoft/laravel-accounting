<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class AccountInactiveException extends Exception
{
    protected $message = 'The account is inactive and cannot be used in transactions.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
