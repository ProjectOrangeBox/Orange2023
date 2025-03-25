<?php

declare(strict_types=1);

namespace peels\model;

use PDO;
use peels\validate\interfaces\ValidateInterface;

abstract class Model
{
    private static array $instances = [];

    protected PDO $pdo;
    protected array $config = [];

    // extended by child models
    protected array $rules = [];
    /* rules example:
      'id' => ['isRequired|isInteger', 'Id'],
      'firstname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'First Name'],
      'lastname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'Last Name'],
      'age' => ['isRequired|isInteger|isGreaterThan[17]|isLessThan[111]', 'Age'],
    */

    protected array $ruleSets = [];
    /* ruleSets example:
      'create' => ['firstname', 'lastname', 'age'],
      'update' => ['id', 'firstname', 'lastname', 'age'],
      'delete' => ['id'],
    */

    // required in extending class
    protected string $tablename;
    protected string $primaryColumn = 'id';

    protected string $entityClass;

    // https://www.php.net/manual/en/pdostatement.fetch.php
    protected int $defaultFetchType = PDO::FETCH_ASSOC;

    // if type is PDO::FETCH_CLASS provide the class here
    protected string $fetchClass = '';

    // throw an exception on error or simply capture for further processing
    protected bool $throwException = false;

    protected Sql $sql;
    protected Crud $crud;
    protected ValidateInterface $validateService;

    protected function __construct(?array $config, PDO $pdo, ValidateInterface $validateService)
    {
        $this->config = $config ?? [];

        if (!isset($this->tablename)) {
            $this->tablename = $this->generateTablename();
        }

        $this->pdo = $pdo;
        $this->validateService = $validateService;

        // setup sql config
        $this->config = [
            'primaryColumn' => $config['primaryColumn'] ?? $this->primaryColumn,
            'tablename' => $config['tablename'] ?? $this->tablename,
            'defaultFetchType' => $config['defaultFetchType'] ?? $this->defaultFetchType,
            'fetchClass' => $config['fetchClass'] ?? $this->fetchClass,
            // should the model throw exceptions?
            'throwException' => $config['throwException'] ?? $this->throwException,
        ];

        // validateService should throw exceptions for all failed rules so we can catch them
        $this->validateService->throwExceptionOnFailure(true);

        // setup our own personal versions
        $this->sql = new Sql($this->config, $pdo);

        // crud always throws sql exceptions
        $this->crud = new Crud($this->config, $pdo);
    }

    public static function getInstance(array $config, PDO $pdo, ValidateInterface $validate): self
    {
        $subclass = static::class;

        if (!isset(self::$instances[$subclass])) {
            self::$instances[$subclass] = new static($config, $pdo, $validate);
        }

        return self::$instances[$subclass];
    }

    public function getRules(string $set): array
    {
        // we only need these rules
        return array_intersect_key($this->rules, array_flip($this->ruleSets[$set]));
    }

    public function filterFields(string $set, array &$fields): array
    {
        // we only need these columns (any additional are removed!)
        return array_intersect_key($fields, array_flip($this->ruleSets[$set]));
    }

    public function validateFields(string $set, array $fields): bool|array
    {
        // now let's validation the input against the models column rules
        $this->validateService->reset();

        // we only need these rules
        $rules = $this->getRules($set);

        // we only need these columns (any additional are removed!)
        $fields = $this->filterFields($set, $fields);

        // validate our record and get out our values
        // above we request that an exception is thrown containing all failed rules
        $fields = $this->validateService->input($fields, $rules)->values();

        // if any rules failed technically we shouldn't get here
        return $this->validateService->hasNoErrors() ? $fields : false;
    }

    public function generateTablename(): string
    {
        $tablename = static::class;

        $pos = strrpos($tablename, '\\');

        if ($pos) {
            $tablename = substr($tablename, $pos + 1);
        }

        if (str_ends_with(strtolower($tablename), 'model')) {
            $tablename = substr($tablename, 0, -5);
        }

        return $tablename;
    }

    public function getLastInsertId(): string|false
    {
        return $this->pdo->lastInsertId();
    }
}
