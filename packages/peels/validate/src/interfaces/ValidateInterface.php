<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface ValidateInterface
{
    public function reset(): self;
    public function getDelimiters(string $needle = ''): string|array;

    public function addRule(string $name, string $class): self;
    public function addRules(array $rules): self;

    public function setCurrentInput(mixed $input): self;
    public function input(mixed $input, array|string $rules, string $human = null): self;

    public function stopProcessing(): self;
    public function throwExceptionOnFailure(): self;

    public function changeNotationDelimiter(string $delimiter): self;
    public function disableNotation(): self;

    // internal error handling - this way we can capture more that 1
    public function addError(string $errorMsg, string $human = '', string $options = '', string $rule = '', string $input = ''): self;
    public function hasError(): bool;
    public function hasErrors(): bool;
    public function error(): string;
    public function errors(): array;
    public function hasNoErrors(): bool;

    public function value(): mixed;
    public function values(): mixed;
}
