<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class JournalEntryVoided
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public JournalEntry $journalEntry,
        public ?string $reason = null,
        public ?int $userId = null
    ) {
    }
}
