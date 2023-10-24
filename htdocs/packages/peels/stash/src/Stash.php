<?php

declare(strict_types=1);

namespace dmyers\stash;

use dmyers\orange\exceptions\InvalidValue;
use dmyers\stash\StashInterface;
use peels\session\sessionInterface;
use dmyers\orange\interfaces\InputInterface;

class Stash implements StashInterface
{
    private StashInterface $instance;
    protected InputInterface $input;
    protected SessionInterface $session;

    protected string $stashKey = '__#stash#__';

    public function __construct(SessionInterface $session, InputInterface $input)
    {
        $this->session = $session;
        $this->input = $input;
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
        $this->session->set($this->stashKey, $this->input->copy());

        return $this;
    }

    public function apply(): bool
    {
        $stashed = null;

        if ($this->session->has($this->stashKey)) {
            $stashed = $this->session->get($this->stashKey);

            $this->session->remove($this->stashKey);

            if (is_array($stashed)) {
                $this->input->replace($stashed);
            } else {
                throw new InvalidValue('Stashed input was not an array.');
            }
        }

        // returns false on fail or no stashed data
        return is_array($stashed);
    }

    public function __debugInfo(): array
    {
        return [
            'stashKey' => $this->stashKey,
        ];
    }
}
