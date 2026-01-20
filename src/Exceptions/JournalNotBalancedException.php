<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class JournalNotBalancedException extends Exception
{
    protected $message = 'Journal entry is not balanced. Debits must equal credits.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
