<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class JournalEntryAlreadyExistsException extends LaravelAccountingException
{
    protected $message = 'A journal entry for this model already exists.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
