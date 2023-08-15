<?php

declare(strict_types=1);

namespace dmyers\orange\interfaces;

interface LogInterface
{
    const EMERGENCY = 1;
    const ALERT = 2;
    const CRITICAL = 4;
    const ERROR = 8;
    const WARNING = 16;
    const NOTICE = 32;
    const INFO = 64;
    const DEBUG = 128;
    const ALL = 255;

    public function changeThreshold(int $threshold): self;
    public function getThreshold(): int;
    public function isEnabled(): bool;
    public function __call($name, $arguments);
}
