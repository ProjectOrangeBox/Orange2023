<?php

declare(strict_types=1);

namespace peels\validate;

use peels\validate\exceptions\InvalidValue;
use dmyers\orange\interfaces\InputInterface;
use peels\validate\interfaces\RemapInterface;

/**
 * Class to pull data from input with validation & filtering rules as well as support for default values
 * 
 * additionally provides a method to "remap" input as needed
 */
class Remap implements RemapInterface
{
    private static $instance;

    protected InputInterface $input;

    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    public static function getInstance(InputInterface $input): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($input);
        }

        return self::$instance;
    }

    /**
     * remap input array keys
     */
    public function input(string $method, string $mapping): array
    {
        $method = strtolower($method);
        
        $array = $this->input->$method();

        if (!empty($mapping)) {
            $array = $this->array($array, $mapping);

            $this->input->replace([$method => $array]);
        }

        // also send back remapped
        return $array;
    }

    /**
     * remap
     *
     * mapping fname>first_name|last_name<lname|fullname=first_name|fullname>#|new<=concat($first_name," ",$last_name)|foo<=substr($phone,0,3)
     *
     * Rename array key "A" to array key "B" : A>B
     * Rename array key "B" to array key "A" : A<B
     * Copy from array key "A" into array key "B" : A=B
     * Delete array key "A" : A>#
     *
     * Perform "calculation" (like excel)
     * functions called must be global or called statically
     *
     * A<=concat($fielda,' ',$fieldb)
     * B<=trim($fieldb)
     * =substr($fielda,0,4)>A
     *
     * the variables are the "extracted" keys from the $input
     *
     * so if your input is $input = ['foo'=>1,'bar'=>2];
     * then in your formula $foo and $bar are available
     *
     *
     */
    public function array(array $array, string $mapping): array
    {
        $re = ';(?<section1>^[=]?[^<=>]+)(?<operator>[<=>]{1})(?<section2>.+);';
        $matches = [];

        foreach (explode('|', $mapping) as $seg) {
            preg_match($re, $seg, $matches, 0, 0);

            if (count($matches) != 7) {
                throw new InvalidValue('Remap input error "' . $seg . '".');
            }

            $section1 = $matches['section1'];
            $section2 = $matches['section2'];

            $section1Value = $array[$section1] ?? '';
            $section2Value = $array[$section2] ?? '';

            // handle formulas
            if (substr($section1, 0, 1) == '=') {
                $section1Value = $this->formula(substr($section1, 1), $array);
            }

            if (substr($section2, 0, 1) == '=') {
                $section2Value = $this->formula(substr($section2, 1), $array);
            }

            // handle operators
            switch ($matches['operator']) {
                    // move seg 1 to seg 2
                case '>':
                    $array[$section2] = $section1Value;

                    unset($array[$section1]);
                    break;
                    // move seg 2 to seg 1
                case '<':
                    $array[$section1] = $section2Value;

                    unset($array[$section2]);
                    break;
                    // copy seg 1 to seg 2
                case '=':
                    $array[$section1] = $section2Value;
                    break;
                default:
                    throw new InvalidValue('Unknown remap operator "' . $matches['operator'] . '" in "' . $seg . '".');
            }

            // anything sent to # is deleted
            unset($array['#']);
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
    protected function formula($logic, $arguments): mixed
    {
        // create a closure in it's own jailed box
        $closure = eval('return function($arguments){extract($arguments);return(' . $logic . ');};');

        // call the closure
        return $closure($arguments);
    }
}
