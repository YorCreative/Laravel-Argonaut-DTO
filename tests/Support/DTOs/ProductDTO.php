<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Support\Collection;
use YorCreative\LaravelArgonautDTO\ArgonautDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers\UserDTOAssembler;

class ProductDTO extends ArgonautDTO
{
    public string $title;

    public array $features; // FeatureArgument[]

    public Collection|DatabaseCollection $reviews; // Collection<ReviewArgument>

    public ?UserDTO $user = null;

    protected array $casts = [
        'features' => [ProductFeatureDTO::class],
        'reviews' => Collection::class.':'.ProductReviewDTO::class,
        'user' => UserDTO::class,
    ];

    protected array $nestedAssemblers = [
        'user' => UserDTOAssembler::class,
    ];

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'reviews' => ['sometimes', 'required', 'collection', 'min:1'],
        ];
    }
}
