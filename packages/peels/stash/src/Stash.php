<?php

declare(strict_types=1);

namespace peels\stash;

use peels\stash\StashInterface;
use peels\session\SessionInterface;
use orange\framework\interfaces\InputInterface;

class Stash implements StashInterface
{
    protected static ?StashInterface $instance = null;

    protected InputInterface $inputService;
    protected SessionInterface $sessionService;

    protected string $stashKey = '__#stash#__';

    protected function __construct(SessionInterface $session, InputInterface $input)
    {
        $this->sessionService = $session;
        $this->inputService = $input;
    }

    public static function getInstance(SessionInterface $session, InputInterface $input): self
    {
        if (self::$instance === null) {
            self::$instance = new self($session, $input);
        }

        return self::$instance;
    }

    public function push(string $name = null): self
    {
        $key = $name ? $this->stashKey . $name : $this->stashKey;

        $this->sessionService->set($key, $this->inputService->copy());

        return $this;
    }

    public function apply(string $name = null): bool
    {
        $key = $name ? $this->stashKey . $name : $this->stashKey;

        $hasStash = false;

        if ($this->sessionService->has($key)) {
            $stashed = $this->sessionService->get($key);

            $this->sessionService->remove($key);

            if (is_array($stashed)) {
                $this->inputService->replace($stashed);
                $hasStash = true;
            } else {
                throw new StashException('Stashed input was not an array.');
            }
        }

        // return false on no stashed data
        return $hasStash;
    }
}
