<?php

declare(strict_types=1);

namespace peels\validate;

use orange\framework\Log;
use peels\validate\exceptions\InvalidValue;
use peels\validate\interfaces\RemapInterface;
use orange\framework\interfaces\InputInterface;
use orange\framework\base\Singleton;

/**
 * Class to pull data from input with validation & filtering rules as well as support for default values
 *
 * additionally provides a method to "remap" input as needed
 */
class Remap extends Singleton implements RemapInterface
{
    protected string $devNullKey = '#';
    protected string $delimiter = '|';

    protected InputInterface $inputService;

    protected function __construct(InputInterface $input)
    {
        $this->inputService = $input;
    }

    /**
     * remap input/request - array keys
     */
    public function request(string $method, array|string $mapping): array
    {
        $method = strtolower($method);

        // get the entire matching input type array
        // throws error if unavailable
        // remap the array
        $inputArray = $this->array($this->inputService->$method(), $mapping);

        // put the new mapping back in the input service
        $this->inputService->replace([$method => $inputArray]);

        // also send back remapped
        return $inputArray;
    }

    /**
     * remap
     *
     * mapping fname>first_name|last_name<lname|fullname=first_name|fullname>#|new<@concat($first_name," ",$last_name)|foo<@substr($phone,0,3)
     *
     * Rename array key "A" to array key "B" : A>B
     * Rename array key "B" to array key "A" : A<B
     * Copy from array key "A" into array key "B" : A=B
     * Delete array key "A" : A>#
     *
     * Perform "calculation" (like excel)
     * functions called must be global or called statically
     *
     * A<@concat(fielda,' ',fieldb)
     * B<@trim(fieldb)
     * @substr(fielda,0,4)>A
     *
     * the variables are the "extracted" keys from the $input
     *
     * so if your input is $input = ['foo'=>1,'bar'=>2];
     * then in your formula $foo and $bar are available
     *
     *
     */
    public function array(array $array, array|string $mapping): array
    {
        if (is_array($mapping)) {
            $mapping = implode($this->delimiter, $mapping);
        }

        $re = ';(?<section1>^[=]?[^<=>]+)(?<operator>[<=>]{1})(?<section2>.+);';
        $matches = [];

        // split the rules up into sections
        foreach (explode($this->delimiter, $mapping) as $seg) {
            // further break up the rule into segments
            preg_match($re, $seg, $matches, 0, 0);

            // if the regular expression doesn't match 3 segments then there is probably an error
            if (!isset($matches['section1']) || !isset($matches['operator']) || !isset($matches['section2'])) {
                throw new InvalidValue('Remap syntax error in segment "' . $seg . '".');
            }

            logMsg('DEBUG', __METHOD__, ['formula' => $matches['section1'], '~' => $matches['operator'], '~' => $matches['section2']]);

            // ok now let's capture out the segments
            $segment1 = $matches['section1'];
            $segment2 = $matches['section2'];

            // and the values for those segments
            $segment1Value = $array[$segment1] ?? '';
            $segment2Value = $array[$segment2] ?? '';

            // handle any formulas in segment 1 and 2
            if (substr($segment1, 0, 1) == '@') {
                $segment1Value = $this->formula(ltrim($segment1, '@'), $array);
            }

            if (substr($segment2, 0, 1) == '@') {
                $segment2Value = $this->formula(ltrim($segment2, '@'), $array);
            }

            // now let's handle operators
            switch ($matches['operator']) {
                    // move seg 1 to seg 2
                case '>':
                    $array[$segment2] = $segment1Value;

                    unset($array[$segment1]);
                    break;
                    // move seg 2 to seg 1
                case '<':
                    $array[$segment1] = $segment2Value;

                    unset($array[$segment2]);
                    break;
                    // copy seg 1 to seg 2
                case '=':
                    $array[$segment1] = $segment2Value;
                    break;
                default:
                    throw new InvalidValue('Unknown remap operator "' . $matches['operator'] . '" in "' . $seg . '".');
            }

            // anything sent to # (our "/dev/null") is deleted
            unset($array[$this->devNullKey]);
        }

        return $array;
    }

    /**
     * Protected
     */

    /**
     * eval inside a closure (ie. jailed)
     *
     * but still only use developer formulas
     */
    protected function formula($expression, $arguments): mixed
    {
        logMsg('INFO', __METHOD__ . ' formula ' . $expression);
        logMsg('DEBUG', '', ['expression' => $expression, 'arguments' => $arguments]);

        // create a closure in it's own jailed environment
        $closure = eval('return function($arguments){extract($arguments);return(' . $this->makePHPVariables($expression) . ');};');

        // call the closure
        return $closure($arguments);
    }

    protected function makePHPVariables(string $input): string
    {
        logMsg('INFO', __METHOD__ . ' ' . $input);

        $re = ';(?<prefix>[(,])(?<variable>[A-Za-z0-9_]+);';
        $matches = [];

        preg_match_all($re, $input, $matches, PREG_SET_ORDER, 0);

        foreach ($matches as $match) {
            $input = str_replace($match['prefix'] . $match['variable'], $match['prefix'] . '$' . $match['variable'], $input);
        }

        logMsg('INFO', ' > ', ['input' => $input]);

        return $input;
    }
}
