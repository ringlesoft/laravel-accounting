<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use ReflectionClass;
use RingleSoft\LaravelAccounting\Attributes\DefaultAccountingTemplate;
use RingleSoft\LaravelAccounting\Contracts\PostableModel;
use RingleSoft\LaravelAccounting\DTOs\VoucherData;
use RingleSoft\LaravelAccounting\Exceptions\AccountNotFoundException;
use RingleSoft\LaravelAccounting\Exceptions\InvalidVoucherException;
use RingleSoft\LaravelAccounting\Exceptions\JournalEntryAlreadyExistsException;
use RingleSoft\LaravelAccounting\Exceptions\JournalNotBalancedException;
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
 *
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

    public function withTemplate(AccountingTemplate $template): self
    {
        $this->template = $template;

        return $this;
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
            throw new JournalEntryAlreadyExistsException;
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
    public function post(): bool // createAndPostJournal() instead
    {
        try {
            return DB::transaction(function () {
                return $this->createJournal() && $this->postJournal();
            });
        } catch (Throwable $e) {
            // A QueryException's message embeds the raw SQL and bound values -
            // never echo it verbatim, since host applications commonly surface
            // this exception's message directly to end users. The original is
            // still chained as $previous for internal logging/debugging.
            $message = $e instanceof QueryException
                ? 'Failed to post to journal due to a database error.'
                : 'Failed to post to journal. '.$e->getMessage();

            throw LaravelAccountingException::from($e, $message);
        }
    }

    public function postWithTemplate(AccountingTemplate $template): bool
    {
        $this->template = $template;

        return $this->post();
    }

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
        if (! empty($this->template)) {
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
     * @throws AccountNotFoundException
     * @throws InvalidVoucherException
     * @throws JournalNotBalancedException
     * @throws TemplateNotFoundException
     */
    public function toVoucher(): VoucherData
    {
        $template = $this->getTemplate();
        if (! $template) {
            throw new TemplateNotFoundException('No template found for '.get_class($this));
        }

        return $template->make($this);
    }
}
