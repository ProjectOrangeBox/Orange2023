<?php

declare(strict_types=1);

namespace peels\validate\rules;

use peels\validate\rules\RuleAbstract;

/**
 * default rules
 *
 * These can be overridden by providing additional rules in the validation configuration file
 * You can also override these by simply pointing to your own class and method
 */
class Filters extends RuleAbstract
{
    public function date(): void
    {
        $this->toString();

        $timestamp = strtotime($this->input);

        if ($timestamp === false) {
            $this->input = '';
        } else {
            $format = $this->option ?? 'Y-m-d H:i:s';

            $this->input = date($format, $timestamp);
        }
    }

    public function filename(): void
    {
        $this->toString();

        /*
        only word characters - from a-z, A-Z, 0-9, including the _ (underscore) character
        then trim any _ (underscore) characters from the beginning and end of the string
        */
        $this->input = \strtolower(\trim(\preg_replace('#\W+#', '_', $this->input), '_'));

        $this->input = \preg_replace('#_+#', '_', $this->input);
    }

    public function integer(): void
    {
        $this->toString();

        $pos = strpos($this->input, '.');

        if ($pos !== false) {
            $this->input = substr($this->input, 0, $pos);
        }

        $this->input  = preg_replace('/[^\-\+0-9]+/', '', $this->input);

        $prefix = ($this->input[0] == '-' || $this->input[0] == '+') ? $this->input[0] : '';

        $this->input  = $prefix . preg_replace('/[\D]+/', '', $this->input);
    }

    public function length(): void
    {
        $this->toString();

        $length = $this->option ?? 0;

        $this->input = substr($this->input, 0, $length);
    }

    public function number(): void
    {
        $this->toString();

        $this->input = preg_replace('/[^\-\+0-9.]+/', '', $this->input);

        $prefix = '';

        if (isset($this->input[0])) {
            $prefix = ($this->input[0] == '-' || $this->input[0] == '+') ? $this->input[0] : '';
        }

        $this->input = $prefix . preg_replace('/[^0-9.]+/', '', $this->input);
    }

    public function readable(): void
    {
        $this->toString();

        /*
        only word characters - from a-z, A-Z, 0-9, including the _ (underscore) character
        then trim any _ (underscore) characters from the beginning and end of the string
        convert to lowercase
        replace _ (underscore) characters with spaces
        uppercase words
        */
        $this->input = ucwords(str_replace('_', ' ', strtolower(trim(preg_replace('#\W+#', ' ', $this->input), ' '))));

        /* run of spaces */
        $this->input = preg_replace('# +#', ' ', $this->input);
    }

    public function slug(): void
    {
        $this->toString();

        $this->input = preg_replace('~[^\pL\d]+~u', '-', $this->input);
        $this->input = iconv('utf-8', 'us-ascii//TRANSLIT', $this->input);
        $this->input = preg_replace('~[^-\w]+~', '', $this->input);
        $this->input = trim($this->input, '-');
        $this->input = preg_replace('~-+~', '-', $this->input);

        $this->input = strtolower($this->input);
    }

    public function lower(): void
    {
        $this->toString();

        $this->input = strtolower($this->input);
    }

    public function upper(): void
    {
        $this->toString();

        $this->input = strtoupper($this->input);
    }

    public function visible(): void
    {
        $this->toString();

        $this->input = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $this->input);
    }

    public function base64(): void
    {
        $this->toString();

        if ($this->option == 'encode') {
            $this->input = base64_encode($this->input);
        } else {
            $this->input = base64_decode($this->input);
        }
    }

    public function md5(): void
    {
        $this->toString();

        $this->input = md5($this->input);
    }

    public function sha1(): void
    {
        $this->toString();

        $this->input = sha1($this->input);
    }

    public function passwordHash(): void
    {
        $this->toString();

        // throws an exception on fail
        $this->input = password_hash($this->input, PASSWORD_DEFAULT);
    }
}
