<?php

namespace YorCreative\LaravelArgonautDTO\Traits;

use Illuminate\Support\Collection;
use RuntimeException;
use YorCreative\LaravelArgonautDTO\ArgonautDTOContract;

trait HasSerialization
{
    /**
     * Returns the list of internal properties to exclude from serialization.
     *
     * @return array<string> Property names to exclude from toArray() output.
     */
    protected function getExcludedSerializationProperties(): array
    {
        return ['prioritizedAttributes', 'casts', 'nestedAssemblers', 'validatorFactory'];
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

        $excluded = $this->getExcludedSerializationProperties();

        return collect(get_object_vars($this))
            ->reject(fn ($_, $key) => in_array($key, $excluded))
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
}
