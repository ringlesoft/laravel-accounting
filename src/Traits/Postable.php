<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use ReflectionClass;
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\Contracts\PostableTemplate;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;

/**
 * @mixin Builder
 * @mixin Model
 * @mixin PostableModel
 *
 * @phpstan-require-implements PostableModel
 */
trait Postable
{
    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'postable');
    }

    public function createJournal(): bool
    {
        $voucherData = $this->toVoucher();
        LaravelAccounting::journals()->create($voucherData);

        return true;
    }

    public function postJournal(): bool
    {
        if ($journal = $this->journalEntry()) {
            return $journal->post();
        }

        return true;
    }

    public function post(): bool
    {
        return $this->createJournal() && $this->postJournal();
    }

    public function postWithTemplate(PostableTemplate $template): bool
    {
        // TODO: implement
        $this->template = $template;

        return $this->post();
    }

    public function isPosted(): bool
    {
        return $this->journalEntry?->is_posted ?? false;
    }

    public function getReference(): ?string
    {
        return null;
    }

    public function getTemplate(): ?AccountingTemplate
    {
        $reflector = new ReflectionClass($this);
        $attributes = $reflector->getAttributes(DefaultAccountingTemplate::class);
        if (count($attributes) > 0) {
            return $attributes[0]->newInstance()->getTemplate();
        }

        return null;
    }

    public function toVoucher(): VoucherData
    {
        $template = $this->getTemplate();
        if (! $template) {
            throw new \Exception('No template found for '.get_class($this));
        }

        return $template->make($this);
    }
}
