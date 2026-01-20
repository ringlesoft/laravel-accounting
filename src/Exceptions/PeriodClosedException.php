<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class PeriodClosedException extends Exception
{
    protected $message = 'The fiscal period is closed and cannot accept new transactions.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
