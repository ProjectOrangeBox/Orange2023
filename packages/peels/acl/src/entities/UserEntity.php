<?php

declare(strict_types=1);

namespace peels\acl\entities;

use Exception;
use peels\acl\interfaces\UserModelInterface;
use peels\acl\interfaces\RoleEntityInterface;
use peels\acl\interfaces\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
    protected UserModelInterface $userModel;
    protected array $config = [];

    public readonly int $id;
    // users name
    public string $username;
    // users email
    public string $email;
    // if the permission is active or not
    public readonly int $is_active;
    // soft delete user
    public readonly int $is_deleted;
    // users password
    private readonly string $password;

    protected array $permissions = [];
    protected array $roles = [];
    protected array $meta = [];

    protected bool $lazyLoaded = false;

    public function __construct(array $config, UserModelInterface $userModel)
    {
        $this->config = $config;
        $this->userModel = $userModel;
    }

    public function update(): bool
    {
        // combined meta & local properties
        // get the public columns from the entity
        $columns = get_object_vars(...)->__invoke($this) + $this->meta;

        return $this->userModel->update($columns);
    }

    public function updatePassword(string $newPassword): bool
    {
        return $this->userModel->updatePassword($this->id, $newPassword);
    }

    public function deactive(): bool
    {
        return $this->userModel->deactive($this->id);
    }

    public function active(): bool
    {
        return $this->userModel->active($this->id);
    }

    public function addRole(string|int|RoleEntityInterface $arg): bool
    {
        return $this->userModel->addRole($this->id, $arg);
    }

    public function removeRole(string|int|RoleEntityInterface $arg): bool
    {
        return $this->userModel->removeRole($this->id, $arg);
    }

    public function removeAllRoles(): bool
    {
        return $this->userModel->removeAllRoles($this->id);
    }

    /* access */
    public function can(string $permission): bool
    {
        $this->lazyLoad();

        return (in_array($permission, $this->permissions, true));
    }

    public function hasRole(int $role): bool
    {
        $this->lazyLoad();

        return array_key_exists($role, $this->roles);
    }

    public function hasRoles(array $roles): bool
    {
        foreach ($roles as $r) {
            if (!$this->hasRole($r)) {
                return false;
            }
        }

        return true;
    }

    public function hasOneRoleOf(array $roles): bool
    {
        foreach ((array) $roles as $r) {
            if ($this->hasRole($r)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermissions(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->cannot($p)) {
                return false;
            }
        }

        return true;
    }

    public function hasOnePermissionOf(array $permissions): bool
    {
        foreach ($permissions as $p) {
            if ($this->can($p)) {
                return true;
            }
        }

        return false;
    }

    public function hasPermission(string $permission): bool
    {
        return $this->can($permission);
    }

    public function cannot(string $permission): bool
    {
        return !$this->can($permission);
    }

    public function loggedIn(): bool
    {
        return $this->id != $this->config['guest user'];
    }

    public function isAdmin(): bool
    {
        return $this->hasRole($this->config['admin role']);
    }

    public function isGuest(): bool
    {
        return $this->id == $this->config['guest user'];
    }

    // meta
    public function __set(string $name, mixed $value): void
    {
        if (array_key_exists($name, $this->meta)) {
            $this->meta[$name] = $value;
        } else {
            throw new Exception('Unknown property "' . __CLASS__ . '::$' . $name . '".');
        }
    }

    // meta and easier access to a few others
    public function __get(string $name): mixed
    {
        $this->lazyLoad();

        if (array_key_exists($name, $this->meta)) {
            $return = $this->meta[$name];
        } else {
            switch (strtolower($name)) {
                case 'loggedin':
                    $return = $this->loggedIn();
                    break;
                case 'isadmin':
                    $return = $this->isAdmin();
                    break;
                case 'isguest':
                    $return = $this->isGuest();
                    break;
                case 'isactive':
                    $return = ($this->is_active == 1);
                    break;
                default:
                    throw new Exception('Undefined property "' . __CLASS__ . '::$' . $name . '".');
            }
        }

        return $return;
    }

    // internal use
    protected function lazyLoad(): void
    {
        if (!$this->lazyLoaded) {
            $access = $this->userModel->getRolesPermissions($this->id);

            $this->permissions = $access['permissions'] ?? [];
            $this->roles = $access['roles'] ?? [];

            $this->meta = $this->userModel->getMeta($this->id);

            $this->lazyLoaded = true;
        }
    }
}
