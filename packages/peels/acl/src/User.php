<?php

declare(strict_types=1);

namespace peels\acl;

use orange\framework\traits\ConfigurationTrait;
use peels\session\SessionInterface;
use peels\acl\interfaces\AclInterface;
use peels\acl\interfaces\UserInterface;
use peels\acl\interfaces\UserEntityInterface;
use orange\framework\exceptions\MissingRequired;

class User implements UserInterface
{
    use ConfigurationTrait;

    protected array $config = [];

    private static UserInterface $instance;

    protected AclInterface $acl;
    protected SessionInterface $sessionService;

    protected string $sessionKey = '##USERSESSION##';
    protected int $guestUserId;

    public function __construct(array $config, AclInterface $acl, SessionInterface $sessionService)
    {
        $this->config = $this->mergeConfigWith($config);

        $this->acl = $acl;
        $this->sessionService = $sessionService;

        // required value
        if (!isset($this->config['guest user']) || !is_integer($this->config['guest user'])) {
            throw new MissingRequired('Invalid Guest User Id');
        }

        $this->guestUserId = $this->config['guest user'];

        $this->sessionKey = $this->config['sessionKey'] ?? $this->sessionKey;
    }

    public static function getInstance(array $config, AclInterface $acl, SessionInterface $sessionService): self
    {
        if (!isset(self::$instance)) {
            self::$instance = new self($config, $acl, $sessionService);
        }

        return self::$instance;
    }

    public function load(): UserEntityInterface
    {
        /*
         * Get the user they are
         * - OR -
         * if they don't have a session guest is returned
         */
        return $this->acl->getUser($this->retrieve());
    }

    public function change(int $userID): UserEntityInterface
    {
        $this->save($userID);

        return $this->acl->getUser($userID);
    }

    public function logout(): UserEntityInterface
    {
        return $this->change($this->guestUserId);
    }

    /* session */
    protected function retrieve(): int
    {
        // default to guest
        $userId = $this->guestUserId;

        $sessionUserId = $this->sessionService->get($this->sessionKey, null);

        // do checks
        if ($sessionUserId != null) {
            if ((int)$userId > 0) {
                $userId = $sessionUserId;
            }
        }

        return $userId;
    }

    /* session */
    protected function save(int $userId): bool
    {
        $this->sessionService->set($this->sessionKey, $userId);

        return true;
    }
}
