<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class AccountNotFoundException extends Exception
{
    protected $message = 'Account not found.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
