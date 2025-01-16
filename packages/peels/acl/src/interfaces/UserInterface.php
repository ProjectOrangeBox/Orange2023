<?php

declare(strict_types=1);

namespace peels\acl\interfaces;

use peels\acl\interfaces\UserEntityInterface;

interface UserInterface
{
    public function load(): UserEntityInterface; // retrieve user or guest on no session
    public function change(int $userID): UserEntityInterface; // switch to user id ???
    public function logout(): UserEntityInterface; // switch to guest
}
