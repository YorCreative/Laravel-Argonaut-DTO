<?php

namespace YorCreative\LaravelArgonautDTO;

use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Support\Collection;

class ArgonautAssembler
{
    protected static array $methodMap = [];

    /**
     * Transform a Collection or Eloquent Collection of arguments.
     */
    public static function fromCollection(Collection|DatabaseCollection $items, string $transformedInputClass): Collection
    {
        return $items->map(fn (mixed $item) => static::assemble($item, $transformedInputClass));
    }

    /**
     * Transform an array of arguments into a Collection.
     */
    public static function fromArray(array $items, string $transformedInputClass): Collection
    {
        return collect($items)->map(fn (mixed $item) => static::assemble($item, $transformedInputClass));
    }

    /**
     * Dynamically transform an object or array into a specific class.
     *
     * @throws MethodNotImplementedException
     */
    public static function assemble(object|array $input, string $transformedInputClass): mixed
    {
        $class = static::class;
        $method = static::$methodMap[$class][$transformedInputClass] ??= static::resolveAssembleMethod($transformedInputClass);
        $objectInput = is_array($input) ? (object) $input : $input;

        return static::$method($objectInput);
    }

    /**
     * Assemble an associative array using a constructed input class instance.
     *
     * @throws MethodNotImplementedException
     */
    public static function arrayAssemble(array $input, string $transformedInputClass): mixed
    {
        $method = static::resolveAssembleMethod($transformedInputClass);

        return static::$method((object) $input);
    }

    /**
     * Get the method name based on the assembled input class.
     *
     * @throws MethodNotImplementedException
     */
    protected static function resolveAssembleMethod(string $assembledInputClass): string
    {
        $method = 'to'.class_basename($assembledInputClass);

        if (! method_exists(static::class, $method)) {
            throw new \BadFunctionCallException("Missing method [{$method}] for assembling to {$assembledInputClass} on [".static::class.']');
        }

        return $method;
    }
}
