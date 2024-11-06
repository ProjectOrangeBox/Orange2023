<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PeopleSeeder extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {
        $data = [
            [
                'firstname' => 'Johnny',
                'lastname' => 'Appleseed',
                'age' => 23,
            ],
            [
                'firstname' => 'Charlie',
                'lastname' => 'Brown',
                'age' => 26,
            ]
        ];

        $this->table('parent')->insert($data)->saveData();
    }
}
