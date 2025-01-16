<?php

declare(strict_types=1);

namespace peels\validate;

use orange\framework\base\Singleton;
use peels\validate\interfaces\FilterInterface;
use orange\framework\interfaces\InputInterface;
use peels\validate\interfaces\ValidateInterface;

/**
 * Class to pull data from input with validation & filtering rules as well as support for default values
 *
 * additionally provides a method to "remap" input as needed
 */
class Filter extends Singleton implements FilterInterface
{
    protected ValidateInterface $validateService;
    protected InputInterface $inputService;

    protected function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validateService = $validate;
        $this->inputService = $input;
    }

    /**
     * Example
     *
     * $value = $filter->post('name', 'castString');
     */
    public function __call(string $method, array $arguments): mixed
    {
        // allow these to pass though to input
        $inputKey = $arguments[0] ?? null;
        $default = $arguments[2] ?? null;
        $rules = $arguments[1] ?? '';

        $method = strtolower($method);

        // copy everything
        // throws error if unavailable so test with inputService->has('post');
        // or something like that before calling __call()
        $inputArray = $this->inputService->$method();

        // if it doesn't exist then use the default
        if (isset($inputArray[$inputKey])) {
            // validate a single value against rules
            $default = $inputArray[$inputKey] = $this->value($inputArray[$inputKey], $rules);

            // put it all back
            $this->inputService->replace([$method => $inputArray]);
        }

        return $default;
    }

    public function request(array $inputKeysRules, string $method = null): array
    {
        if (!$method) {
            // guess
            $method = $this->inputService->requestMethod(true);
        } else {
            $method = strtolower($method);
        }

        $clean = [];

        // get all input for this request type
        $inputArray = $this->inputService->$method();

        // for each key and rule...
        foreach ($inputKeysRules as $inputKeys => $rules) {
            // let's make sure we have something to filter
            $value = $inputArray[$inputKeys] ?? '';

            $clean[$inputKeys] = $inputArray[$inputKeys] = $this->value($value, $rules);
        }

        // put it all back
        $this->inputService->replace([$method => $inputArray]);

        return $clean;
    }

    /**
     * Single value filter
     * $value = $filter->value($foobar,'readable');
     * $value = $filter->value($foobar,['string','maxlength[20]']);
     *
     * This WILL throw an error on fail
     * but these should be "filters" which do not return errors
     * and not validation rules which do return (or throw exceptions) errors
     */
    public function value(mixed $value, string|array $rules): mixed
    {
        return $this->runRule($value, $rules);
    }

    protected function runRule(mixed $value, string|array $rules): mixed
    {
        if (is_array($rules)) {
            $rules = implode($this->validateService->getDelimiters('rule'), $rules);
        }

        // throws exception on fail
        // returns value on success
        return $this->validateService->throwExceptionOnFailure(true)->input($value, $rules)->value();
    }
}
