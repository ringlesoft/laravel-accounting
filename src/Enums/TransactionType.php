<?php

namespace RingleSoft\LaravelAccounting\Enums;

enum TransactionType: string
{
    case SALE = 'Sale';
    case PURCHASE = 'Purchase';
    case TRANSFER = 'Transfer';
    case ADJUSTMENT = 'Adjustment';
}
