<?php

declare(strict_types=1);

namespace peels\validate\exceptions;

class ValidationFailed extends \Exception
{
    protected array $errors = [];

    public function __construct(string|array $errors = [])
    {
        if (is_string($errors)) {
            $errors = (array)$errors;
        }

        parent::__construct(implode(PHP_EOL, $errors));

        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getJson(int $jsonOptions = 0): string
    {
        return json_encode($this->errors, $jsonOptions);
    }

    public function getHtml(string $prefix = '', string $suffix = ''): string
    {
        $html = '';

        foreach ($this->errors as $err) {
            $html .= $prefix . $err . $suffix;
        }

        return $html;
    }
}
