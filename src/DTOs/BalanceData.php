<?php

namespace RingleSoft\LaravelAccounting\DTOs;

use RingleSoft\LaravelAccounting\Support\Money;

/**
 * All amounts are numeric strings with Money::SCALE decimal places.
 */
class BalanceData
{
    public string $opening;

    public string $debits;

    public string $credits;

    public string $closing;

    public function __construct(
        string|int|float $opening = 0,
        string|int|float $debits = 0,
        string|int|float $credits = 0,
        string|int|float $closing = 0
    ) {
        $this->opening = Money::of($opening);
        $this->debits = Money::of($debits);
        $this->credits = Money::of($credits);
        $this->closing = Money::of($closing);
    }

    /**
     * Calculate closing balance from opening, debits, and credits
     */
    public function calculateClosing(string $normalBalance = 'debit'): string
    {
        if ($normalBalance === 'debit') {
            $this->closing = Money::sub(Money::add($this->opening, $this->debits), $this->credits);
        } else {
            $this->closing = Money::sub(Money::add($this->opening, $this->credits), $this->debits);
        }

        return $this->closing;
    }

    /**
     * Get net movement (debits - credits)
     */
    public function getNetMovement(): string
    {
        return Money::sub($this->debits, $this->credits);
    }

    /**
     * Get total activity (debits + credits)
     */
    public function getTotalActivity(): string
    {
        return Money::add($this->debits, $this->credits);
    }

    /**
     * Check if there is any activity
     */
    public function hasActivity(): bool
    {
        return Money::isPositive($this->debits) || Money::isPositive($this->credits);
    }

    /**
     * Get the change from opening to closing
     */
    public function getChange(): string
    {
        return Money::sub($this->closing, $this->opening);
    }

    /**
     * Get percentage change. A ratio, not an amount, so it stays a float.
     */
    public function getPercentageChange(): ?float
    {
        if (Money::isZero($this->opening)) {
            return null;
        }

        return ((float) bcdiv($this->getChange(), Money::abs($this->opening), 10)) * 100;
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
            opening: $data['opening'] ?? 0,
            debits: $data['debits'] ?? 0,
            credits: $data['credits'] ?? 0,
            closing: $data['closing'] ?? 0
        );
    }

    /**
     * Create with opening balance
     */
    public static function withOpening(string|int|float $opening): self
    {
        return new self(opening: $opening);
    }

    /**
     * Add debit amount
     */
    public function addDebit(string|int|float $amount): self
    {
        $this->debits = Money::add($this->debits, Money::abs($amount));

        return $this;
    }

    /**
     * Add credit amount
     */
    public function addCredit(string|int|float $amount): self
    {
        $this->credits = Money::add($this->credits, Money::abs($amount));

        return $this;
    }

    /**
     * Set opening balance
     */
    public function setOpening(string|int|float $opening): self
    {
        $this->opening = Money::of($opening);

        return $this;
    }

    /**
     * Set closing balance
     */
    public function setClosing(string|int|float $closing): self
    {
        $this->closing = Money::of($closing);

        return $this;
    }
}
