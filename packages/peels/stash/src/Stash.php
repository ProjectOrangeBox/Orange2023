<?php

declare(strict_types=1);

namespace peels\stash;

use peels\stash\StashInterface;
use peels\session\SessionInterface;
use orange\framework\exceptions\InvalidValue;
use orange\framework\interfaces\InputInterface;

class Stash implements StashInterface
{
    private StashInterface $instance;
    protected InputInterface $inputService;
    protected SessionInterface $sessionService;

    protected string $stashKey = '__#stash#__';

    public function __construct(SessionInterface $session, InputInterface $input)
    {
        $this->sessionService = $session;
        $this->inputService = $input;
    }

    public static function getInstance(SessionInterface $session, InputInterface $input): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($session, $input);
        }

        return self::$instance;
    }

    public function push(): self
    {
        $this->sessionService->set($this->stashKey, $this->inputService->copy());

        return $this;
    }

    public function apply(): bool
    {
        $stashed = null;

        if ($this->sessionService->has($this->stashKey)) {
            $stashed = $this->sessionService->get($this->stashKey);

            $this->sessionService->remove($this->stashKey);

            if (is_array($stashed)) {
                $this->inputService->replace($stashed);
            } else {
                throw new InvalidValue('Stashed input was not an array.');
            }
        }

        // returns false on fail or no stashed data
        return is_array($stashed);
    }
}
