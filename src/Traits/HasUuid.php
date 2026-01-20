<?php

namespace RingleSoft\LaravelAccounting\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

trait HasUuid
{
    /**
     * Boot the HasUuid trait for a model.
     */
    protected static function bootHasUuid(): void
    {
        if (!config('laravel_accounting.use_uuid', false)) {
            return;
        }

        static::creating(function (Model $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }

    /**
     * Initialize the HasUuid trait for an instance.
     */
    public function initializeHasUuid(): void
    {
        if (!config('laravel_accounting.use_uuid', false)) {
            return;
        }

        $this->incrementing = false;
        $this->keyType = 'string';
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     */
    public function getIncrementing(): bool
    {
        if (config('laravel_accounting.use_uuid', false)) {
            return false;
        }

        return $this->incrementing ?? true;
    }

    /**
     * Get the auto-incrementing key type.
     */
    public function getKeyType(): string
    {
        if (config('laravel_accounting.use_uuid', false)) {
            return 'string';
        }

        return $this->keyType ?? 'int';
    }
}
