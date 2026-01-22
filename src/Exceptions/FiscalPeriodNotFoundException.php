<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class FiscalPeriodNotFoundException extends LaravelAccountingException
{
    protected $message = 'There should be a fiscal period .';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
