<?php

declare(strict_types=1);

namespace peels\validate\rules;

use peels\validate\Notation;
use peels\validate\exceptions\RuleFailed;
use peels\validate\interfaces\ValidateInterface;

abstract class RuleAbstract
{
    protected mixed $input = null;
    protected mixed $options = '';
    // simply for human "grammar" this is a pointer to options
    protected $option;

    protected array $config = [];
    protected ValidateInterface $parent;

    protected string $defaultOptionSeparator = ',';
    // local instance of notation
    protected Notation $notation;

    public function __construct(mixed &$input, string $options, array $config, ValidateInterface $parent)
    {
        // work on by reference
        $this->input = &$input;
        $this->options = $options;
        $this->option = &$this->options;

        $this->config = $config;
        $this->parent = $parent;

        $this->defaultOptionSeparator = $config['defaultOptionSeparator'] ?? $this->defaultOptionSeparator;

        $delimiter = $this->config['notationDelimiter'] ?? '';

        $this->notation = new Notation($delimiter);
    }

    // shared
    protected function convert2bool(): mixed
    {
        $bool = null;

        if (is_bool($this->input)) {
            $bool = $this->input;
        } elseif (is_scalar($this->input)) {
            $val = strtolower((string)$this->input);

            if (in_array($val, $this->config['isTrue'] + $this->config['isFalse'], true)) {
                $bool = in_array($val, $this->config['isTrue'], true);
            }
        }

        return $bool;
    }

    protected function trimLength(int $length = null): self
    {
        if ($length === null) {
            $length = (int)$this->option;
        }

        if ($length > 0) {
            $this->input = substr((string)$this->input, 0, $length);
        }

        return $this;
    }

    // input validation "level 1"
    protected function inputIsBool(string $errorMsg = null): self
    {
        $value = $this->convert2bool();

        if ($value === null) {
            $errorMsg = $errorMsg ?? '%s is not considered a boolean value.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = $value;

        return $this;
    }

    protected function inputIsStringNumber(string $errorMsg = null): self
    {
        if (!is_scalar($this->input) || is_bool($this->input) || $this->input === '') {
            $errorMsg = $errorMsg ?? '%s must be a string or numbers and not empty.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    protected function inputIsNumber(string $errorMsg = null): self
    {
        if (!is_scalar($this->input) || is_bool($this->input) || $this->input === '') {
            $errorMsg = $errorMsg ?? '%s must be numbers and not empty.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    protected function inputIsStringNumberEmpty(string $errorMsg = null): self
    {
        if (!is_scalar($this->input) || is_bool($this->input)) {
            $errorMsg = $errorMsg ?? '%s must be a string or numbers.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    protected function inputIsStringNumberBoolean(string $errorMsg = null): self
    {
        if (!is_scalar($this->input) || $this->input === '') {
            $errorMsg = $errorMsg ?? '%s must be a string, numbers or boolean value.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    protected function inputIsStringNumberBooleanEmpty(string $errorMsg = null): self
    {
        if (!is_scalar($this->input)) {
            $errorMsg = $errorMsg ?? '%s must be a string, numbers, boolean value or empty.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    protected function inputIsArrayObject(string $errorMsg = null): self
    {
        if (!is_array($this->input) || !is_object($this->input) || $this->input === '') {
            $errorMsg = $errorMsg ?? '%s must be a array or object.';

            throw new RuleFailed($errorMsg);
        }

        return $this;
    }

    protected function inputIsInteger(string $errorMsg = null): self
    {
        if (preg_match('/^-?\d+$/', (string)$this->input) !== 1) {
            $errorMsg = $errorMsg ?? '%s must be an integer.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (int)$this->input;

        return $this;
    }

    protected function inputIsRequired(string $errorMsg = null): self
    {
        if ($this->input === '') {
            $errorMsg = $errorMsg ?? 'you must include a option for %s.';

            throw new RuleFailed($errorMsg);
        }

        $this->input = (string)$this->input;

        return $this;
    }

    // option validation
    protected function optionIsInteger(): self
    {
        if (preg_match('/^-?\d+$/', (string)$this->options) !== 1) {
            throw new RuleFailed('%s must be an integer.');
        }

        $this->options = (int)$this->options;

        return $this;
    }

    protected function optionIsRequired(): self
    {
        if (empty($this->options)) {
            throw new RuleFailed('you must include a option for %s.');
        }

        return $this;
    }

    protected function optionDefault(mixed $default): self
    {
        $this->options = (empty($this->option)) ? $default : $this->option;

        return $this;
    }

    // basic filtering
    protected function inputHuman(): self
    {
        $this->input = preg_replace("/[^\\x20-\\x7E]/mi", '', (string)$this->input);

        return $this;
    }

    protected function inputHumanPlus(): self
    {
        $this->input = preg_replace("/[^\\x20-\\x7E\\n\\t\\r]/mi", '', (string)$this->input);

        return $this;
    }

    protected function stripInput($strip): self
    {
        $this->input = str_replace(str_split($strip), '', (string)$this->input);

        return $this;
    }

    protected function toString(): self
    {
        if (is_scalar($this->input)) {
            $this->input = (string)$this->input;
        } else {
            $this->input = '';
        }

        return $this;
    }
}
