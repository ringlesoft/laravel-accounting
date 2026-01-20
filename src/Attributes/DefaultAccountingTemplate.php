<?php

namespace RingleSoft\LaravelAccounting\Attributes;

use Attribute;
use Exception;
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;

#[Attribute(Attribute::TARGET_CLASS)]
class DefaultAccountingTemplate
{
    public function __construct(
        readonly public string $accountingTemplate,
    )
    {
    }

    /**
     * @throws Exception
     */
    public function getTemplate(): AccountingTemplate
    {
        $templateClass = $this->accountingTemplate;
        // check if is proper class and exists
        if (!class_exists($templateClass)) {
            throw new Exception("Template class {$templateClass} does not exist");
        }
        if (!is_subclass_of($templateClass, AccountingTemplate::class)) {
            throw new Exception("Template class {$templateClass} is not a subclass of AccountingTemplate");
        }
        return new $templateClass();
    }
}
