<?php

declare(strict_types=1);

namespace peels\acl\models;

use PDO;
use peels\model\Crud;
use peels\model\Model;
use orange\framework\Application;
use peels\acl\entities\UserEntity;
use peels\acl\models\UserMetaModel;
use peels\acl\interfaces\AclInterface;
use peels\acl\interfaces\UserModelInterface;
use peels\acl\interfaces\RoleEntityInterface;
use peels\acl\interfaces\UserEntityInterface;
use orange\framework\traits\ConfigurationTrait;
use peels\validate\exceptions\ValidationFailed;
use peels\validate\interfaces\ValidateInterface;
use peels\acl\exceptions\RecordNotFoundException;

class UserModel extends Model implements UserModelInterface
{
    use ConfigurationTrait;

    public AclInterface $acl;

    protected UserMetaModel $userMetaModel;
    protected string $tableJoin;

    protected array $rules = [
        'id' => ['isRequired|integer', 'Id'],
        'username' => ['isRequired|minLength[4]|maxLength[64]|isUnique[%s,username,id,pdo]', 'User Name'],
        'email' => ['isRequired|minLength[4]|maxLength[255]||isUnique[%s,email,id,pdo]', 'Email'],
        'password' => ['isRequired|minLength[4]|maxLength[255]', 'Password'],
        'is_active' => ['isOneOf[0,1]', 'Is Active'],
    ];
    protected array $ruleSets = [
        'create' => ['username', 'email', 'password', 'dashboard_url', 'is_active'],
        'update' => ['id', 'username', 'email', 'dashboard_url', 'is_active'],
        'delete' => ['id'],
    ];

    public function __construct(array $config, PDO $pdo, ValidateInterface $validateService)
    {
        $this->mergeWithDefault($config);

        $this->entityClass = $this->config['UserEntityClass'] ?? \peels\acl\entities\UserEntity::class;

        $this->config['tablename'] = $this->tablename = $this->config['user table'];

        $this->rules['username'][0] = sprintf($this->rules['username'][0], $this->tablename);
        $this->rules['email'][0] = sprintf($this->rules['email'][0], $this->tablename);

        $this->tableJoin = $this->config['user role table'];

        // I manage the meta model 100%
        $this->userMetaModel = new UserMetaModel(['tablename' => $this->config['user meta table']], $pdo, $validateService);

        $validateService->throwExceptionOnFailure(true);

        parent::__construct($this->config, $pdo, $validateService);

        $this->sql->throwExceptions(true);

        $this->crud = new Crud(['tablename' => $this->tablename, 'primaryColumn' => 'id', 'activeColumn' => 'is_active', 'deactivate on delete' => true], $pdo);
    }

    public function create(array $columns): UserEntityInterface
    {
        // password is required
        $columns['password'] = $this->passwordHash($columns['password']);

        $metaColumns = $columns;

        // setup a validation failed exception as a collector
        $errors = new ValidationFailed();

        // we need to capture both then return both if applicable
        try {
            $this->userMetaModel->validateFields('update', $metaColumns);
        } catch (ValidationFailed $vf) {
            $errors->merge($vf);
        }

        try {
            $this->validateFields('update', $columns);
        } catch (ValidationFailed $vf) {
            $errors->merge($vf);
        }

        // if it has errors then "throw" it
        if ($errors->hasErrors()) {
            throw $errors;
        }

        // both check out so now insert
        // model filters $columns
        $userId = $this->sql->insert()->into($this->tablename)->values($columns)->execute()->lastInsertId();

        $metaColumns['id'] = $userId;

        // model filters $columns
        $this->userMetaModel->create($metaColumns);

        return $this->read($userId);
    }

    /**
     * This will not update the password
     * Please use updatePassword()
     */
    public function update(array $columns): bool
    {
        $metaColumns = $columns;

        // setup a collector
        $errors = new ValidationFailed();

        // we need to capture both then return both if applicable
        try {
            $this->userMetaModel->validateFields('update', $metaColumns);
        } catch (ValidationFailed $vf) {
            $errors->merge($vf);
        }

        try {
            $this->validateFields('update', $columns);
        } catch (ValidationFailed $vf) {
            $errors->merge($vf);
        }

        // if it has errors then "throw" it
        if ($errors->hasErrors()) {
            throw $errors;
        }

        // both check out so now update
        $this->sql->update($this->tablename)->set($columns)->where('id', '=', $columns['id'])->execute();

        $this->userMetaModel->update($metaColumns);

        return ($this->sql->rowCount() > 0);
    }

