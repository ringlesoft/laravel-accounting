<?php

namespace RingleSoft\LaravelAccounting\Models;

use RingleSoft\LaravelAccounting\Traits\HasTablePrefix;
use RingleSoft\LaravelAccounting\Traits\MultiTenant;

class JournalEntry
{
    use HasTablePrefix, MultiTenant;
}
