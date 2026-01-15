<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Unit;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ImmutableProductDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ImmutableUserDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductFeatureDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductReviewDTO;
use YorCreative\LaravelArgonautDTO\Tests\TestCase;

class ImmutableDTOTest extends TestCase
{
    public function test_creates_immutable_dto_with_basic_fields(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $this->assertSame('jdoe', $user->username);
        $this->assertSame('jdoe@example.com', $user->email);
        $this->assertSame('John', $user->firstName);
        $this->assertSame('Doe', $user->lastName);
    }

    public function test_casts_datetime_fields(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'registeredAt' => '2023-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(Carbon::class, $user->registeredAt);
        $this->assertSame('2023-01-15', $user->registeredAt->toDateString());
    }

    public function test_serializes_to_array(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
        ]);

        $array = $user->toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('username', $array);
        $this->assertArrayHasKey('email', $array);
        $this->assertArrayHasKey('firstName', $array);
        $this->assertArrayHasKey('lastName', $array);
        $this->assertArrayNotHasKey('casts', $array);
        $this->assertArrayNotHasKey('nestedAssemblers', $array);
    }

    public function test_serializes_to_json(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
        ]);

        $json = $user->toJson();

        $this->assertJson($json);
        $decoded = json_decode($json, true);
        $this->assertSame('jdoe', $decoded['username']);
    }

    public function test_validates_successfully(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
        ]);

        $this->assertTrue($user->isValid());
    }

    public function test_validation_fails_for_invalid_data(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'invalid-email',
        ]);

        $this->assertFalse($user->isValid());
    }

    public function test_validation_returns_errors(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'invalid-email',
        ]);

        $errors = $user->validate(throw: false);

        $this->assertIsArray($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_validation_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'invalid-email',
        ]);

        $user->validate();
    }

    public function test_creates_collection_of_immutable_dtos(): void
    {
        $users = ImmutableUserDTO::collection([
            ['username' => 'user1', 'email' => 'user1@example.com'],
            ['username' => 'user2', 'email' => 'user2@example.com'],
        ]);

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(2, $users);
        $this->assertInstanceOf(ImmutableUserDTO::class, $users->first());
    }

    public function test_casts_nested_array_of_dtos(): void
    {
        $product = new ImmutableProductDTO([
            'title' => 'Standing Desk',
            'features' => [
                ['name' => 'Height Adjustable', 'description' => 'Adjusts from 28" to 48"'],
                ['name' => 'Memory Presets', 'description' => '4 programmable heights'],
            ],
            'reviews' => [],
            'user' => null,
        ]);

        $this->assertIsArray($product->features);
        $this->assertCount(2, $product->features);
        $this->assertInstanceOf(ProductFeatureDTO::class, $product->features[0]);
        $this->assertSame('Height Adjustable', $product->features[0]->name);
    }

    public function test_casts_nested_collection_of_dtos(): void
    {
        $product = new ImmutableProductDTO([
            'title' => 'Standing Desk',
            'features' => [],
            'reviews' => [
                ['rating' => 5, 'comment' => 'Excellent!'],
                ['rating' => 4, 'comment' => 'Very good'],
            ],
            'user' => null,
        ]);

        $this->assertInstanceOf(Collection::class, $product->reviews);
        $this->assertCount(2, $product->reviews);
        $this->assertInstanceOf(ProductReviewDTO::class, $product->reviews->first());
        $this->assertSame(5, $product->reviews->first()->rating);
    }

    public function test_casts_nested_single_dto(): void
    {
        $product = new ImmutableProductDTO([
            'title' => 'Standing Desk',
            'features' => [],
            'reviews' => [],
            'user' => [
                'username' => 'seller',
                'email' => 'seller@example.com',
            ],
        ]);

        $this->assertInstanceOf(ImmutableUserDTO::class, $product->user);
        $this->assertSame('seller', $product->user->username);
    }

    public function test_handles_null_values(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
            'firstName' => null,
            'lastName' => null,
            'registeredAt' => null,
        ]);

        $this->assertNull($user->firstName);
        $this->assertNull($user->lastName);
        $this->assertNull($user->registeredAt);
    }

    public function test_readonly_property_cannot_be_modified(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
        ]);

        $this->expectException(\Error::class);
        $this->expectExceptionMessage('Cannot modify readonly property');

        $user->username = 'different';
    }

    public function test_recursive_serialization_with_depth(): void
    {
        $product = new ImmutableProductDTO([
            'title' => 'Test Product',
            'features' => [['name' => 'Feature 1']],
            'reviews' => [['rating' => 5, 'comment' => 'Great!']],
            'user' => ['username' => 'test', 'email' => 'test@test.com'],
        ]);

        $array = $product->toArray();

        $this->assertIsArray($array);
        $this->assertIsArray($array['features']);
        $this->assertIsArray($array['reviews']);
        $this->assertIsArray($array['user']);
    }

    public function test_depth_zero_returns_empty_array(): void
    {
        $user = new ImmutableUserDTO([
            'username' => 'jdoe',
            'email' => 'jdoe@example.com',
        ]);

        $result = $user->toArray(depth: 0);

        $this->assertSame([], $result);
    }
}
