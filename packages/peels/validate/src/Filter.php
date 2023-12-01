<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\exceptions\InvalidValue;
use dmyers\orange\interfaces\InputInterface;
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

    protected ValidateInterface $validate;
    protected InputInterface $input;

    protected mixed $default = null;
    protected string $rules = '';

    public function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validate = $validate;
        $this->input = $input;
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
        if (in_array($name,['hasError','hasErrors','error','errors','throwErrorOnFailure','addRule','addRules'])) {
            return $this->validate->$name(...$arguments);
        } else {
            // allow these to pass though to input
            $type = strtolower($name);
            $key = $arguments[0] ?? null;
            $rules = $arguments[1] ?? '';
            $default = $arguments[2] ?? null;

            $value = $this->input->$type($key, $default);

            return $this->input($value, $rules);
        }
    }

    /**
     * $value = $filtered->keyname;
     * $value = $filtered->withDefault(true)->keyname;
     * $value = $filtered->withRules('isString')->keyname;
     * $value = $filtered->withRules('isString')->withDefault('empty')->keyname;
     */
    public function __get(string $name): mixed
    {
        $rules = $this->rules;

        // reset them
        $this->rules = '';

        return $this->input($this->input->$name, $rules);
    }

    public function withDefault($default): self
    {
        $this->input->withDefault($default);

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
     */
    public function input(mixed $input, string $rules): mixed
    {
        return $this->validate->throwErrorOnFailure(true)->input($input, $rules)->value();
    }
}
