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
}
