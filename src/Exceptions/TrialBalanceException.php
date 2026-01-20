<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class TrialBalanceException extends Exception
{
    protected $message = 'Trial balance does not balance. Total debits must equal total credits.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