    public function updatePassword(int $id, string $password): bool
    {
        $this->sql->update($this->tablename)->set(['password' => $this->passwordHash($password)])->where('id', '=', $id)->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function delete(int $id): bool
    {
        // throws an exception
        $this->validateFields('delete', ['id' => $id]);

        $this->sql->update($this->tablename)->set(['is_deleted' => 0])->where('id', '=', $id)->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function read(int $userId): UserEntityInterface
    {
        if ($this->sql->setFetchMode($this->entityClass, [$this->config, $this])->select()->from($this->tablename)->where('id', '=', $userId)->execute()->rowCount() > 0) {
            $userEntity = $this->sql->row();
        } else {
            throw new RecordNotFoundException('User Record ' . $userId, 404);
        }

        // without meta - this is lazy loaded with the permission only before being used
        return $userEntity;
    }

    public function readAll(): array
    {
        return $this->crud->readAll();
    }

    public function deactive(int $id): bool
    {
        $this->sql->update($this->tablename)->set(['is_active' => 0])->where('id', '=', $id)->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function active(int $id): bool
    {
        $this->sql->update($this->tablename)->set(['is_active' => 1])->where('id', '=', $id)->execute();

        return ($this->sql->rowCount() > 0);
    }

    protected function passwordHash(string $password): string
    {
        $info = password_get_info($password);

        if ($info['algo'] == 0) {
            $password = \password_hash($password, PASSWORD_DEFAULT);
        }

        return $password;
    }

    public function addRole(int $userId, string|int|RoleEntityInterface $arg): bool
    {
        if (is_string($arg)) {
            $roleEntity = $this->acl->getRole($arg);
            $roleId = (int)$roleEntity->id;
        } elseif ($arg instanceof RoleEntityInterface) {
            $roleId = (int)$arg->id;
        } else {
            // must be a integer
            $roleId = $arg;
        }

        $this->sql->insert()->into($this->tableJoin)->values(['role_id' => $roleId, 'user_id' => $userId])->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function removeRole(int $userId, string|int|RoleEntityInterface $arg): bool
    {
        if (is_string($arg)) {
            $roleEntity = $this->acl->getRole($arg);
            $roleId = (int)$roleEntity->id;
        } elseif ($arg instanceof RoleEntityInterface) {
            $roleId = (int)$arg->id;
        } else {
            // must be a integer
            $roleId = $arg;
        }

        $this->sql->delete($this->tableJoin)->whereEqual('role_id', $roleId)->and()->where('user_id', '=', $userId)->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function removeAllRoles(int $userId): bool
    {
        $this->sql->delete($this->tableJoin)->where('user_id', '=', $userId)->execute();

        return ($this->sql->rowCount() > 0);
    }

    public function relink(int $userId, array $roleIds): bool
    {
        $this->sql->pdo->beginTransaction();

        $this->removeAllRoles($userId);

        foreach ($roleIds as $roleId) {
            $this->addRole($userId, $roleId);
        }

        if ($this->sql->hasError()) {
            $this->sql->pdo->rollBack();
        } else {
            $this->sql->pdo->commit();
        }

        return $this->sql->hasError();
    }

    public function getRolesPermissions(int $userId): array
    {
        $rolesPermissions = [];

        $sql = "select
			`user_id`,
			`" . $this->config['role table'] . "`.`id` `orange_roles_id`,
			`" . $this->config['role table'] . "`.`name` `orange_roles_name`,
			`" . $this->config['role permission table'] . "`.`permission_id` `orange_permission_id`,
			`" . $this->config['permission table'] . "`.`key` `orange_permission_key`
			from `" . $this->config['user role table'] . "`
			left join `" . $this->config['role table'] . "` on `" . $this->config['role table'] . "`.`id` = `" . $this->config['user role table'] . "`.`role_id`
			left join `" . $this->config['role permission table'] . "` on `" . $this->config['role permission table'] . "`.`role_id` = `" . $this->config['role table'] . "`.`id`
			left join `" . $this->config['permission table'] . "` on `" . $this->config['permission table'] . "`.`id` = `" . $this->config['role permission table'] . "`.`permission_id`
			where `" . $this->config['user role table'] . "`.`user_id` = :userid
			and `" . $this->config['role table'] . "`.`is_active` = 1 and `" . $this->config['permission table'] . "`.`is_active` = 1
		";

        $dbc = $this->pdo->prepare($sql);
        $dbc->execute([':userid' => (int) $userId]);

        if ($dbc) {
            while ($dbr = $dbc->fetchObject()) {
                if ($dbr->orange_roles_name) {
                    if (!empty($dbr->orange_roles_name)) {
                        $rolesPermissions['roles'][(int) $dbr->orange_roles_id] = $dbr->orange_roles_name;
                    }
                }
                if ($dbr->orange_permission_key) {
                    if (!empty($dbr->orange_permission_key)) {
                        $rolesPermissions['permissions'][(int) $dbr->orange_permission_id] = $dbr->orange_permission_key;
                    }
                }
            }
        }

        /* everybody */
        $rolesPermissions['roles'][$this->config['everyone role']] = 'Everyone';

        return $rolesPermissions;
    }

    public function getMeta(int $userId): array
    {
        return $this->userMetaModel->read($userId);
    }
}
