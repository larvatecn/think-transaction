<?php

declare(strict_types=1);

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
        $table->addColumn(Column::bigInteger('id')->setUnSigned()->setComment('收款流水号'))
            ->addColumn('trade_channel', 'string', ['limit' => 64, 'null' => true, 'comment' => '收款渠道'])
            ->addColumn('trade_type', 'string', ['limit' => 20, 'null' => true, 'comment' => '交易类型'])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true, 'comment' => '网关流水号'])
            ->addColumn(Column::bigInteger('source_id')->setUnSigned())
            ->addColumn(Column::string('source_type'))
            ->addColumn('subject', 'string', ['limit' => 256, 'null' => true, 'comment' => '订单标题'])
            ->addColumn('description', 'string', ['limit' => 127, 'null' => true, 'comment' => '商品描述'])
            ->addColumn('total_amount', 'integer', ['signed' => true, 'comment' => '订单总金额'])
            ->addColumn('refunded_amount', 'integer', ['signed' => true, 'default' => 0, 'comment' => '已退款金额'])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'CNY', 'comment' => '货币类型'])
            ->addColumn('state', 'string', ['limit' => 32, 'null' => true, 'comment' => '交易状态'])
            ->addColumn('client_ip', 'string', ['limit' => 45, 'null' => true, 'comment' => '客户端IP'])
            ->addColumn(Column::json('metadata')->setNullable()->setComment('元信息'))
            ->addColumn(Column::json('credential')->setNullable()->setComment('客户端支付凭证'))
            ->addColumn(Column::json('extra')->setNullable()->setComment('成功时额外返回的渠道信息'))
            ->addColumn(Column::json('failure')->setNullable()->setComment('错误信息'))
            ->addColumn('expired_at', 'timestamp', ['null' => true, 'comment' => '失效时间'])
            ->addColumn('succeed_at', 'timestamp', ['null' => true, 'comment' => '支付时间'])
            ->addColumn('created_at', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->addColumn('deleted_at', 'timestamp', ['null' => true])
            ->addIndex('id', ['unique' => true,])
            ->addIndex(['source_id', 'source_type'], ['name' => null])
            ->create();
    }
}
