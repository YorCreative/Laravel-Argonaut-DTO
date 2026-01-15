<?php

namespace YorCreative\LaravelArgonautDTO\Traits;

use Illuminate\Translation\ArrayLoader;
use Illuminate\Translation\Translator;
use Illuminate\Validation\Factory;
use Illuminate\Validation\ValidationException;
use LogicException;
use Throwable;

trait HasValidation
{
    protected static ?Factory $validatorFactory = null;

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
