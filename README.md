<div align="center">
  <a href="https://github.com/YorCreative">
    <img src="content/Laravel-Argonaut-DTO.png" alt="Logo" width="245" height="200">
  </a>
</div>
<h3 align="center">Laravel Argonaut DTO</h3>


<div align="center">
<a href="https://github.com/YorCreative/Laravel-Argonaut-DTO/blob/main/LICENSE.md"><img alt="GitHub license" src="https://img.shields.io/github/license/YorCreative/Laravel-Argonaut-DTO"></a>
<a href="https://github.com/YorCreative/Laravel-Argonaut-DTO/stargazers"><img alt="GitHub stars" src="https://img.shields.io/github/stars/YorCreative/Laravel-Argonaut-DTO?label=Repo%20Stars"></a>
<img alt="GitHub Org's stars" src="https://img.shields.io/github/stars/YorCreative?style=social&label=YorCreative%20Stars&link=https%3A%2F%2Fgithub.com%2FYorCreative">
<a href="https://github.com/YorCreative/Laravel-Argonaut-DTO/issues"><img alt="GitHub issues" src="https://img.shields.io/github/issues/YorCreative/Laravel-Argonaut-DTO"></a>
<a href="https://github.com/YorCreative/Laravel-Argonaut-DTO/network"><img alt="GitHub forks" src="https://img.shields.io/github/forks/YorCreative/Laravel-Argonaut-DTO"></a>
<a href="https://github.com/YorCreative/Laravel-Argonaut-DTO/actions/workflows/phpunit-tests.yml"><img alt="PHPUnit" src="https://github.com/YorCreative/Laravel-Argonaut-DTO/actions/workflows/phpunit-tests.yml/badge.svg"></a>
</div>

Laravel Argonaut DTO is a lightweight, highly composable package for transforming arrays, objects, or collections into
structured DTOs (Data Transfer Objects), with built-in support for:

- 🧱 Deep nested transformation and casting
- 🔁 Type-safe data conversion
- ✅ Validation using Laravel’s validator
- 🧠 Explicit attribute prioritization
- 📦 Clean serialization (`toArray`, `toJson`)
- ♻️ Consistent data shape enforcement across boundaries

---

## 📦 Installation

Install via Composer:

```bash
composer require yorcreative/laravel-argonaut-dto
```

---

## 🚀 Quick Start

### 1. Define a DTO

DTOs extend `ArgonautDTO`, and define your expected structure via public properties, casting rules, and validation.

```php
class UserDTO extends ArgonautDTO
{
    public string $username;
    public string $email;

    protected array $casts = [
        'username' => 'string',
        'email' => 'string',
    ];

    public function rules(): array
    {
        return [
            'username' => ['required', 'string'],
            'email' => ['required', 'email'],
        ];
    }
}
```

This defines a strongly typed DTO with both validation rules and simple type casting.

---

### 2. Create an Assembler

Assemblers are responsible for mapping raw inputs (arrays or objects) into your DTOs.

```php
// static usage example
class UserDTOAssembler extends ArgonautAssembler
{
    public static function toUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'username' => $input->display_name,
            'email' => $input->email,
        ]);
    }
}

// instance usage example
class UserDTOAssembler extends ArgonautAssembler
{
    public function __construct(protected UserFormattingService $formattingService) 
    {
        //
    }
    
    public static function toUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'username' => $formatingService->userName($input->display_name),
            'email' => $formatingService->email($input->email),
        ]);
    }
}
```

> Assembler method names must follow the format `to<ClassName>` or `from<ClassName>`, and are resolved automatically using `class_basename`.

---

### 3. Assemble a DTO

Use the assembler to transform raw data into structured, casted DTO instances.

```php
// static usage example
$dto = UserDTOAssembler::assemble([
    'display_name' => 'jdoe',
    'email' => 'jdoe@example.com',
], UserDTO::class);

// instance usage example
$dto = $userDTOAssemblerInstance->assembleInstance([
    'display_name' => 'jdoe',
    'email' => 'jdoe@example.com',
], UserDTO::class);
```

You can also batch transform arrays or collections:

