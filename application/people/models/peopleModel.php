<?php

declare(strict_types=1);

namespace application\people\models;

use PDO;
use peels\model\ModelAbstract;
use peels\validate\interfaces\ValidateInterface;

class peopleModel extends ModelAbstract
{
    protected string $tablename = 'parent';
    protected string $primaryColumn = 'id';
    protected array $rules = [
        'id' => ['isInteger', 'Id'],
        'firstname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'First Name'],
        'lastname' => ['isRequired|isString|isAlphaNumericSpace|maxLength[32]', 'Last Name'],
        'age' => ['isRequired|isInteger|isGreaterThan[17]|isLessThan[111]', 'Age'],
    ];
    protected array $ruleSets = [
        'create' => ['firstname', 'lastname', 'age'],
        'update' => ['id', 'firstname', 'lastname', 'age'],
        'delete' => ['id'],
    ];
    protected int $primaryKey = 0;
    protected ValidateInterface $validate;

    public function __construct(array $config, PDO $pdo)
    {
        parent::__construct($config, $pdo);

        $this->validate = container()->validate;
    }

    public function getByName(string $name): mixed
    {
        return $this->sql->select('*')->from($this->tablename)->whereEqual('name', $name)->run()->row();
    }

    public function create(array $record): bool
    {
        return $this->process('create', $record);
    }

    public function update(array $record): bool
    {
        return $this->process('update', $record);
    }

    public function delete(array $record): bool
    {
        return $this->process('delete', $record);
    }

    public function errors(): array
    {
        return $this->validate->errors();
    }

    protected function process(string $type, array $record): bool
    {
        $this->validate->reset();

        // let's make sure this use can... Read, Create, Update, Delete
        if (isset($record['id'])) {
            $this->validate->input($record['id'], 'isPrimaryId|hasCan' . ucfirst($type));
        } else {
            //$this->validate->input('', 'hasCanCreate');
        }

        if (!$this->validate->hasErrors()) {
            $this->validate->reset();

            // we only need these rules
            $rules = array_intersect_key($this->rules, array_flip($this->ruleSets[$type]));

            // we only need these columns (any addtional are removed!)
            $record = array_intersect_key($record, array_flip($this->ruleSets[$type]));

            // validate our record
            $record = $this->validate->input($record, $rules)->values();

            // handle as needed
            if (!$this->validate->hasErrors()) {
                switch ($type) {
                    case 'create':
                        $this->sql->insert()->into()->set($record)->run();
                        break;
                    case 'update':
                        $this->sql->update()->set($record)->wherePrimary($record['id'])->run();
                        break;
                    case 'delete':
                        $this->sql->delete()->wherePrimary($record['id'])->run();
                        break;
                }
            }
        }

        // return bool
        return !$this->validate->hasErrors();
    }
}
