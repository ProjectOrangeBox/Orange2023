<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;
use peels\validate\interfaces\FilterInterface;
use peels\validate\interfaces\ValidateInterface;

/**
 * Class to pull data from input with validation & filtering rules as well as support for default values
 *
 * additionally provides a method to "remap" input as needed
 */
class Filter implements FilterInterface
{
    private static FilterInterface $instance;

    protected ValidateInterface $validateService;
    protected InputInterface $inputService;

    protected mixed $default = null;
    protected string $rules = '';

    public function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validateService = $validate;
        $this->inputService = $input;
    }

    public static function getInstance(ValidateInterface $validate, InputInterface $input): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($validate, $input);
        }

        return self::$instance;
    }

    /**
     * $value = $filter->post('name','isString','Johnny');
     */
    public function __call(string $name, array $arguments): mixed
    {
        // allow these to pass thru to validate
        if (in_array($name, ['hasError','hasErrors','error','errors','throwErrorOnFailure','addRule','addRules'])) {
            return $this->validateService->$name(...$arguments);
        } else {
            // allow these to pass though to input
            $type = strtolower($name);
            $key = $arguments[0] ?? null;
            $rules = $arguments[1] ?? '';
            $default = $arguments[2] ?? null;

            $value = $this->inputService->$type($key, $default);

            return $this->input($value, $rules);
        }
    }

    /**
     * $value = $filtered->keyname; - just like input
     * $value = $filtered->withDefault(true)->keyname; - just like input with default value if no matching array key is found
     * $value = $filtered->withRules('isString')->keyname; - run a filter on matching array key value
     * $value = $filtered->withRules('isString')->withDefault('empty')->keyname; -- run a filter on a matching array key value or default if key if no matching array key is found
     */
    public function __get(string $name): mixed
    {
        $rules = $this->rules;

        // reset them
        $this->rules = '';

        return $this->input($this->inputService->$name, $rules);
    }

    public function withDefault($default): self
    {
        $this->inputService->withDefault($default);

        return $this;
    }

    public function withRules($rules): self
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * Single value filter
     * $filter->input($foobar,'isString');
     *
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     */
    public function input(mixed $input, string $rules): mixed
    {
        return $this->validateService->throwErrorOnFailure(true)->input($input, $rules)->value();
    }
}
