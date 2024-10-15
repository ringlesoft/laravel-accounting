<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum PaymentMethods: string
{
    case CASH = 'Cash';
    case BANK_TRANSFER = 'Bank Transfer';
    case CREDIT_CARD = 'Credit Card';
    case CHECK = 'Check';
    case MOBILE = 'Mobile';

}
