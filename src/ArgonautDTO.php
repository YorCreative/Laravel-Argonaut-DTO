<?php

namespace YorCreative\LaravelArgonautDTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use YorCreative\LaravelArgonautDTO\Traits\HasCasting;
use YorCreative\LaravelArgonautDTO\Traits\HasSerialization;
use YorCreative\LaravelArgonautDTO\Traits\HasValidation;

class ArgonautDTO implements ArgonautDTOContract
{
    use HasCasting;
    use HasSerialization;
    use HasValidation;

    protected array $prioritizedAttributes = [];

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
        unset(
            $attributes['prioritizedAttributes'],
            $attributes['casts'],
            $attributes['nestedAssemblers']
        );

        return $attributes;
    }
}
