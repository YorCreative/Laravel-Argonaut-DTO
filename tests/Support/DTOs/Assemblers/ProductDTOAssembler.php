<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers;

use YorCreative\LaravelArgonautDTO\ArgonautAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductFeatureDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductReviewDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\Models\TestEloquentModel;

class ProductDTOAssembler extends ArgonautAssembler
{
    public static function toProductDTO(object $input): ProductDTO
    {
        return new ProductDTO([
            'title' => $input->product_name,
            'user' => $input?->user,
            'features' => $input->features ?? [],
            'reviews' => $input->reviews ?? [],
        ]);
    }

    public static function toProductFeatureDTO(object $input): ProductFeatureDTO
    {
        return new ProductFeatureDTO([
            'name' => $input->name ?? 'Unnamed Feature',
            'description' => $input->description ?? null,
        ]);
    }

    public static function toProductReviewDTO(object $input): ProductReviewDTO
    {
        return new ProductReviewDTO([
            'rating' => (int) ($input->rating ?? 0),
            'comment' => $input->comment ?? '',
        ]);
    }

    public static function toTestEloquentModel(object $input): TestEloquentModel
    {
        $testEloquentModel = new TestEloquentModel;
        $testEloquentModel->foo = $input->bar;

        return $testEloquentModel;
    }
}
