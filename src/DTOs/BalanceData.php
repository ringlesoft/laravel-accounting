<?php

namespace RingleSoft\LaravelAccounting\DTOs;

class BalanceData
{
    public function __construct(
        public float $opening = 0.0,
        public float $debits = 0.0,
        public float $credits = 0.0,
        public float $closing = 0.0
    ) {}

    /**
     * Calculate closing balance from opening, debits, and credits
     */
    public function calculateClosing(string $normalBalance = 'debit'): float
    {
        if ($normalBalance === 'debit') {
            $this->closing = $this->opening + $this->debits - $this->credits;
        } else {
            $this->closing = $this->opening + $this->credits - $this->debits;
        }

        return $this->closing;
    }

    /**
     * Get net movement (debits - credits)
     */
    public function getNetMovement(): float
    {
        return $this->debits - $this->credits;
    }

    /**
     * Get total activity (debits + credits)
     */
    public function getTotalActivity(): float
    {
        return $this->debits + $this->credits;
    }

    /**
     * Check if there is any activity
     */
    public function hasActivity(): bool
    {
        return $this->debits > 0 || $this->credits > 0;
    }

    /**
     * Get the change from opening to closing
     */
    public function getChange(): float
    {
        return $this->closing - $this->opening;
    }

    /**
     * Get percentage change
     */
    public function getPercentageChange(): ?float
    {
        if ($this->opening == 0) {
            return null;
        }

        return (($this->closing - $this->opening) / abs($this->opening)) * 100;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'opening' => $this->opening,
            'debits' => $this->debits,
            'credits' => $this->credits,
            'closing' => $this->closing,
            'net_movement' => $this->getNetMovement(),
            'total_activity' => $this->getTotalActivity(),
            'change' => $this->getChange(),
            'percentage_change' => $this->getPercentageChange(),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            opening: $data['opening'] ?? 0.0,
            debits: $data['debits'] ?? 0.0,
            credits: $data['credits'] ?? 0.0,
            closing: $data['closing'] ?? 0.0
        );
    }

    /**
     * Create with opening balance
     */
    public static function withOpening(float $opening): self
    {
        return new self(opening: $opening);
    }

    /**
     * Add debit amount
     */
    public function addDebit(float $amount): self
    {
        $this->debits += abs($amount);
        return $this;
    }

    /**
     * Add credit amount
     */
    public function addCredit(float $amount): self
    {
        $this->credits += abs($amount);
        return $this;
    }

    /**
     * Set opening balance
     */
    public function setOpening(float $opening): self
    {
        $this->opening = $opening;
        return $this;
    }

    /**
     * Set closing balance
     */
    public function setClosing(float $closing): self
    {
        $this->closing = $closing;
        return $this;
    }
}
