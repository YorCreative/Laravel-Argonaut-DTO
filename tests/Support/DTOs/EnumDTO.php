<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use YorCreative\LaravelArgonautDTO\ArgonautDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\Enums\PriorityEnum;
use YorCreative\LaravelArgonautDTO\Tests\Support\Enums\StatusEnum;

class EnumDTO extends ArgonautDTO
{
    public ?StatusEnum $status = null;

    public ?PriorityEnum $priority = null;

    public ?string $name = null;

    protected array $casts = [
        'status' => StatusEnum::class,
        'priority' => PriorityEnum::class,
    ];
}
