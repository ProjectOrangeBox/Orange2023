<?php

declare(strict_types=1);

namespace peels\validate\exceptions;

use Exception;
use Throwable;

class ValidationFailed extends Exception
{
    protected array $errors = [];

    public function __construct($message = '', $code = 0, Throwable $previous = null, array $errors = [])
    {
        $this->errors = $errors;

        parent::__construct($message, $code, $previous);
    }

    public function hasErrors(): bool
    {
        return (count($this->errors) > 0);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getErrorsAsHtml(string $prefix = '', string $suffix = '', string $separator = ''): string
    {
        $elements = [];

        foreach ($this->errors as $error) {
            $elements[] = $prefix . $error->text . $suffix;
        }

        return implode($separator, $elements);
    }

    public function getErrorsAsJson(bool $raw = true, int $jsonOptions = 0): string
    {
        $jsonOptions = ($jsonOptions != 0) ? $jsonOptions : JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE;

        $json = ($raw) ? $this->errors : $this->getErrorsAsHtml();

        return json_encode($json, $jsonOptions);
    }

    public function getErrorsAsText(bool $raw = true): string
    {
        $json = ($raw) ? $this->errors : $this->getErrorsAsHtml();

        return json_encode($json, JSON_PRETTY_PRINT);
    }

    public function getKeys(): array
    {
        $keys = [];

        foreach ($this->errors as $error) {
            $keys[] = $error->key;
        }

        return $keys;
    }

    public function merge(ValidationFailed $errors): self
    {
        $this->errors += $errors->getErrors();

        return $this;
    }
}
