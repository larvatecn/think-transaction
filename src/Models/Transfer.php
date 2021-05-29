<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types = 1);

namespace Larva\Transaction\Models;

use Carbon\CarbonInterface;
use think\Model;
use think\model\relation\MorphTo;

/**
 * 企业付款模型，处理提现
 *
 * @property string $id 付款单ID
 * @property string $channel 付款渠道
 * @property-read boolean $paid 是否已经转账
 * @property string $status 状态
 * @property mixed $source 触发源对象
 * @property int $amount 金额
 * @property string $currency 币种
 * @property string $recipient_id 接收者ID
 * @property string $description 描述
 * @property string $transaction_no 网关交易号
 * @property string $failure_msg 失败详情
 * @property array $metadata 元数据
 * @property array $extra 扩展数据
 * @property CarbonInterface $transferred_at 交易成功时间
 * @property CarbonInterface $deleted_at 软删除时间
 * @property CarbonInterface $created_at 创建时间
 * @property CarbonInterface $updated_at 更新时间
 * @property-read boolean $scheduled
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Transfer extends Model
{

    //付款状态
    const STATUS_SCHEDULED = 'scheduled';//scheduled: 待发送
    const STATUS_PENDING = 'pending';//pending: 处理中
    const STATUS_PAID = 'paid';//paid: 付款成功
    const STATUS_FAILED = 'failed';//failed: 付款失败

    protected $name = 'transaction_transfer';

    /**
     * 主键值
     * @var string
     */
    protected $key = 'id';

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = 'created_at';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = 'updated_at';

    /**
     * 新增前事件
     * @param Model $model
     * @return mixed|void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Transfer $model */
        $model->id = $model->generateId();
        $model->currency = $model->currency ?: 'CNY';
        $model->status = static::STATUS_SCHEDULED;
    }

    /**
     * 新增后事件
     * @param Model $model
     */
    public static function onAfterInsert($model)
    {
        $model->send();
    }

    /**
     * 关联调用该功能模型
     * @return MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 生成流水号
     * @return int
     */
    protected function generateId()
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad($i, 4, '0', STR_PAD_LEFT);
            $row = static::where($this->key, $id)->exists();
        } while ($row);
        return $id;
    }
}