<?php

namespace RingleSoft\LaravelAccounting\Traits;

trait Postable
{

    /**
     * @return bool
     */
    public function createJournal(): bool
    {
        // Todo Implement
        return true;
    }

    /**
     * @return bool
     */
    public function postJournal(): bool
    {
        // Todo Implement
        return true;
    }

    /**
     * @return bool
     */
    public function post(): bool
    {
        return $this->createJournal() && $this->postJournal();
    }
}
