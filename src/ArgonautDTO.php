<?php

namespace YorCreative\LaravelArgonautDTO;

use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;

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
     * Attribute Handling
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

    public function getAttributesToUpdate(): array
    {
        $attributes = get_object_vars($this);
        unset($attributes['prioritizedAttributes'], $attributes['casts']);

        return $attributes;
    }

    /**
     * Casting
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

    protected function castToCollectionModel(string $cast, string|array $value): Collection
    {
        [$_, $class] = explode(':', $cast, 2);

        if (gettype($value) !== 'array') {
            throw new \InvalidArgumentException("$value must be an array to cast to a collection.");
        }

        return collect($value)->map(fn ($item) => $item instanceof $class ? $item : new $class($item));
    }

    protected function castToArrayOfModels(string $class, array $value): array
    {
        return array_map(fn ($item) => $item instanceof $class ? $item : new $class($item), $value);
    }

    protected function castToSingleModel(string $class, mixed $value): mixed
    {
        if (is_a($class, \DateTimeInterface::class, true)) {
            return $value instanceof \DateTimeInterface ? $value : new Carbon($value);
        }

        return $value instanceof $class ? $value : new $class($value);
    }

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
     * Serialization
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

    public function toJson($options = 0): string
    {
        $json = json_encode($this->toArray(), $options);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $json;
        }

        throw new \RuntimeException('JSON error: '.json_last_error_msg());
    }

    public static function collection(array $items = []): Collection
    {
        return collect($items)->map(fn (array $item) => new static($item));
    }

    /**
     * Validation
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

    public function validate(bool $throw = true): bool|array
    {
        if (! method_exists($this, 'rules')) {
            throw new \LogicException(static::class.' must implement a rules() method for validation.');
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

    public function isValid(bool $throw = false): bool
    {
        try {
            return $this->validate(throw: $throw) === true;
        } catch (\Throwable) {
            return false;
        }
    }
}
