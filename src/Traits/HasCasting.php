<?php

namespace YorCreative\LaravelArgonautDTO\Traits;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

trait HasCasting
{
    protected array $casts = [];

    protected array $nestedAssemblers = [];

    /**
     * Casts the given input value based on the defined type or nested assembler for the specified key.
     *
     * @param  string  $key  The key associated with the value to be cast.
     * @param  mixed  $value  The value to be cast, which can be of any type.
     * @return mixed The cast value, potentially transformed into another type based on the cast definition.
     */
    protected function castInputValue(string $key, mixed $value): mixed
    {
        $cast = $this->casts[$key] ?? null;

        $hasNestedAssembler = isset($this->nestedAssemblers[$key]);

        if ($hasNestedAssembler && $cast !== null) {
            $assemblerClass = $this->nestedAssemblers[$key];

            // Determine the target class and cast type upfront for efficiency
            $targetClass = null;
            $isMultiCast = false;

            if (is_string($cast)) {
                if (class_exists($cast)) {
                    $targetClass = $cast;
                } elseif (str_starts_with($cast, Collection::class.':')) {
                    [, $targetClass] = explode(':', $cast, 2);
                    $isMultiCast = true;
                }
            } elseif (is_array($cast) && isset($cast[0]) && class_exists($cast[0])) {
                $targetClass = $cast[0];
                $isMultiCast = true;
            }

            if ($targetClass !== null) {
                if ($isMultiCast) {
                    // For array/collection casts, apply to each item if iterable
                    if (is_array($value)) {
                        $value = array_map(function ($item) use ($assemblerClass, $targetClass) {
                            return (is_array($item) || is_object($item)) ? $assemblerClass::assemble($item, $targetClass) : $item;
                        }, $value);
                    } elseif ($value instanceof Collection) {
                        $value = $value->map(function ($item) use ($assemblerClass, $targetClass) {
                            return (is_array($item) || is_object($item)) ? $assemblerClass::assemble($item, $targetClass) : $item;
                        })->all();
                    }
                    // Skip if not iterable
                } else {
                    // For single casts
                    if (is_array($value) || is_object($value)) {
                        $value = $assemblerClass::assemble($value, $targetClass);
                    }
                    // Skip for scalars
                }
            }
        }

        return match (true) {
            is_string($cast) && str_starts_with($cast, Collection::class.':') => $this->castToCollectionModel($cast, $value),
            is_array($cast) && isset($cast[0]) && class_exists($cast[0]) => $this->castToArrayOfModels($cast[0], $value),
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
}
