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
        $table->addColumn(Column::bigInteger('id')->setUnSigned()->setComment('�˿���ˮ��'))
            ->addColumn('charge_id', 'integer', ['signed' => true, 'null' => true, 'comment' => '������ˮ��'])
            ->addColumn('transaction_no', 'string', ['limit' => 64, 'null' => true, 'comment' => '������ˮ��'])
            ->addColumn('amount', 'integer', ['signed' => true, 'comment' => '�˿���'])
            ->addColumn('reason', 'string', ['limit' => 127, 'null' => true, 'comment' => '�˿�ԭ��'])
            ->addColumn('status', 'string', ['null' => true, 'default' => Refund::STATUS_PENDING, 'comment' => '�˿�״̬'])
            ->addColumn(Column::json('failure')->setNullable()->setComment('������Ϣ'))
            ->addColumn(Column::json('extra')->setNullable()->setComment('�˿�ɹ�ʱ���ⷵ�ص�������Ϣ'))
            ->addColumn('succeed_at', 'datetime', ['null' => true, 'comment' => '�ɹ�ʱ��'])
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
