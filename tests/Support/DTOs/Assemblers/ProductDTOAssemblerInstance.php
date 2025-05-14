<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers;

use YorCreative\LaravelArgonautDTO\ArgonautAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductFeatureDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductReviewDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\Models\TestEloquentModel;
use YorCreative\LaravelArgonautDTO\Tests\Support\Services\ExampleService;

class ProductDTOAssemblerInstance extends ArgonautAssembler
{
    public function __construct(protected ExampleService $exampleService)
    {
        //
    }

    public function toProductDTO(object $input): ProductDTO
    {
        $product = new ProductDTO([
            'title' => $input->product_name,
            'user' => $input?->user,
            'features' => $input->features ?? [],
            'reviews' => $input->reviews ?? [],
        ]);

        $product->title = $this->exampleService->foo();

        return $product;
    }

    public static function toProductFeatureDTO(object $input): ProductFeatureDTO
    {
        return new ProductFeatureDTO([
            'name' => $input->name,
            'description' => $input->description ?? null,
            'subFeatures' => $input->subFeatures ?? null,
        ]);
    }

    public function toProductReviewDTO(object $input): ProductReviewDTO
    {
        return new ProductReviewDTO([
            'rating' => (int) ($input->rating ?? 0),
            'comment' => $this->exampleService->foo(),
        ]);
    }

    public function toTestEloquentModel(object $input): TestEloquentModel
    {
        $testEloquentModel = new TestEloquentModel;
        $input->bar = $this->exampleService->foo();
        $testEloquentModel->foo = $input->bar;

        return $testEloquentModel;
    }
}
