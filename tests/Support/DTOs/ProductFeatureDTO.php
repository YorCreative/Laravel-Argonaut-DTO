<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Support\Collection;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;

class ProductFeatureDTO extends ArgonautDTO
{
    public string $name;

    public ?string $description = null;

    public ?Collection $subFeatures = null;

    protected array $casts = [
        'subFeatures' => Collection::class.':'.ProductFeatureDTO::class,
    ];
}
