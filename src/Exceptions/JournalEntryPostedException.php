<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class JournalEntryPostedException extends LaravelAccountingException
{
    public function __construct(string $message = 'Journal entry already posted', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
