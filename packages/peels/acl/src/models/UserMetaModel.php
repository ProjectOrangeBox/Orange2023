<?php

declare(strict_types=1);

namespace peels\acl\models;

use PDO;
use peels\model\Model;
use peels\acl\interfaces\ModelInterface;
use peels\validate\interfaces\ValidateInterface;
use peels\acl\exceptions\RecordNotFoundException;

/**
 * Add all of the extra fluff data a user might have
 * that doesn't effect application operations to this class/model/table
 */
class UserMetaModel extends Model implements ModelInterface
{
    protected array $rules = [
        'id' => ['isRequired|integer', 'Id'],
        'phone' => ['isRequired'],
        'ext' => ['isRequired'],
        'dashboard_url' => ['isRequired'],
    ];
    protected array $ruleSets = [
        // We normally don't include the primary id
        // but in this case we manually insert the primary id based on the UserModel's primary id
        'create' => ['id', 'dashboard_url', 'phone', 'ext'],
        'update' => ['id', 'dashboard_url', 'phone', 'ext'],
        'delete' => ['id'],
    ];

    public function __construct(array $config, PDO $pdo, ValidateInterface $validateService)
    {
        $this->tablename = $config['tablename'];

        $validateService->throwExceptionOnFailure(true);

        parent::__construct($config, $pdo, $validateService);

        $this->sql->throwExceptions(true);
    }

    public function create(array $columns): int
    {
        // throws an exception
        $this->validateFields('create', $columns);

        return $this->sql->insert()->into($this->tablename)->values($columns)->execute()->lastInsertId();
    }

    public function update(array $columns): bool
    {
        // throws an exception
        $this->validateFields('update', $columns);

        $this->sql->update($this->tablename)->set($columns)->where('id', '=', $columns['id'])->execute();

        return true;
    }

    public function delete(int $id): bool
    {
        // throws an exception
        $this->validateFields('delete', ['id' => $id]);

        $this->sql->update($this->tablename)->set(['is_deleted' => 0])->where('id', '=', $id)->execute();

        return true;
    }

    public function read(int $id): array
    {
        if ($this->sql->select()->from($this->tablename)->where('id', '=', $id)->execute()->rowCount() > 0) {
            $array = $this->sql->row();
        } else {
            throw new RecordNotFoundException('User Meta Record ' . $id);
        }

        return $array;
    }
}
