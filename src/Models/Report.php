<?php

namespace RingleSoft\LaravelAccounting\Models;

use Carbon\Carbon;
use RingleSoft\LaravelAccounting\Services\BalanceService;

abstract class Report
{
    protected string $title;
    protected array $parameters = [];
    protected array $data = [];
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function setParameters(array $parameters): self
    {
        $this->parameters = $parameters;
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

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_PRETTY_PRINT);
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
}
