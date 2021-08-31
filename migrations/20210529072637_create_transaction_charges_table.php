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
        $table->addColumn('id', 'string', ['limit' => 64, 'comment' => '付款流水号'])
            ->addColumn('channel', 'string', ['limit' => 64, 'null' => true, 'comment' => '付款渠道'])
            ->addColumn('type', 'string', ['limit' => 20, 'null' => true, 'comment' => '交易类型'])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true, 'comment' => '网关流水号'])
            ->addColumn(Column::bigInteger('source_id')->setUnSigned())
            ->addColumn(Column::string('source_type'))
            ->addColumn('subject', 'string', ['limit' => 64,])
            ->addColumn('description', 'string', ['limit' => 127, 'null' => true])
            ->addColumn('total_amount', 'integer', ['signed' => true, 'comment' => '订单总金额'])
            ->addColumn('currency', 'string', ['limit' => 3, 'default' => 'CNY', 'comment' => '货币类型'])
            ->addColumn('state', 'string', ['limit' => 32, 'null' => true, 'comment' => '交易状态'])
            ->addColumn('client_ip', 'string', ['limit' => 45, 'null' => true, 'comment' => '客户端IP'])
            ->addColumn(Column::json('payer')->setNullable()->setComment('支付者信息'))
            ->addColumn(Column::json('credential')->setNullable()->setComment('客户端支付凭证'))
            ->addColumn('expire_time', 'timestamp', ['null' => true])
            ->addColumn('create_time', 'timestamp', ['null' => true, 'default' => 'CURRENT_TIMESTAMP'])
            ->addColumn('update_time', 'timestamp', ['null' => true])
            ->addColumn('delete_time', 'timestamp', ['null' => true])
            ->addIndex('id', ['unique' => true,])
            ->addIndex(['source_id', 'source_type'], ['name' => null])
            ->create();
    }
}
