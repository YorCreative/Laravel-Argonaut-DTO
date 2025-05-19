<?php

namespace YorCreative\LaravelArgonautDTO;

use DateTimeInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use Throwable;

class ArgonautDTO implements ArgonautDTOContract
{
    protected array $prioritizedAttributes = [];

    protected array $casts = [];

    protected static ?Factory $validatorFactory = null;

    public function __construct(array $attributes)
    {
        $this->setAttributes($attributes);
    }

    /**
     * Sets multiple attributes on the object, prioritizing specific keys if present.
     *
     * @param  array  $attributes  An associative array of attributes to set, where keys represent attribute names and values represent their corresponding values.
     * @return static The current instance with the updated attributes.
     */
    public function setAttributes(array $attributes): static
    {
        foreach ($this->prioritizedAttributes as $key) {
            if (array_key_exists($key, $attributes)) {
                $this->setAttribute($key, Arr::pull($attributes, $key));
            }
        }

        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }

        return $this;
    }

    /**
     * Sets the value of a specified attribute on the instance.
     *
     * @param  string  $key  The name of the attribute to set.
     * @param  mixed  $value  The value to assign to the attribute.
     * @return static The current instance after updating the attribute.
     */
    public function setAttribute(string $key, mixed $value): static
    {
        $method = 'set'.Str::studly($key);

        if (method_exists($this, $method)) {
            $this->$method($value);
        } elseif (property_exists($this, $key)) {
            $this->$key = ! is_null($value) ? $this->castInputValue($key, $value) : null;
        }

        return $this;
    }

    /**
     * Retrieves the attributes of the object that should be updated, excluding specific properties.
     *
     * @return array The filtered attributes to update.
     */
    public function getAttributesToUpdate(): array
    {
        $attributes = get_object_vars($this);
        unset($attributes['prioritizedAttributes'], $attributes['casts']);

        return $attributes;
    }

    /**
     * Casts the input value to a specific type based on the defined cast rules for the given key.
     *
     * @param  string  $key  The key associated with the value to be cast.
     * @param  mixed  $value  The value to be cast.
     * @return mixed The result of casting the value, or the original value if no casting is applicable.
     */
    protected function castInputValue(string $key, mixed $value): mixed
    {
        $cast = $this->casts[$key] ?? null;

        return match (true) {
            is_string($cast) && str_starts_with($cast, Collection::class.':') => $this->castToCollectionModel($cast, $value),

            is_array($cast) && class_exists($cast[0]) => $this->castToArrayOfModels($cast[0], $value),

            is_string($cast) && class_exists($cast) => $this->castToSingleModel($cast, $value),

            default => $value,
        };
    }

    /**
     * Casts an array of values to a collection of a specified model.
     *
     * @param  string  $cast  The casting directive, containing the class name to which the items should be cast.
     * @param  string|array  $value  The value to be cast, expected to be an array.
     * @return Collection A collection of items cast to the specified class.
     *
     * @throws InvalidArgumentException If the provided value is not an array.
     */
    protected function castToCollectionModel(string $cast, string|array $value): Collection
    {
        [$_, $class] = explode(':', $cast, 2);

        if (gettype($value) !== 'array') {
            throw new InvalidArgumentException("$value must be an array to cast to a collection.");
        }

        return collect($value)->map(fn ($item) => $item instanceof $class ? $item : new $class($item));
    }

    /**
     * Converts an array of items into an array of model instances of the specified class.
     *
     * @param  string  $class  The fully qualified class name of the model to cast items to.
     * @param  array  $value  The array of items to cast into instances of the specified class.
     * @return array An array of instances of the specified class.
     */
    protected function castToArrayOfModels(string $class, array $value): array
    {
        return array_map(fn ($item) => $item instanceof $class ? $item : new $class($item), $value);
    }

    /**
     * Casts a single value into an instance of the specified class.
     *
     * @param  string  $class  The fully qualified class name of the target model or type to cast the value to.
     * @param  mixed  $value  The value to cast into an instance of the specified class or type.
     * @return mixed An instance of the specified class or type, or the original value if already appropriately cast.
     */
    protected function castToSingleModel(string $class, mixed $value): mixed
    {
        if (is_a($class, DateTimeInterface::class, true)) {
            return $value instanceof DateTimeInterface ? $value : new Carbon($value);
        }

        return $value instanceof $class ? $value : new $class($value);
    }

    /**
     * Processes the given value by converting objects implementing ArgonautDTOContract to arrays,
     * recursively processing collections or arrays, or returning the value as is.
     *
     * @param  mixed  $value  The value to be processed. Can be an object, array, or any data type.
     * @param  int  $depth  The maximum depth of recursion when converting objects to arrays. Defaults to 3.
     * @return mixed The processed value, either as an array or in its original form.
     */
    protected function castOutputValue(mixed $value, int $depth = 3): mixed
    {

        if ($value instanceof ArgonautDTOContract) {
            return $value->toArray($depth);
        }

        if ($value instanceof Collection || is_array($value)) {
            return collect($value)
                ->map(fn ($v) => $this->castOutputValue($v, $depth))
                ->all();
        }

        return $value;
    }

    /**
     * Converts the current object into an array, optionally with a specified depth for nested structures.
     *
     * @param  int  $depth  The maximum depth to process when converting nested structures. Defaults to 3. A depth of 0 or less will return an empty array.
     * @return array An array representation of the current object, respecting the specified depth.
     */
    public function toArray(int $depth = 3): array
    {
        if ($depth <= 0) {
            return [];
        }

        return collect(get_object_vars($this))
            ->reject(fn ($_, $key) => in_array($key, ['prioritizedAttributes', 'casts']))
            ->mapWithKeys(fn ($value, $key) => [$key => $this->castOutputValue($value, $depth - 1)])
            ->all();
    }

    /**
     * Converts the current object to a JSON-encoded string.
     *
     * @param  int  $options  Optional JSON encoding options as accepted by json_encode. Defaults to 0.
     * @return string The JSON representation of the object.
     *
     * @throws RuntimeException If a JSON encoding error occurs.
     */
    public function toJson($options = 0): string
    {
        $json = json_encode($this->toArray(), $options);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        throw new RuntimeException('JSON error: '.json_last_error_msg());
    }

    /**
     * Creates a collection of instances of the static class from an array of items.
     *
     * @param  array  $items  An array of items to be converted into a collection of instances.
     * @return Collection A collection of instances of the static class.
     */
    public static function collection(array $items = []): Collection
    {
        return collect($items)->map(fn (array $item) => new static($item));
    }

    /**
     * Creates or retrieves a singleton instance of the validation factory.
     *
     * @return Factory The validation factory instance.
     */
    protected function makeValidator(): Factory
    {
        if (static::$validatorFactory) {
            return static::$validatorFactory;
        }

        $loader = new ArrayLoader;
        $translator = new Translator($loader, 'en');

        return static::$validatorFactory = new Factory($translator);
    }

    /**
     * Validates the current instance against the defined validation rules.
     *
     * @param  bool  $throw  Determines whether to throw an exception on validation failure.
     *                       If true, a ValidationException is thrown upon failure;
     *                       if false, validation errors are returned as an array.
     * @return bool|array Returns true if validation succeeds. Returns an array of validation errors if validation fails and $throw is false.
     *
     * @throws LogicException If the class does not implement a rules() method for validation.
     * @throws ValidationException If validation fails and $throw is true.
     */
    public function validate(bool $throw = true): bool|array
    {
        if (! method_exists($this, 'rules')) {
            throw new LogicException(static::class.' must implement a rules() method for validation.');
        }

        $validatorFactory = $this->makeValidator();
        $validator = $validatorFactory->make($this->toArray(), $this->rules());

        if ($validator->fails()) {
            if ($throw) {
                throw new ValidationException($validator);
            }

            return $validator->errors()->toArray();
        }

        return true;
    }

    /**
     * Determines if the current instance is valid by performing validation checks.
     *
     * @param  bool  $throw  Whether to throw an exception upon validation failure.
     *                       If set to true, exceptions will be thrown for validation errors.
     * @return bool True if the instance is valid, otherwise false.
     */
    public function isValid(bool $throw = false): bool
    {
        try {
            return $this->validate(throw: $throw) === true;
        } catch (Throwable) {
            return false;
        }
    }
}
