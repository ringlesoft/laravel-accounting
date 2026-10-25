<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class InvalidPresetDefinitionException extends LaravelAccountingException
{
    protected $message = 'Invalid custom account preset definition.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
