<?php

declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class Init extends AbstractMigration
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * https://book.cakephp.org/phinx/0/en/migrations.html#the-change-method
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function up(): void
    {
        $this->table('parent')
            ->addColumn('firstname', 'string', ['length' => 32, 'null' => false])
            ->addColumn('lastname', 'string', ['length' => 32, 'null' => false])
            ->addColumn('age', 'integer', ['null' => true])
            ->create();

        $this->table('childern')
            ->addColumn('parent_id', 'integer', ['null' => false, 'signed' => false])
            ->addColumn('firstname', 'string', ['length' => 32, 'null' => false])
            ->addColumn('lastname', 'string', ['length' => 32, 'null' => false])
            ->addColumn('age', 'integer', ['null' => true])
            ->create();
    }

    public function down(): void
    {
        $this->table('parent')->drop()->save();
        $this->table('childern')->drop()->save();
    }
}
