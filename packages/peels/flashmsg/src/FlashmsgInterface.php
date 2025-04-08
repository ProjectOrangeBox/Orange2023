<?php

declare(strict_types=1);

namespace peels\flashmsg;

interface FlashMsgInterface
{
    public const DANGER = 'red';
    public const WARN = 'yellow';
    public const INFO = 'blue';
    public const SUCCESS = 'green';

    public function msg(string $msg, string $type = null): self;
    public function msgs(array $array, string $type = null): self;
    public function redirect(string $redirect): void;
    public function getMessages(bool $detailed = false): array;
    public function __debugInfo(): array;
}
