<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Support\Collection;
use YorCreative\LaravelArgonautDTO\ArgonautImmutableDTO;

class ImmutableProductDTO extends ArgonautImmutableDTO
{
    public readonly string $title;

    public readonly array $features;

    public readonly Collection $reviews;

    public readonly ?ImmutableUserDTO $user;

    protected array $casts = [
        'features' => [ProductFeatureDTO::class],
        'reviews' => Collection::class.':'.ProductReviewDTO::class,
        'user' => ImmutableUserDTO::class,
    ];

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
        ];
    }
}
