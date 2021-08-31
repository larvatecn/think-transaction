<?php

use Larva\Transaction\Models\Transfer;
use think\migration\Migrator;
use think\migration\db\Column;

class CreateTransactionTransferTable extends Migrator
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
        $table = $this->table('transaction_transfer', ['id' => false, 'primary_key' => 'id']);
        $table->addColumn('id', 'string', ['limit' => 64])
            ->addColumn('channel', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('state', 'string', ['limit' => 15, 'null' => true, 'default' => Transfer::STATE_SCHEDULED])
            ->addColumn(Column::bigInteger('source_id')->setUnSigned())
            ->addColumn(Column::string('source_type'))
            ->addColumn('amount', 'integer', ['signed' => true])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'CNY'])
            ->addColumn('recipient_id', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => true])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true])
            ->addColumn('failure_msg', 'string', ['limit' => 64, 'null' => true])
            ->addColumn(Column::json('metadata')->setNullable())
            ->addColumn(Column::json('extra')->setNullable())
            ->addColumn('transferred_at', 'datetime', ['null' => true])
            ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex('id', ['unique' => true,])
            ->addIndex(['source_id', 'source_type'], ['name' => null])
            ->create();
    }
}
