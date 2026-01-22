<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class PeriodLockedException extends LaravelAccountingException
{
    protected $message = 'The fiscal period is locked and cannot be modified.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
