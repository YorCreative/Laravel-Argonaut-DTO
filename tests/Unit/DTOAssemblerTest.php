<?php

namespace YorCreative\LaravelArgonautDTO\Tests\Unit;

use Illuminate\Support\Collection;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers\FromPatternAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers\ProductDTOAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers\ProductDTOAssemblerInstance;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\Assemblers\UserDTOAssembler;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\FullNameDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductFeatureDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\ProductReviewDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\DTOs\UserDTO;
use YorCreative\LaravelArgonautDTO\Tests\Support\Models\TestEloquentModel;
use YorCreative\LaravelArgonautDTO\Tests\Support\Services\ExampleService;
use YorCreative\LaravelArgonautDTO\Tests\TestCase;

class DTOAssemblerTest extends TestCase
{
    public function test_assembles_single_dto()
    {
        $input = [
            'product_name' => 'Standing Desk',
            'user' => ['username' => 'Test-user', 'email' => 'testuser@test.com'],
            'features' => [['name' => 'Height Adjustable', 'description' => 'The standing desk moves up and down!']],
            'reviews' => [['displayName' => 'Test-user', 'rating' => 5, 'comment' => 'Excellent!']],
        ];

        $productDTO = ProductDTOAssembler::assemble($input, ProductDTO::class);

        $this->assertInstanceOf(ProductDTO::class, $productDTO);
        $this->assertSame('Standing Desk', $productDTO->title);
        $this->assertInstanceOf(UserDTO::class, $productDTO->user);
        $this->assertCount(1, $productDTO->features);
        $this->assertInstanceOf(ProductFeatureDTO::class, $productDTO->features[0]);
        $this->assertCount(1, $productDTO->reviews);
        $this->assertInstanceOf(ProductReviewDTO::class, $productDTO->reviews[0]);
    }

    public function test_assembles_array_to_collection()
    {
        $inputs = [
            ['rating' => 4, 'comment' => 'Good!'],
            ['rating' => 5, 'comment' => 'Perfect!'],
        ];

        $productReviewDTOCollection = ProductDTOAssembler::fromArray($inputs, ProductReviewDTO::class);

        $this->assertCount(2, $productReviewDTOCollection);
        $this->assertInstanceOf(Collection::class, $productReviewDTOCollection);
        $this->assertInstanceOf(ProductReviewDTO::class, $productReviewDTOCollection[0]);
    }

    public function test_assembles_collection_of_dtos_from_collection_raw_inputs()
    {
        $collectionOfRawProducts = collect();

        for ($x = 1; $x <= 3; $x++) {
            $collectionOfRawProducts->push([
                'product_name' => fake()->domainName(),
                'user' => ['display_name' => 'Test-user', 'email' => 'testuser@test.com'],
                'features' => [['name' => 'Height Adjustable', 'description' => 'The standing desk moves up and down!']],
                'reviews' => [['displayName' => 'Test-user', 'rating' => rand(1, 5), 'comment' => 'Was okay']],
            ]);
        }

        $collectionOfProductDTOs = ProductDTOAssembler::fromCollection($collectionOfRawProducts, ProductDTO::class);

        $this->assertInstanceOf(Collection::class, $collectionOfProductDTOs);

        foreach ($collectionOfProductDTOs as $productDTO) {
            $this->assertInstanceOf(ProductDTO::class, $productDTO);
            $this->assertInstanceOf(Collection::class, $productDTO->reviews);
            $this->assertIsArray($productDTO->features);

            foreach ($productDTO->features as $feature) {
                $this->assertInstanceOf(ProductFeatureDTO::class, $feature);
            }

            foreach ($productDTO->reviews as $review) {
                $this->assertInstanceOf(ProductReviewDTO::class, $review);
            }
        }

        $this->assertEquals('Test-user', $collectionOfProductDTOs[0]->user->username);
    }

    public function test_dto_assembler_throws_bad_function_exception()
    {
        $this->expectException(\BadFunctionCallException::class);

        UserDTOAssembler::assemble([], 'InvalidDTOClass');
    }

