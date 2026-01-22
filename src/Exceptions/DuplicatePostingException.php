<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class DuplicatePostingException extends LaravelAccountingException
{
    protected $message = 'This transaction has already been posted.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
