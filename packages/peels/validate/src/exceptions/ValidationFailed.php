<?php

declare(strict_types=1);

namespace peels\validate\exceptions;

use Throwable;
use peels\validate\exceptions\ValidateException;

class ValidationFailed extends ValidateException
{
    // place to store the errors attached to me
    protected array $errors = [];

    // default http 406 - Not Acceptable
    public function __construct($message = '', $code = 406, Throwable $previous = null, ?array $errors = null)
    {
        if (is_array($errors)) {
            $this->merge($errors);
        }

        parent::__construct($message, $code, $previous);
    }

    // for the error / exception handler
    public function getHttpCode(): int
    {
        // return my error number to error exception handler
        return $this->getCode();
    }

    // for the error / exception handler
    public function getOutput(): string
    {
        // return the JSON payload as a string to the error exception handler
        return $this->getJson();
    }

    public function getArray(): array
    {
        return [
            'has' => $this->hasErrors(),
            'count' => count($this->errors),
            'errors' => $this->getErrors(),
            'keys' => $this->getKeys(),
            'array' => $this->getErrorsAsArray(),
            'html' => $this->getErrorsAsHtml(),
            'text' => $this->getErrorsAsText(),
            'json' => $this->getErrorsAsJson(),
        ];
    }

    public function getJson(): string
    {
        return json_encode($this->getArray());
    }

    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    public function getErrors(): array
    {
        return $this->getErrorsAsArray();
    }

    public function getErrorsAsHtml(string $prefix = '', string $suffix = '', string $separator = PHP_EOL): string
    {
        $elements = [];

        foreach ($this->errors as $error) {
            $elements[] = $prefix . $error->text . $suffix;
        }

        return implode($separator, $elements);
    }

    public function getErrorsAsJson(bool $raw = true, int $flags = null, int $depth = 512): string
    {
        $value = $raw ? $this->errors : $this->getErrorsAsHtml();

        return json_encode($value, $flags ?? JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE, $depth);
    }

    public function getErrorsAsText(bool $raw = true, int $depth = 512): string
    {
        return $this->getErrorsAsJson($raw, JSON_PRETTY_PRINT, $depth);
    }

    public function getErrorsAsArray(): array
    {
        return $this->errors;
    }

    public function getKeys(): array
    {
        $keys = [];

        foreach ($this->errors as $error) {
            $keys[] = $error->key;
        }

        return $keys;
    }

    /**
     * Merge other errors with this instance
     */
    public function merge(array|ValidationFailed $errors): self
    {
        if (!is_array($errors)) {
            $errors = $errors->getErrors();
        }

        $this->errors += $errors;

        return $this;
    }
}