    public function test_user_assembler_multiple_methods(): void
    {
        $userDTO = UserDTOAssembler::assemble(
            [
                'display_name' => $username = 'Test-user',
                'email' => $email = 'testuser@test.com',
                'first_name' => $firstName = 'Test',
                'last_name' => $lastName = 'User',
            ],
            UserDTO::class
        );

        $this->assertInstanceOf(UserDTO::class, $userDTO);
        $this->assertSame($username, $userDTO->username);
        $this->assertSame($firstName.' '.$lastName, $userDTO->fullName);
        $this->assertSame($firstName, $userDTO->firstName);
        $this->assertSame($lastName, $userDTO->lastName);
    }

    public function test_assemble_accepts_array_input(): void
    {
        $input = [
            'product_name' => 'Ultra Chair',
            'user' => ['display_name' => 'array-user', 'email' => 'test@user.com'],
            'features' => [],
            'reviews' => [],
        ];

        $productDTO = ProductDTOAssembler::assemble($input, ProductDTO::class);

        $this->assertInstanceOf(ProductDTO::class, $productDTO);
        $this->assertSame('Ultra Chair', $productDTO->title);
    }

    public function test_array_assembler_creates_dto(): void
    {
        $data = [
            'display_name' => 'array-transform-user',
            'email' => 'at@example.com',
        ];

        $userDTO = UserDTOAssembler::arrayAssemble($data, UserDTO::class);

        $this->assertInstanceOf(UserDTO::class, $userDTO);
        $this->assertSame('array-transform-user', $userDTO->username);
    }

    public function test_it_can_transform_into_another_argument(): void
    {
        $data = [
            'first_name' => 'Test',
            'last_name' => 'User',
            'display_name' => 'array-transform-user',
            'email' => 'at@example.com',
        ];

        $DTO = UserDTOAssembler::arrayAssemble($data, FullNameDTO::class);

        $this->assertInstanceOf(FullNameDTO::class, $DTO);
        $this->assertSame('Test User', $DTO->fullName);
    }

    public function test_invalid_input_for_dto_assembling()
    {
        $input = [
            'product_name' => 'Standing Desk',
            'user' => ['username' => 'Test-user', 'email' => 'invalid-email'],  // invalid email
            'features' => null,  // invalid feature type
            'reviews' => 'invalid-reviews',  // invalid review format
        ];

        $this->expectException(\InvalidArgumentException::class);
        ProductDTOAssembler::assemble($input, ProductDTO::class);
    }

    public function test_optional_fields_with_missing_data()
    {
        $input = [
            'product_name' => 'Standing Desk',
            'user' => ['username' => 'Test-user', 'email' => 'testuser@test.com'],
        ];

        $productDTO = ProductDTOAssembler::assemble($input, ProductDTO::class);

        $this->assertEmpty($productDTO->features);  // Default empty
        $this->assertEmpty($productDTO->reviews);  // Default empty
    }

    public function test_it_can_transform_argument_model_into_eloquent_model()
    {
        $expected = 'Foo Bar';
        $input = [
            'bar' => $expected,
        ];

        $eloquentModel = ProductDTOAssembler::assemble($input, TestEloquentModel::class);

        $this->assertInstanceOf(TestEloquentModel::class, $eloquentModel);
        $this->assertEquals($expected, $eloquentModel->foo);
    }

    public function test_it_can_assemble_and_assemble_instance()
    {
        $input = [
            'bar' => 'not foo bar',
        ];

        $assembler = new ProductDTOAssemblerInstance($service = new ExampleService);
        $eloquentModel = $assembler::assemble($input, TestEloquentModel::class, $assembler);

        $this->assertInstanceOf(TestEloquentModel::class, $eloquentModel);
        $this->assertEquals($service->foo(), $eloquentModel->foo);

        $assembler->assembleInstance($input, TestEloquentModel::class);

        $this->assertInstanceOf(TestEloquentModel::class, $eloquentModel);
        $this->assertEquals($service->foo(), $eloquentModel->foo);
    }

