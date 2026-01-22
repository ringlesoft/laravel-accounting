<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class InsufficientPermissionException extends LaravelAccountingException
{
    protected $message = 'Insufficient permissions to perform this action.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