```php
// static usage
UserDTOAssembler::fromArray($userArray, UserDTO::class);
UserDTOAssembler::fromCollection($userCollection, UserDTO::class);

// instance usage
UserDTOAssembler::fromArray($userArray, UserDTO::class, $userDTOAssemblerInstance);
UserDTOAssembler::fromCollection($userCollection, UserDTO::class, $userDTOAssemblerInstance);

// or using the assembler instance's static methods
$userDTOAssemblerInstance::fromArray($userArray, UserDTO::class, $userDTOAssemblerInstance);
$userDTOAssemblerInstance::fromCollection($userCollection, UserDTO::class, $userDTOAssemblerInstance);
```

---

## 🧪 Real-World Static Usage Example: Product + Features + Reviews

This example demonstrates nested relationships and complex type casting in action.

### ProductDTO with nested casting:

```php
class ProductDTO extends ArgonautDTO
{
    public string $title;
    public array $features;
    public Collection $reviews;
    public ?UserDTO $user = null;

    protected array $casts = [
        'features' => [ProductFeatureDTO::class],
        'reviews' => Collection::class . ':' . ProductReviewDTO::class,
        'user' => UserDTO::class,
    ];

    public function rules(): array
    {
        return [
            'title' => ['required', 'string'],
            'reviews' => ['sometimes', 'required', 'collection', 'min:1'],
        ];
    }
}
```

### ProductDTOAssembler mapping input structure:

```php
class ProductDTOAssembler extends ArgonautAssembler
{
    public static function toProductDTO(object $input): ProductDTO
    {
        return new ProductDTO([
            'title' => $input->product_name,
            'user' => $input->user,
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
}
```

## 🎯 Advanced: Dependency Injection in Assemblers

ArgonautAssembler offers enhanced flexibility for your Assembler logic by supporting dependency injection. This allows
you to leverage services or custom logic, whether defined in static or non-static methods, during the DTO assembly
process. This is particularly powerful when integrating with Laravel's service container.

This feature enables you to:

- **Integrate Application Services:** Easily inject your existing application services (e.g., a custom formatting
  utility, a validation service) directly into your assembler methods.
- **Decouple Complex Logic:** Keep your assembler methods focused on the core task of data mapping by delegating more
  complex operations or external data fetching/processing to injected dependencies.
- **Improve Testability:** By injecting dependencies, you can more easily mock them in your unit tests, leading to more
  robust and isolated tests for your assemblers.

### How Dependency Injection Works

`ArgonautAssembler` supports dependency injection in non-static transformation methods (e.g., `toUserDTO` or
`fromUserDTO`) by leveraging Laravel’s service container. When you call `ArgonautAssembler::assemble()`,
`fromCollection()`, `fromArray()`, or `assembleInstance()` with an instance of the assembler, the transformation method
is invoked on that instance. Laravel’s container automatically resolves and injects any dependencies declared in the
method’s signature.

- **Static Methods:** Static transformation methods (e.g., `public static function toUserDTO($input)`) do not support
  dependency injection, as they are called statically without an instance.
- **Instance Methods:** Non-static transformation methods (e.g., `public function toUserDTO($input)`) are called on an
  assembler instance, allowing Laravel to inject dependencies into the method.

### Example: Using Dependency Injection

Below is an example of an assembler with a non-static transformation method that uses dependency injection to format a
user’s name via an injected service.

```php
<?php

namespace App\Assemblers;

use App\DTOs\UserDTO;
use App\Services\UserFormattingService;
use YorCreative\LaravelArgonautDTO\ArgonautAssembler;

class UserAssembler extends ArgonautAssembler
{
    public function __construct(protected UserFormattingService $formattingService) 
    {
        //
    }
    
    /**
     * Transform input data into a UserDTO with dependency injection.
     *
     * @param object $input Input data (e.g., from a model or array cast to object).
     * @param UserFormattingService $formatter Injected service for formatting user data.
     * @return UserDTO
     */
    public function toUserDTO(object $input): UserDTO
    {
        return new UserDTO([
            'full_name' => $formattingService->formatName($input->first_name, $input->last_name),
            'email' => $input->email,
            'created_at' => $input->created_at,
        ]);
    }
}
```

### Registering the Assembler

```php
// ServiceProvider
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class YourServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(FormattingServiceInterface::class, function($app) {
            return new FormattingService();
        })
         $this->app->bind(YourArgonautAssembler::clas, function ($app) {
             return new YourArgonautAssembler($app->get(FormattingServiceInterface::class));
         });
    }

    public function provides()
    {
        return [
               YourArgonautAssembler::class,
               FormattingServiceInterface::class
        ]       
    }
}
```

#### Using the Assembler