    public function test_it_cannot_assemble_without_instance()
    {
        $DTO = ProductDTOAssemblerInstance::assemble($input = [
            'name' => $featureName = 'Desk',
            'subFeatures' => [
                ['name' => $subFeatureName = 'Foldable', 'description' => 'Folds with ease!'],
            ],
        ], ProductFeatureDTO::class);

        $this->assertInstanceOf(ProductFeatureDTO::class, $DTO);
        $this->assertEquals($featureName, $DTO->name);
        $this->assertCount(1, $DTO->subFeatures);
        $this->assertEquals($subFeatureName, $DTO->subFeatures[0]->name);

        $this->expectException(\BadFunctionCallException::class);
        $assembler = new ProductDTOAssemblerInstance(new ExampleService);
        $assembler::assemble($input, TestEloquentModel::class);
    }

    public function test_nested_assembler_for_array_cast_field()
    {
        $featuresInput = [
            (object) ['description' => 'No name description'],
            ['description' => 'Another no name'],
        ];

        $attributes = [
            'title' => 'Test Product',
            'features' => $featuresInput,
            'reviews' => [],
            'user' => null,
        ];

        $refClass = new \ReflectionClass(ProductDTO::class);
        $dto = $refClass->newInstanceWithoutConstructor();

        $prop = $refClass->getProperty('nestedAssemblers');
        $prop->setAccessible(true);
        $prop->setValue($dto, ['features' => ProductDTOAssembler::class]);

        $setAttributesMethod = $refClass->getMethod('setAttributes');
        $setAttributesMethod->setAccessible(true);
        $setAttributesMethod->invoke($dto, $attributes);

        $this->assertIsArray($dto->features);
        $this->assertCount(2, $dto->features);
        $this->assertInstanceOf(ProductFeatureDTO::class, $dto->features[0]);
        $this->assertSame('Unnamed Feature', $dto->features[0]->name);
        $this->assertSame('Unnamed Feature', $dto->features[1]->name);
    }

    public function test_nested_assembler_with_collection_value()
    {
        $reviewsInput = collect([
            ['rating' => 3],
            (object) ['rating' => 4],
        ]);

        $attributes = [
            'title' => 'Test Product',
            'features' => [],
            'reviews' => $reviewsInput,
            'user' => null,
        ];

        $refClass = new \ReflectionClass(ProductDTO::class);
        $dto = $refClass->newInstanceWithoutConstructor();

        $prop = $refClass->getProperty('nestedAssemblers');
        $prop->setAccessible(true);
        $prop->setValue($dto, ['reviews' => ProductDTOAssembler::class]);

        $setAttributesMethod = $refClass->getMethod('setAttributes');
        $setAttributesMethod->setAccessible(true);
        $setAttributesMethod->invoke($dto, $attributes);

        $this->assertInstanceOf(Collection::class, $dto->reviews);
        $this->assertCount(2, $dto->reviews);
        $this->assertInstanceOf(ProductReviewDTO::class, $dto->reviews[0]);
        $this->assertSame('', $dto->reviews[0]->comment);
        $this->assertSame('', $dto->reviews[1]->comment);
    }

    public function test_assembler_resolves_from_method_pattern(): void
    {
        $dto = FromPatternAssembler::assemble(
            ['name' => 'test-user', 'email' => 'test@example.com'],
            UserDTO::class
        );

        $this->assertInstanceOf(UserDTO::class, $dto);
        $this->assertSame('test-user', $dto->username);
        $this->assertSame('test@example.com', $dto->email);
    }

    public function test_assembler_from_pattern_with_array(): void
    {
        $users = FromPatternAssembler::fromArray([
            ['name' => 'user1', 'email' => 'user1@example.com'],
            ['name' => 'user2', 'email' => 'user2@example.com'],
        ], UserDTO::class);

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(2, $users);
        $this->assertSame('user1', $users[0]->username);
        $this->assertSame('user2', $users[1]->username);
    }
}
