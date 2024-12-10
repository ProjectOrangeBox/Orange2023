<?php

declare(strict_types=1);

namespace peels\validate;

class ValidationError
{
    public function __construct(
        public string $text,
        public string $key,
        public string $msg,
        public string $human,
        public string $options,
        public string $rule,
        public mixed $input
    ) {}
}
