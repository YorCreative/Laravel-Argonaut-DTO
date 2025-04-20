<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\InvalidDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductFeatureDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductReviewDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\UserDTO;
use YorCreative\LaravelArgonautDTO\Tests\TestCase;

class DTOTest extends TestCase
{
    public function test_sets_and_casts_basic_fields(): void
    {
        $user = new UserDTO([
            'email' => 'test@user.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'registeredAt' => '2023-01-01 10:00:00',
        ]);

        $this->assertSame('Test User', $user->fullName);
        $this->assertInstanceOf(Carbon::class, $user->registeredAt);
        $this->assertSame('2023-01-01', $user->registeredAt->toDateString());
    }

    public function test_casts_nested_array_to_argument_array(): void
    {
        $product = new ProductDTO([
            'title' => 'Desk',
            'features' => [
                ['name' => 'Foldable', 'description' => 'Folds with ease!'],
                ['name' => 'Lightweight', 'description' => 'Lightweight and easy to move!'],
            ],
        ]);

        $this->assertIsArray($product->features);
        $this->assertInstanceOf(ProductFeatureDTO::class, $product->features[0]);
        $this->assertSame('Foldable', $product->features[0]->name);
    }

    public function test_casts_to_collection_of_argument_models(): void
    {
        $product = new ProductDTO([
            'title' => 'Desk',
            'reviews' => [
                ['rating' => 5, 'comment' => 'Great!'],
                ['rating' => 4, 'comment' => 'Good.'],
            ],
        ]);

        $this->assertInstanceOf(Collection::class, $product->reviews);
        $this->assertCount(2, $product->reviews);
        $this->assertInstanceOf(ProductReviewDTO::class, $product->reviews->first());
    }

    public function test_casts_collection_values_recursively(): void
    {
        $product = new ProductDTO([
            'title' => 'Desk',
            'reviews' => [
                ['rating' => 5, 'comment' => 'Great!', 'createdAt' => '2023-01-01'],
                ['rating' => 4, 'comment' => 'Good.', 'createdAt' => '2023-01-02'],
            ],
        ]);

        $this->assertInstanceOf(Collection::class, $product->reviews);
        $this->assertCount(2, $product->reviews);
        $this->assertInstanceOf(ProductReviewDTO::class, $product->reviews[0]);
        $this->assertInstanceOf(Carbon::class, $product->reviews[0]->createdAt);
        $this->assertSame('2023-01-01', $product->reviews[0]->createdAt->toDateString());
        $this->assertInstanceOf(ProductReviewDTO::class, $product->reviews[1]);
        $this->assertInstanceOf(Carbon::class, $product->reviews[1]->createdAt);
        $this->assertSame('2023-01-02', $product->reviews[1]->createdAt->toDateString());
    }

    public function test_serializes_to_array_and_json(): void
    {
        $user = new UserDTO([
            'email' => 'test@user.com',
            'firstName' => 'Test',
            'lastName' => 'User',
            'registeredAt' => '2023-01-01',
        ]);

        $array = $user->toArray();
        $this->assertIsArray($array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('firstName', $array);
        $this->assertArrayHasKey('lastName', $array);
        $this->assertArrayHasKey('fullName', $array);
        $this->assertArrayHasKey('registeredAt', $array);

        $json = $user->toJson();
        $this->assertJson($json);
    }

    public function test_json_encoding_failure_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/JSON error:/');

        $model = new class([]) extends UserDTO
        {
            public function toArray(int $depth = 3): array
            {
                $res = tmpfile(); // resource that cannot be json_encoded

                return ['bad' => $res];
            }
        };

        $model->toJson();
    }

    public function test_recursive_array_serialization_depth_limit(): void
    {
        $user = new UserDTO([
            'email' => 'a@b.com',
            'firstName' => 'A',
            'lastName' => 'B',
            'registeredAt' => now(),
        ]);

        $result = $user->toArray(depth: 0);
        $this->assertSame([], $result);
    }

