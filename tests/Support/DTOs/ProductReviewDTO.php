<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Support\Carbon;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;

class ProductReviewDTO extends ArgonautDTO
{
    public ?string $displayName = null;

    public int $rating;

    public ?string $comment = null;

    public ?Carbon $createdAt = null;

    protected array $casts = [
        'displayName' => 'string',
        'rating' => 'int',
        'comment' => 'string',
        'createdAt' => Carbon::class,
    ];
}
