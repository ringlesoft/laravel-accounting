<?php

namespace RingleSoft\LaravelAccounting\DTOs;

class VoucherData
{
    public function __construct(
        public array $entries,
        public \Illuminate\Support\Carbon $date,
        public string $description,
        public ?string $reference = null,
        public array $metadata = []
    ) {}
}
