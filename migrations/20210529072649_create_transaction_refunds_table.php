<?php

use Larva\Transaction\Models\Refund;
use think\migration\Migrator;
use think\migration\db\Column;

class CreateTransactionRefundsTable extends Migrator
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
        $table = $this->table('transaction_refunds', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'string', ['limit' => 64])
            ->addColumn('user_id', 'biginteger', ['signed' => true, 'null' => true])
            ->addColumn('charge_id', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('amount', 'integer', ['signed' => true])
            ->addColumn('status', 'string', ['null' => true, 'default' => Refund::STATUS_PENDING])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('failure_code', 'string', ['null' => true])
            ->addColumn('failure_msg', 'string', ['null' => true])
            ->addColumn('charge_order_id', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('funding_source', 'string', ['limit' => 20, 'null' => true])
            ->addColumn(Column::json('extra')->setNullable())
            ->addColumn(Column::json('metadata')->setNullable())
            ->addColumn('time_succeed', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex('id', [
                'unique' => true,
            ])
            ->addIndex('user_id')
            ->addIndex('charge_id')
            ->create();

    }
}
