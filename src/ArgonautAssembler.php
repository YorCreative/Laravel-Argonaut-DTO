<?php

namespace YorCreative\LaravelArgonautDTO;

use BadFunctionCallException;
use BadMethodCallException;
use Illuminate\Database\Eloquent\Collection as DatabaseCollection;
use Illuminate\Support\Collection;
use ReflectionException;
use ReflectionMethod;

/**
 * Class ArgonautAssembler
 *
 * A utility class for transforming various data structures (e.g., arrays, objects, collections)
 * into specific classes using dynamic assembly methods. This class provides both static and
 * instance-based assembly mechanisms to enhance flexibility and reuse.
 */
class ArgonautAssembler
{
    /**
     * A static cache to store resolved transformation method names.
     *
     * This array is used to optimize the method resolution process in `static::assemble()`.
     * When a transformation method for a specific assembler class and target DTO class
     * is resolved for the first time (via `static::resolveAssembleMethod()`), its name is stored here.
     * Subsequent calls to `static::assemble()` for the same combination can then retrieve the
     * method name directly from this cache, avoiding repeated resolution logic.
     *
     * The structure is:
     * [
     *     'AssemblerClassName' => [
     *         'TargetDTOClassName' => 'resolvedMethodName',
     *         // ... more target DTOs for this assembler
     *     ],
     *     // ... more assembler classes
     * ]
     *
     * @var array<class-string, array<class-string, string>>
     */
    protected static array $methodMap = [];

    /**
     * Transforms each item in an input Laravel Collection into an instance of a specified target class.
     *
     * This static method iterates over the provided `$items` (an instance of `Illuminate\Support\Collection`
     * or `Illuminate\Database\Eloquent\Collection`). For each item in the collection, it delegates
     * the transformation logic to the `static::assemble()` method, using the `$transformedInputClass`
     * as the target type and passing along the optional `$instance` of the assembler.
     *
     * The result is a new `Illuminate\Support\Collection` containing the transformed items.
     *
     * @param  Collection|DatabaseCollection  $items  The collection of items to be transformed. Each item will be
     *                                                passed as the first argument to `static::assemble()`.
     * @param  string  $transformedInputClass  The fully qualified class name of the target class to which each
     *                                         item in the collection should be transformed (e.g., `\App\DTOs\UserDTO`).
     * @param  self|null  $instance  An optional instance of the assembler (`static::class` or a subclass).
     *                               This is passed to `static::assemble()` for each item and is required if
     *                               the resolved transformation method for `$transformedInputClass` is non-static.
     * @return Collection A new `Illuminate\Support\Collection` containing an instance of `$transformedInputClass`
     *                    for each item from the input collection.
     *
     * @throws ReflectionException Bubbled up from `static::assemble()` if the transformation method cannot be reflected.
     * @throws BadMethodCallException Bubbled up from `static::assemble()` if a non-static method is called without an instance.
     * @throws BadFunctionCallException Bubbled up from `static::assemble()` if the transformation method cannot be resolved by name.
     */
    public static function fromCollection(Collection|DatabaseCollection $items, string $transformedInputClass, ?self $instance = null): Collection
    {
        return $items->map(fn (mixed $item) => static::assemble($item, $transformedInputClass, $instance));
    }

    /**
     * Transforms each item in an input array into an instance of a specified target class, returning them in a Laravel Collection.
     *
     * This static method first converts the input PHP `$items` array into an `Illuminate\Support\Collection`
     * using the `collect()` helper. It then iterates over this newly created collection. For each item,
     * it delegates the transformation logic to the `static::assemble()` method, using the
     * `$transformedInputClass` as the target type and passing along the optional `$instance` of the assembler.
     *
     * The result is a new `Illuminate\Support\Collection` containing the transformed items.
     *
     * @param  array  $items  The array of items to be transformed. Each item will be passed as the
     *                        first argument to `static::assemble()`.
     * @param  string  $transformedInputClass  The fully qualified class name of the target class to which each
     *                                         item in the array should be transformed (e.g., `\App\DTOs\UserDTO`).
     * @param  self|null  $instance  An optional instance of the assembler (`static::class` or a subclass).
     *                               This is passed to `static::assemble()` for each item and is required if
     *                               the resolved transformation method for `$transformedInputClass` is non-static.
     * @return Collection A new `Illuminate\Support\Collection` containing an instance of `$transformedInputClass`
     *                    for each item from the input array.
     *
     * @throws ReflectionException Bubbled up from `static::assemble()` if the transformation method cannot be reflected.
     * @throws BadMethodCallException Bubbled up from `static::assemble()` if a non-static method is called without an instance.
     * @throws BadFunctionCallException Bubbled up from `static::assemble()` if the transformation method cannot be resolved by name.
     */
    public static function fromArray(array $items, string $transformedInputClass, ?self $instance = null): Collection
    {
        return collect($items)->map(fn (mixed $item) => static::assemble($item, $transformedInputClass, $instance));
    }

