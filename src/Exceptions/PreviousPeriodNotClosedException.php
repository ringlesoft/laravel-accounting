<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class PreviousPeriodNotClosedException extends Exception
{
    protected $message = 'Previous period must be closed.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
