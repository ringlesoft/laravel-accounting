<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalEntryAlreadyExistsException;
use RingleSoft\LaravelAccounting\Exceptions\LaravelAccountingException;
use RingleSoft\LaravelAccounting\Exceptions\TemplateNotFoundException;
use RingleSoft\LaravelAccounting\Facades\LaravelAccounting;
use RingleSoft\LaravelAccounting\Models\JournalEntry;
use RingleSoft\LaravelAccounting\Templates\AccountingTemplate;
use Throwable;

/**
 * @mixin Builder
 * @mixin Model
 * @mixin PostableModel
 * @property JournalEntry $journalEntry
 *
 * @phpstan-require-implements PostableModel
 */
trait Postable
{
    private ?AccountingTemplate $template = null;

    public function journalEntry(): MorphOne
    {
        return $this->morphOne(JournalEntry::class, 'postable');
    }

    /**
     * @throws Throwable
     * @throws InvalidVoucherException
     * @throws TemplateNotFoundException
     * @throws AccountNotFoundException
     */
    public function createJournal(): bool
    {
        if ($this->hasJournalEntry()) {
            throw new JournalEntryAlreadyExistsException();
        }
        $voucherData = $this->toVoucher();
        LaravelAccounting::journals()->createDraft($voucherData);
        $this->load('journalEntry');
        return true;
    }

    public function postJournal(): bool
    {
        if ($journal = $this->journalEntry) {
            return $journal->post();
        }
        return false;
    }

    /**
     * @throws LaravelAccountingException
     */
    public function post(): bool
    {
        try {
            return DB::transaction(function () {
                return $this->createJournal() && $this->postJournal();
            });
        } catch (Throwable $e) {
            throw LaravelAccountingException::from($e, "Failed to post to journal. " . $e->getMessage());
        }
    }

    public function postWithTemplate(AccountingTemplate $template): bool
    {
        $this->template = $template;
        return $this->post();
    }

    /**
     * @return bool
     */
    public function hasJournalEntry(): bool
    {
        return $this->journalEntry()->exists();
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
        // Firstly, check if template is assigned
        if (!empty($this->template)) {
            return $this->template;
        }

        // Check if template is assigned by attribute
        $reflector = new ReflectionClass($this);
        $attributes = $reflector->getAttributes(DefaultAccountingTemplate::class);
        if (count($attributes) > 0) {
            return $attributes[0]->newInstance()->getTemplate();
        }
        return null;
    }

    /**
     * @throws TemplateNotFoundException
     * @throws InvalidVoucherException
     * @throws AccountNotFoundException
     */
    public function toVoucher(): VoucherData
    {
        $template = $this->getTemplate();
        if (!$template) {
            throw new TemplateNotFoundException('No template found for ' . get_class($this));
        }
        return $template->make($this);
    }
}
