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
        $table->addColumn(Column::bigInteger('id')->setUnSigned()->setComment('付款流水号'))
            ->addColumn('trade_channel', 'string', ['limit' => 64, 'null' => true, 'comment' => '支付渠道'])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true, 'comment' => '网关流水号'])
            ->addColumn('status', 'string', ['limit' => 15, 'null' => true, 'default' => Transfer::STATUS_PENDING, 'comment' => '状态'])
            ->addColumn(Column::bigInteger('source_id')->setUnSigned())
            ->addColumn(Column::string('source_type'))
            ->addColumn('amount', 'integer', ['signed' => true, 'comment' => '金额'])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'CNY', 'comment' => '货币代码'])
            ->addColumn('description', 'string', ['limit' => 500, 'null' => true, 'comment' => '备注信息'])
            ->addColumn(Column::json('failure')->setNullable()->setComment('错误信息描述'))
            ->addColumn(Column::json('recipient')->setNullable()->setComment('收款人信息'))
            ->addColumn(Column::json('extra')->setNullable()->setComment('网关返回的信息'))
            ->addColumn('succeed_at', 'datetime', ['null' => true, 'comment' => '成功时间'])
            ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex('id', ['unique' => true,])
            ->addIndex(['source_id', 'source_type'], ['name' => null])
            ->create();
    }
}
