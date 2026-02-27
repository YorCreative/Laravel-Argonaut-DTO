<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use YorCreative\LaravelArgonautDTO\ArgonautImmutableDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\Enums\PriorityEnum;
use YorCreative\LaravelArgonautDTO\Tests\Support\Enums\StatusEnum;

class ImmutableEnumDTO extends ArgonautImmutableDTO
{
    public readonly ?StatusEnum $status;

    public readonly ?PriorityEnum $priority;

    public readonly ?string $name;

    protected array $casts = [
        'status' => StatusEnum::class,
        'priority' => PriorityEnum::class,
    ];
}