    public function test_get_attributes_to_update_removes_internal_props(): void
    {
        $user = new UserDTO([
            'firstName' => 'A',
            'lastName' => 'B',
        ]);

        $attributes = $user->getAttributesToUpdate();
        $this->assertArrayNotHasKey('casts', $attributes);
        $this->assertArrayNotHasKey('prioritizedAttributes', $attributes);
    }

    public function test_static_collection_factory(): void
    {
        $items = UserDTO::collection([
            ['firstName' => 'A', 'lastName' => 'B'],
            ['firstName' => 'C', 'lastName' => 'D'],
        ]);

        $this->assertInstanceOf(Collection::class, $items);
        $this->assertInstanceOf(UserDTO::class, $items->first());
    }

    public function test_user_argument_validates_successfully(): void
    {
        $user = new UserDTO([
            'firstName' => 'Jane',
            'lastName' => 'Doe',
            'username' => 'jdoe',
            'email' => 'jane@example.com',
            'registeredAt' => '2024-01-01',
        ]);

        $this->assertTrue($user->isValid());
    }

    public function test_validation_returns_errors_without_throwing(): void
    {
        $user = new UserDTO([
            'email' => 'invalid',
        ]);

        $result = $user->validate(throw: false);
        $this->assertIsArray($result);
        foreach ($result as $errorField => $errorMessages) {
            if ($errorField === 'email') {
                $this->assertEquals('email', $errorField);
                $this->assertEquals('validation.email', $errorMessages[0]);
            } else {
                $this->assertEquals('username', $errorField);
                $this->assertEquals('validation.required', $errorMessages[0]);
            }
        }
    }

    public function test_validation_exception_thrown_on_failure(): void
    {
        $this->expectException(ValidationException::class);

        $user = new UserDTO([
            'email' => 'invalid',
        ]);

        $user->validate(); // Default is to throw
    }

    public function test_missing_rules_method_throws_logic_exception(): void
    {
        $this->expectException(\LogicException::class);

        $arg = new InvalidDTO([
            'something' => 'value',
        ]);

        $arg->validate();
    }

    public function test_cast_output_value_handles_collection_to_array(): void
    {
        $product = new ProductDTO([
            'title' => 'Desk',
            'features' => [['name' => 'Feature A', 'subFeatures' => [
                ['name' => 'Feature B', 'description' => 'sub feature'],
                ['name' => 'Feature C', 'description' => 'sub feature'],
                ['name' => 'Feature D', 'description' => 'sub feature'],
                new ProductFeatureDTO([
                    'name' => 'Feature E',
                ]),
            ]]],
            'reviews' => [
                ['rating' => 5, 'comment' => 'Great!', 'createdAt' => '2023-01-01'],
                ['rating' => 4, 'comment' => 'Good.', 'createdAt' => '2023-01-02'],
            ],
        ]);

        foreach ($product->features as $productFeatureDTO) {
            $this->assertInstanceOf(ProductFeatureDTO::class, $productFeatureDTO);
            $this->assertInstanceOf(Collection::class, $productFeatureDTO->subFeatures);

            if (! empty($productFeatureDTO->subFeatures)) {
                foreach ($productFeatureDTO->subFeatures as $subFeatureDTO) {
                    $this->assertInstanceOf(ProductFeatureDTO::class, $subFeatureDTO);
                    $this->assertInstanceOf(Collection::class, $productFeatureDTO->subFeatures);
                }
            }
        }

        $DTOArray = $product->toArray();

        $this->assertIsArray($DTOArray);
        $this->assertArrayHasKey('reviews', $DTOArray);
        $this->assertIsArray($DTOArray['reviews']);
        $this->assertCount(2, $DTOArray['reviews']);
    }

    public function test_is_valid_returns_false_on_validation_failure(): void
    {
        $user = new UserDTO([
            'email' => 'invalid', // Invalid email to trigger validation failure
        ]);

        $this->assertFalse($user->isValid(true));
    }
}
