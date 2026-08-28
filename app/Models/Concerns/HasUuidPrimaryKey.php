<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Shared behaviour for the tables that were created directly in Supabase
 * with a `uuid` primary key rather than a bigint identity column. Eloquent
 * generates the id on create so the model works the same on the sqlite
 * test database.
 */
trait HasUuidPrimaryKey
{
    public function initializeHasUuidPrimaryKey(): void
    {
        $this->incrementing = false;
        $this->keyType = 'string';
    }

    protected static function bootHasUuidPrimaryKey(): void
    {
        static::creating(function (Model $model): void {
            if (empty($model->getKey())) {
                $model->{$model->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}
