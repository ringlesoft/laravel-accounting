<?php

namespace RingleSoft\LaravelAccounting\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RingleSoft\LaravelAccounting\Models\JournalEntry;

class JournalEntryCreated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public JournalEntry $journalEntry,
        public ?int $userId = null
    ) {
    }
}
