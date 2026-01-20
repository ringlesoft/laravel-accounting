<?php

namespace RingleSoft\LaravelAccounting\Traits;

trait Postable
{
    public function createJournal(): bool
    {
        // Todo Implement
        return true;
    }

    public function postJournal(): bool
    {
        // Todo Implement
        return true;
    }

    public function post(): bool
    {
        return $this->createJournal() && $this->postJournal();
    }

    public function isPosted(): bool
    {
        return $this->journalEntry?->is_posted ?? false;
    }

    public function getReference(): ?string
    {
       return null;
    }
}