    /**
     * Assembles an input (object or array) into an instance of a specified target class by dynamically invoking a transformation method.
     *
     * This static method is the primary engine for transforming input data. Its operation involves several steps:
     *
     * 1.  **Method Resolution and Caching**:
     *     It first determines the appropriate transformation method to call on the current assembler class (`static::class`).
     *     It checks a static cache (`static::$methodMap`) for a previously resolved method name, keyed by the assembler
     *     class and the target `$transformedInputClass`.
     *     If a cached method name is not found, it calls `static::resolveAssembleMethod($transformedInputClass)`
     *     to determine the method name based on established conventions (e.g., `toTargetClassName` or `fromTargetClassName`).
     *     The resolved method name is then cached in `static::$methodMap` for subsequent calls involving the same
     *     assembler and target class.
     *
     * 2.  **Input Normalization**:
     *     If the provided `$input` is an array, it is cast to an `(object)`. If it's already an object, it's used as is.
     *
     * 3.  **Method Invocation**:
     *     Using `ReflectionMethod`, it introspects the resolved transformation method:
     *     - If the method is static, it's called statically (e.g., `AssemblerClass::toTargetDTO($objectInput)`).
     *     - If the method is non-static, it's called on the provided `$instance` (e.g., `$instance->toTargetDTO($objectInput)`).
     *       A `BadMethodCallException` is thrown if a non-static method is encountered, but no `$instance` is provided.
     *
     * @param  object|array  $input  The input data to be transformed. If an array, it will be cast to an object.
     * @param  string  $transformedInputClass  The fully qualified class name of the target class into which the input
     *                                         should be transformed (e.g., `\App\DTOs\UserDTO`).
     * @param  self|null  $instance  An optional instance of the assembler (`static::class` or a subclass). This is required
     *                               if the resolved transformation method is non-static. It can be `null` if the
     *                               transformation method is static.
     * @return mixed An instance of `$transformedInputClass`, populated by the invoked transformation method.
     *
     * @throws ReflectionException If the resolved transformation method does not exist on `static::class`,
     *                             is not accessible, or if `ReflectionMethod` fails for other reasons.
     * @throws BadMethodCallException If the resolved transformation method is non-static, but no `$instance`
     *                                is provided to call it on.
     * @throws BadFunctionCallException If `static::resolveAssembleMethod()` is called (because the method was not cached)
     *                                  and it fails to find a suitable transformation method based on the naming conventions.
     */
    public static function assemble(object|array $input, string $transformedInputClass, ?self $instance = null): mixed
    {
        $class = static::class;
        $method = static::$methodMap[$class][$transformedInputClass] ??= static::resolveAssembleMethod($transformedInputClass);
        $objectInput = is_array($input) ? (object) $input : $input;

        $reflectionMethod = new ReflectionMethod($class, $method);
        if ($reflectionMethod->isStatic()) {
            return $class::$method($objectInput);
        } elseif ($instance !== null) {
            return $instance->$method($objectInput);
        } else {
            throw new BadMethodCallException("Cannot call instance method {$method} on [".$class.'] without an instance.');
        }
    }

