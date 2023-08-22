<?php

declare(strict_types=1);

namespace dmyers\orange;

use PDO;

abstract class ModelAbstract
{
    protected PDO $pdo;
    protected array $config = [];
    protected Sql $sql;

    // required in extending class
    protected string $tablename = '';
    protected string $primaryColumn = '';

    protected int $defaultFetchType = PDO::FETCH_ASSOC;
    protected string $fetchClass = '';
    protected bool $throwException = false;

    public function __construct(array $config, PDO $pdo)
    {
        // inject your connection when building your service
        $this->pdo = $pdo;
        $this->config = $config;

        $this->sql = new Sql([
            'primaryColumn' => $config['primaryColumn'] ?? $this->primaryColumn,
            'tablename' => $config['tablename'] ?? $this->tablename,
            'defaultFetchType' => $config['defaultFetchType'] ?? $this->defaultFetchType,
            'fetchClass' => $config['fetchClass'] ?? $this->fetchClass,
            'throwException' => $config['throwException'] ?? $this->throwException,
        ], $pdo);
    }

    protected function getById($id, string $columns = '*', int $fetchMode = -1): mixed
    {
        return $this->sql->select($columns)->from($this->tablename)->wherePrimary($id)->run($fetchMode)->row();
    }

    protected function getValueById(string $column, $id)
    {
        return $this->sql->select($column)->from($this->tablename)->wherePrimary($id)->run(PDO::FETCH_ASSOC)->column(0);
    }

    protected function getAll(string $columns = '*', int $fetchMode = -1): array
    {
        return $this->sql->select($columns)->from($this->tablename)->run($fetchMode)->rows();
    }
}
