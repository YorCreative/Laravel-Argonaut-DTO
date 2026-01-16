<?php

namespace YorCreative\LaravelArgonautDTO;

use ReflectionProperty;
use YorCreative\LaravelArgonautDTO\Traits\HasCasting;
use YorCreative\LaravelArgonautDTO\Traits\HasSerialization;
use YorCreative\LaravelArgonautDTO\Traits\HasValidation;

/**
 * Abstract base class for creating immutable Data Transfer Objects.
 *
 * This class provides a foundation for DTOs where properties cannot be modified
 * after construction. Properties should be declared with the `readonly` modifier.
 *
 * Example usage:
 * ```php
 * class UserDTO extends ArgonautImmutableDTO
 * {
 *     public readonly string $username;
 *     public readonly string $email;
 *     public readonly ?Carbon $registeredAt;
 *
 *     protected array $casts = [
 *         'registeredAt' => Carbon::class,
 *     ];
 * }
 *
 * $user = new UserDTO(['username' => 'jdoe', 'email' => 'j@example.com']);
 * // $user->username = 'other'; // ERROR: Cannot modify readonly property
 * ```
 */
abstract class ArgonautImmutableDTO implements ArgonautDTOContract
{
    use HasCasting;
    use HasSerialization;
    use HasValidation;

    /**
     * Creates a new immutable DTO instance from the given attributes.
     *
     * @param  array  $attributes  An associative array of attributes to initialize the DTO with.
     */
    public function __construct(array $attributes)
    {
        $this->initializeFromAttributes($attributes);
    }

    /**
     * Initializes readonly properties from the provided attributes array.
     *
     * This method iterates through the attributes, applies casting where defined,
     * and uses reflection to set readonly properties during construction.
     *
     * @param  array  $attributes  The attributes to initialize properties from.
     */
    protected function initializeFromAttributes(array $attributes): void
    {
        foreach ($attributes as $key => $value) {
            if (property_exists($this, $key) && ! $this->isInternalProperty($key)) {
                $castValue = ! is_null($value) ? $this->castInputValue($key, $value) : null;
                $this->initializeReadonlyProperty($key, $castValue);
            }
        }
    }

    /**
     * Determines if a property is an internal trait/class property that should not be set from attributes.
     *
     * @param  string  $key  The property name to check.
     * @return bool True if the property is internal, false otherwise.
     */
    protected function isInternalProperty(string $key): bool
    {
        return in_array($key, ['casts', 'nestedAssemblers', 'validatorFactory']);
    }

    /**
     * Sets a readonly property value using reflection.
     *
     * This method uses PHP's Reflection API to set readonly properties during
     * object construction. This is the only way to set readonly properties
     * from a parent class, as readonly properties can only be assigned once
     * and typically only within the declaring class.
     *
     * @param  string  $key  The property name to set.
     * @param  mixed  $value  The value to assign to the property.
     */
    protected function initializeReadonlyProperty(string $key, mixed $value): void
    {
        $property = new ReflectionProperty($this, $key);
        $property->setValue($this, $value);
    }
}
