<?php

declare(strict_types=1);

namespace peels\validate\interfaces;

interface ValidateInterface
{
    public function reset(): self;

    public function addRule(string $name, string $class): self;
    public function addRules(array $rules): self;

    public function setInput(mixed $input): self;
    public function input(mixed $input, array|string $rules, string $human = null): self;

    public function stopProcessing(): self;
    public function throwErrorOnFailure(): self;
    
    public function changeNotationDelimiter(string $delimiter): self;
    public function disableNotation(): self;

    public function addError(string $errorMsg, string $human = '', string $options = '', string $rule = '', string $input = ''): self;
    public function hasError(): bool;
    public function hasErrors(): bool;
    public function error(): string;
    public function errors(): array;

    public function value(): mixed;
    public function values(): mixed;
}
