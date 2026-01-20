<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class AccountNotPostableException extends Exception
{
    protected $message = 'This account does not allow manual entries.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
