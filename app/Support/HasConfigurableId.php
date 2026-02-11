<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Trait to allow switching primary key strategy via config.
 *
 * Usage: `use HasConfigurableId;` in your model.
 * Configure in config/saas.php → id_strategy (bigint, uuid, ulid).
 */
trait HasConfigurableId
{
    public static function bootHasConfigurableId(): void
    {
        $strategy = config('saas.id_strategy', 'bigint');

        if ($strategy === 'uuid') {
            // UUID models need HasUuids trait behaviour bootstrapped
            (new class
            {
                use HasUuids;
            })::bootHasUuids();
        }

        if ($strategy === 'ulid') {
            (new class
            {
                use HasUlids;
            })::bootHasUlids();
        }
    }

    public function getIncrementing(): bool
    {
        return config('saas.id_strategy', 'bigint') === 'bigint';
    }

    public function getKeyType(): string
    {
        return config('saas.id_strategy', 'bigint') === 'bigint' ? 'int' : 'string';
    }
}
