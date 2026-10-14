<?php

namespace RingleSoft\LaravelAccounting\Exceptions;

use Exception;

class TenantRequiredException extends LaravelAccountingException
{
    protected $message = 'This record requires a resolvable tenant_id. Authenticate as a user with a tenant, set tenant_id explicitly, or set strict_multi_tenancy_check to false to allow global/shared records.';

    public function __construct(string $message = '', int $code = 0, ?Exception $previous = null)
    {
        if ($message) {
            $this->message = $message;
        }

        parent::__construct($this->message, $code, $previous);
    }
}
