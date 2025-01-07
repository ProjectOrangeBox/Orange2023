<?php

declare(strict_types=1);

namespace peels\model;

use PDO;
use peels\model\Sql;

class Crud
{
    // You can change these in you model
    public string $tablename;
    public string $primaryColumn;

    public string $activeColumn = 'is_active';
    public bool $deactiveOnDelete = false;
    public bool $readOnlyActive = false;

    protected array $config = [];
    protected Sql $sql;
    protected PDO $pdo;

    public function __construct(array $config, PDO $pdo)
    {
        $this->config = $config;

        // merge config
        foreach (['tablename', 'primaryColumn', 'activeColumn', 'deactiveOnDelete', 'readOnlyActive'] as $variable) {
            $this->$variable = $this->config[$variable] ?? $this->$variable;
        }

        $this->pdo = $pdo;

        // setup our own personal version
        $this->sql = new Sql($config, $pdo);

        // make sure we throw exceptions regardless of config
        $this->sql->throwExceptions(true);
    }

    public function create(array $columns): int
    {
        return (int)$this->sql->insert()->into()->set($columns)->execute()->lastInsertId();
    }

    public function update(array $columns, int $primaryId): bool
    {
        return $this->sql->update()->set($columns)->wherePrimary($primaryId)->execute()->rowCount() > 0;
    }

    public function delete(int $primaryId): bool
    {
        if (!$this->deactiveOnDelete) {
            $success = $this->sql->delete()->from()->wherePrimary($primaryId)->execute()->rowCount() > 0;
        } else {
            $success = $this->deactivate($primaryId);
        }

        return $success;
    }

    public function deactivate(int $primaryId): bool
    {
        return $this->sql->update()->set([$this->activeColumn => 0])->wherePrimary($primaryId)->execute()->rowCount() > 0;
    }

    public function activate(int $primaryId): bool
    {
        return $this->sql->update()->set([$this->activeColumn => 1])->wherePrimary($primaryId)->execute()->rowCount() > 0;
    }

    public function readAll(): array
    {
        $this->sql->select('*')->from();

        if ($this->readOnlyActive) {
            $this->sql->whereEqual($this->activeColumn, 1);
        }

        return $this->sql->execute()->rows();
    }

    public function read(int $primaryId): array|bool
    {
        $this->sql->select()->from()->wherePrimary($primaryId);

        if ($this->readOnlyActive) {
            $this->sql->and()->whereEqual($this->activeColumn, 1);
        }

        return $this->sql->execute()->row();
    }

    public function readOnlyActive(bool $bool): self
    {
        $this->readOnlyActive = $bool;

        return $this;
    }

    public function readValueById(string $column, $id): mixed
    {
        return $this->sql->select($column)->from()->wherePrimary($id)->execute(PDO::FETCH_ASSOC)->column(0);
    }
}
