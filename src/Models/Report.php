<?php

namespace RingleSoft\LaravelAccounting\Models;

use Carbon\Carbon;
use JsonException;
use RingleSoft\LaravelAccounting\Services\BalanceService;

abstract class Report
{
    protected string $title;

    protected Carbon $startDate;

    protected Carbon $endDate;

    protected array $parameters = [];

    protected array $data = [];

    protected BalanceService $balanceService;

    public function __construct(?array $params)
    {
        $this->balanceService = app(BalanceService::class);
        $this->parameters = $params ?? [];
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
        if (isset($parameters['start_date'])) {
            $this->from($parameters['start_date']);
        }
        if (isset($parameters['end_date'])) {
            $this->asOf($parameters['end_date']);
        }
        if (isset($parameters['as_of_date'])) {
            $this->asOf($parameters['as_of_date']);
        }

        return $this;
    }

    public function getParameter(string $key, $default = null)
    {
        return $this->parameters[$key] ?? $default;
    }

    abstract public function generate(): self;

    public function toArray(): array
    {
        return [
            'title' => $this->title ?? 'Report',
            'generated_at' => now()->toDateTimeString(),
            'parameters' => $this->parameters,
            'data' => $this->data,
        ];
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }

    protected function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    protected function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function asOf(Carbon|string $date): self
    {
        $this->endDate = Carbon::parse($date);

        return $this;
    }

    public function from(Carbon|string $date): self
    {
        $this->startDate = Carbon::parse($date);

        return $this;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