To use the assembler with dependency injection, you need to provide an instance of the assembler to the `assemble`
method or related methods (`fromCollection`, `fromArray`, or `assembleInstance`). Laravel’s container will resolve the
dependencies when the method is invoked.

```php
<?php

use App\Assemblers\UserAssembler;
use App\DTOs\UserDTO;

// Example input (e.g., a model or object)
$input = (object) [
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john.doe@example.com',
    'created_at' => now(),
];

// Creating an assembler instance
$formattingService = new UserFormattingService();
$assembler = new UserAssembler($formattingService);
// or using the container instance
$assembler = resolve(YourArgonautAssembler::class);

// Pass the $assembler instance 
$userDTO = UserAssembler::assemble($input, UserDTO::class, $assembler);
// Or use the instance method
$userDTO = $assembler->assembleInstance($input, UserDTO::class);

// Transform a collection passing the $assembler instance
$array = [$input, $input];
$collection = collect($array);
$userDTOs = UserAssembler::fromCollection($collection, UserDTO::class, $assembler);
$userDTOs = $assembler::fromArray($array, UserDTO::class, $assembler)
```

In this example:

- The `toUserDTO` method requires a `UserFormattingService` dependency.
- The assembler instance (`$assembler`) is passed to `assemble`, `fromArray` or `fromCollection`, ensuring the
  non-static `toUserDTO` method is invoked on the instance.

---

## Advanced: 🎯 DTOs with Prioritized Attributes and Custom Setters

ArgonautDTO allows you to prioritize the assignment of specific fields using `$prioritizedAttributes`, which is critical
for cases where one field influences others.

```php
class UserDTO extends ArgonautDTO
{
    public ?string $firstName = null;
    public ?string $lastName = null;
    public string $username;
    public string $email;
    public ?string $fullName = null;

    protected array $prioritizedAttributes = ['firstName', 'lastName'];

    protected array $casts = [
        'firstName' => 'string',
        'lastName' => 'string',
        'username' => 'string',
        'email' => 'string',
        'fullName' => 'string',
    ];

    public function setFirstName($value)
    {
        $this->firstName = $value;
        $this->fullName = $this->firstName . ' ' . $this->lastName;
    }

    public function setLastName($value)
    {
        $this->lastName = $value;
        $this->fullName = $this->firstName . ' ' . $this->lastName;
    }

    public function rules(): array
    {
        return [
            'firstName' => ['nullable', 'string', 'max:32'],
            'lastName' => ['nullable', 'string', 'max:32'],
            'username' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
        ];
    }
}
```

---

## 🔁 Casting Reference

Casting allows you to automatically transform values into other DTOs, Laravel Collections, arrays, dates, and more.

```php
protected array $casts = [
    'registeredAt' => \Illuminate\Support\Carbon::class,
    'profile' => ProfileDTO::class,
    'roles' => [RoleDTO::class],
    'permissions' => Collection::class . ':' . PermissionDTO::class,
];
```

| Cast Type          | Example                                       | Description                      |
|--------------------|-----------------------------------------------|----------------------------------|
| Scalar             | `'string'`, `'int'`, etc.                     | Native PHP type cast             |
| Single DTO         | `ProfileDTO::class`                           | Cast an array to a DTO instance  |
| Array of DTOs      | `[RoleDTO::class]`                            | Cast to array of DTOs            |
| Collection of DTOs | `Collection::class . ':' . CommentDTO::class` | Cast to a Laravel Collection     |
| Date casting       | `Carbon::class`                               | Cast to Carbon/DateTime instance |

---

## ✅ Validation

Validate DTOs with Laravel’s validator:

```php
$userDTO->validate();         // Throws ValidationException
$userDTO->validate(false);    // Returns array of errors (non-throwing)
$userDTO->isValid();          // Returns true/false
```

---

## 📤 Serialization

Serialize DTOs for output, API responses, etc.

```php
$userDTO->toArray(); // Recursively converts nested DTOs
$userDTO->toJson();  // JSON output (throws on encoding errors)
```

---

## 🛠️ DTO Collection Helper

Create DTO collections directly:

```php
UserDTO::collection([
    ['username' => 'john', 'email' => 'john@example.com'],
]);
```

---

## 🧪 Testing

Run the test suite using:

```bash
composer test
```

---

## 📚 Credits

- [Yorda](https://github.com/yordadev)
- [All Contributors](../../contributors)

---

## 📃 License

This package is open-sourced software licensed under the [MIT license](LICENSE).