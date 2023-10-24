<?php

declare(strict_types=1);

namespace peels\model;

use PDO;

abstract class ModelAbstract
{
    private static $instance;

    protected PDO $pdo;
    protected array $config = [];

    // required in extending class
    protected string $tablename = '';
    protected string $primaryColumn = 'id';

    // https://www.php.net/manual/en/pdostatement.fetch.php
    protected int $defaultFetchType = PDO::FETCH_ASSOC;

    // if type is PDO::FETCH_CLASS provide the class here
    protected string $fetchClass = '';

    // throw an exception on error or simply capture for further processing
    protected bool $throwException = false;

    // in the child if your don't want to setup and attach sql builder set to false
    protected bool $setupSqlBuilder = true;
    protected Sql $sql;

    public function __construct(array $config, PDO $pdo)
    {
        // inject your connection when building your service
        $this->pdo = $pdo;
        $this->config = $config;

        if ($this->setupSqlBuilder) {
            $this->sql = new Sql([
                'primaryColumn' => $config['primaryColumn'] ?? $this->primaryColumn,
                'tablename' => $config['tablename'] ?? $this->tablename,
                'defaultFetchType' => $config['defaultFetchType'] ?? $this->defaultFetchType,
                'fetchClass' => $config['fetchClass'] ?? $this->fetchClass,
                'throwException' => $config['throwException'] ?? $this->throwException,
            ], $pdo);
        }
    }

    public static function getInstance(array $config, PDO $pdo): self
    {
        if (!isset(self::$instance)) {
            $extendingClass = get_called_class();

            self::$instance = new $extendingClass($config, $pdo);
        }

        return self::$instance;
    }

    public function getById($id, string $columns = '*', int $fetchMode = -1): mixed
    {
        return $this->sql->select($columns)->from($this->tablename)->wherePrimary($id)->run($fetchMode)->row();
    }

    public function getValueById(string $column, $id)
    {
        return $this->sql->select($column)->from($this->tablename)->wherePrimary($id)->run(PDO::FETCH_ASSOC)->column(0);
    }

    public function getAll(string $columns = '*', int $fetchMode = -1): array
    {
        return $this->sql->select($columns)->from($this->tablename)->run($fetchMode)->rows();
    }
}
