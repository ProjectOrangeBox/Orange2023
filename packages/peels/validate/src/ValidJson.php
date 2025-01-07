<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\Filter;
use peels\validate\WildNotation;
use peels\validate\exceptions\RuleFailed;
use orange\framework\interfaces\InputInterface;
use peels\validate\interfaces\ValidateInterface;

class ValidJson extends Filter
{
    protected ValidateInterface $validateService;
    protected InputInterface $inputService;

    protected function __construct(ValidateInterface $validate, InputInterface $input)
    {
        $this->validateService = $validate;
        $this->inputService = $input;
    }

    public function value(mixed $value, string|array $rules): mixed
    {
        if (is_array($rules)) {
            foreach ($rules as $rule) {
                $this->runRule($value, $rule);
            }
        } else {
            $this->runRule($value, $rules);
        }

        return true;
    }
    /**
     * validateJson[isString(person.name.first)]
     * validateJson[isArray(person.children)]
     * validateJson[isString(person.children.*.name.first)]
     * validateJson[isCountLessThan(person.children),20]
     * validateJson[isOneOf(person.color),red,green,blue]
     */
    protected function runRule(mixed $json, string $rule): mixed
    {
        if (is_string($json)) {
            $json = json_decode($json, true);
        } else {
            $json = (array)$json;
        }

        if (!is_object($json) && !is_array($json)) {
            throw new RuleFailed('%s is not a valid JSON');
        }

        preg_match('/(?<rule>[^\(]+)\((?<dot>[^\)]+)\),*(?<options>.*)/i', $rule, $matches, PREG_OFFSET_CAPTURE, 0);

        $dotNotation = $matches['dot'][0];

        $rule = $matches['rule'][0] . '[' . $matches['options'][0] . ']';

        $value = (new WildNotation($json))->get($dotNotation);

        /**
         * returns an array
         * isArray(people.*)
         *
         * foreach
         * isBool(people.*.male)
         */
        if (is_array($value) && substr($dotNotation, -1) != '*') {
            foreach ($value as $v) {
                // throws exception on fail
                // returns value on success
                $this->validateService->throwExceptionOnFailure(true)->input($v, $rule);
            }
        } else {
            // throws exception on fail
            // returns value on success
            $this->validateService->throwExceptionOnFailure(true)->input($value, $rule);
        }

        return $json;
    }
}
