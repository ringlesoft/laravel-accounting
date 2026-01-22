<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class InvalidAccountTypeException extends LaravelAccountingException
{
    protected $message = 'Invalid account type specified.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
