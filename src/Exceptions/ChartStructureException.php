<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class ChartStructureException extends LaravelAccountingException
{
    protected $message = 'This change would break the chart of accounts structure.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
