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
        $table->addColumn(Column::bigInteger('id')->setUnSigned()->setComment('退款流水号'))
            ->addColumn('charge_id', 'integer', ['signed' => true, 'comment' => '付款流水号'])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true, 'comment' => '网关流水号'])
            ->addColumn('amount', 'integer', ['signed' => true, 'comment' => '退款金额'])
            ->addColumn('reason', 'string', ['limit' => 127, 'null' => true, 'comment' => '退款原因'])
            ->addColumn('status', 'string', ['null' => true, 'default' => Refund::STATUS_PENDING, 'comment' => '退款状态'])
            ->addColumn(Column::json('failure')->setNullable()->setComment('错误信息'))
            ->addColumn(Column::json('extra')->setNullable()->setComment('退款成功时额外返回的渠道信息'))
            ->addColumn('succeed_at', 'datetime', ['null' => true, 'comment' => '成功时间'])
            ->addColumn('created_at', 'datetime', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'datetime', ['null' => true])
            ->addColumn('deleted_at', 'datetime', ['null' => true])
            ->addIndex('id', [
                'unique' => true,
            ])
            ->addIndex('charge_id')
            ->create();

    }
}
