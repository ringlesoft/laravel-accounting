<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class JournalEntryReversed
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public JournalEntry $originalEntry,
        public JournalEntry $reversalEntry,
        public ?string $reason = null,
        public ?int $userId = null
    ) {}
}