    /**
     * Assembles an associative array into an instance of a specified target class.
     *
     * This static method serves as a convenience wrapper for the `static::assemble()` method.
     * It accepts an associative array as input, internally casts this array to an (object),
     * and then delegates the actual transformation process to `static::assemble()`, passing along
     * the cast object, the target class name, and an optional assembler instance.
     *
     * @param  array  $input  The associative array containing the data to be transformed. This array is
     *                        cast to an (object) before being processed by `static::assemble()`.
     * @param  string  $transformedInputClass  The fully qualified class name of the target class into which
     *                                         the input array's data should be transformed (e.g., `\App\DTOs\UserDTO`).
     * @param  self|null  $instance  An optional instance of the assembler (or a subclass of `ArgonautAssembler`).
     *                               This instance is passed to `static::assemble()` and is required if the resolved
     *                               transformation method (e.g., "toUserDTO") is non-static. If the transformation
     *                               method is static, this can be `null`.
     * @return mixed An instance of `$transformedInputClass`, populated by the invoked transformation method
     *               via `static::assemble()`.
     *
     * @throws ReflectionException If the resolved transformation method (within `static::assemble()`) cannot be
     *                             reflected upon (e.g., it doesn't exist or is not accessible).
     * @throws BadMethodCallException If a non-static transformation method is resolved (within `static::assemble()`)
     *                                but no `$instance` is provided (or if the provided instance is unsuitable).
     * @throws BadFunctionCallException If `static::resolveAssembleMethod()` (called within `static::assemble()`)
     *                                  cannot find a suitable transformation method (e.g., "toUserDTO" or "fromUserDTO")
     *                                  on the relevant assembler class.
     */
    public static function arrayAssemble(array $input, string $transformedInputClass, ?self $instance = null): mixed
    {
        return static::assemble((object) $input, $transformedInputClass, $instance);
    }

    /**
     * Resolves the name of the transformation method to be used for a given target class.
     *
     * The method name is determined by a convention. It first checks for a method prefixed
     * with "to" followed by the base name (class name without a namespace) of the `$assembledInputClass`
     * (e.g., `toUserDTO` for `\App\DTOs\UserDTO`).
     *
     * If a "to<ClassName>" or "from<ClassName> method is not found, it then checks for a method prefixed with "from"
     * followed by the base name of the `$assembledInputClass` (e.g., `fromUserDTO`).
     *
     * It verifies that a method with one of these resolved names exists on the current static class
     * (i.e., the class on which this `resolveAssembleMethod` is being called, which could be
     * `ArgonautAssembler` or a subclass).
     *
     * @param  string  $assembledInputClass  The fully qualified class name of the target class for which
     *                                       the transformation method name needs to be resolved.
     * @return string The resolved transformation method name (e.g., "toUserDTO" or "fromUserDTO").
     *
     * @throws BadFunctionCallException If neither a "to<ClassName>" nor a "from<ClassName>" method
     *                                  following the naming conventions exists on the current static class (`static::class`).
     */
    protected static function resolveAssembleMethod(string $assembledInputClass): string
    {
        $baseClassName = class_basename($assembledInputClass);
        $toMethod = 'to'.$baseClassName;

        if (method_exists(static::class, $toMethod)) {
            return $toMethod;
        }

        $fromMethod = 'from'.$baseClassName;
        if (method_exists(static::class, $fromMethod)) {
            return $fromMethod;
        }

        throw new BadFunctionCallException("Missing method [{$toMethod}] or [{$fromMethod}] for assembling to {$assembledInputClass} on [".static::class.']');
    }

    /**
     * Assembles an input (object or array) into a specified target class using the current object instance.
     *
     * This is an instance-level convenience method that delegates the transformation logic to the
     * static `assemble` method. It passes the provided `$input` and `$transformedInputClass`
     * along with the current object instance (`$this`) to `static::assemble`.
     *
     * This is particularly useful when the resolved transformation method (e.g., `toUserDTO`)
     * is an instance method and requires access to the assembler's instance properties or methods.
     *
     * @param  object|array  $input  The input data to be transformed. If an array, `static::assemble` will cast it to an object.
     * @param  string  $transformedInputClass  The fully qualified class name of the target class into which the input
     *                                         should be transformed.
     * @return mixed An instance of `$transformedInputClass`, populated by the invoked transformation method
     *               via `static::assemble()`. The transformation method might be static or an instance method
     *               called on the current object (`$this`).
     *
     * @throws ReflectionException If the resolved transformation method (within `static::assemble()`) cannot be reflected upon.
     * @throws BadMethodCallException If a non-static transformation method is resolved (within `static::assemble()`)
     *                                but for some reason `$this` instance is not considered valid (though this is unlikely
     *                                when calling this instance method). More practically, this could bubble up if `assemble`
     *                                internally had an issue.
     * @throws BadFunctionCallException If `static::resolveAssembleMethod()` (called within `static::assemble()`)
     *                                  cannot find a suitable transformation method (e.g., "toUserDTO" or "fromUserDTO").
     */
    public function assembleInstance(object|array $input, string $transformedInputClass): mixed
    {
        return static::assemble($input, $transformedInputClass, $this);
    }
}
