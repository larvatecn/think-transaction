<?php

use think\migration\Migrator;
use think\migration\db\Column;

class CreateTransactionChargesTable extends Migrator
{
    /**
     * Change Method.
     *
     * Write your reversible migrations using this method.
     *
     * More information on writing migrations is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-abstractmigration-class
     *
     * The following commands can be used in this method and Phinx will
     * automatically reverse them when rolling back:
     *
     *    createTable
     *    renameTable
     *    addColumn
     *    renameColumn
     *    addIndex
     *    addForeignKey
     *
     * Remember to call "create()" or "update()" and NOT "save()" when working
     * with the Table class.
     */
    public function change()
    {
        $table = $this->table('transaction_charges', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'string', ['limit' => 64])
            ->addColumn('trade_channel', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('trade_type', 'string', ['limit' => 20, 'null' => true])
            ->addColumn('total_amount', 'integer', ['signed' => true])
            ->addColumn('trade_state', 'string', ['limit' => 32, 'null' => true])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'CNY'])

            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true])

            ->addMorphs('source')//多态


            ->addColumn('subject', 'string', ['limit' => 64,])
            ->addColumn('description', 'string', ['limit' => 127, 'null' => true])
            ->addColumn('client_ip', 'string', ['limit' => 45, 'null' => true])


            ->addColumn('time_paid', 'datetime', ['null' => true])
            ->addColumn('time_expire', 'datetime', ['null' => true])

            ->addColumn('amount_refunded', 'integer', ['signed' => true, 'null' => true, 'default' => 0])
            ->addColumn('failure_code', 'string', ['null' => true])
            ->addColumn('failure_msg', 'string', ['null' => true])
            ->addColumn(Column::json('extra')->setNullable())
            ->addColumn(Column::json('metadata')->setNullable())
            ->addColumn(Column::json('credential')->setNullable())
            ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex('id',[
                'unique' => true,
            ])
            ->create();
    }
}
