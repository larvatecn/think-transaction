<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare (strict_types=1);

namespace Larva\Transaction\Models;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use Exception;
use Larva\Transaction\Events\ChargeClosed;
use Larva\Transaction\Events\ChargeFailure;
use Larva\Transaction\Events\ChargeShipped;
use Larva\Transaction\Transaction;
use think\facade\Event;
use think\facade\Log;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\BelongsTo;
use think\model\relation\HasMany;
use think\model\relation\MorphTo;

/**
 * 支付模型
 * @property string $id
 * @property string $channel 交易渠道
 * @property string $type  交易类型
 * @property string $transaction_no 支付网关交易号
 * @property string $source_id 订单ID
 * @property string $source_type 订单类型
 * @property string $subject 支付标题
 * @property string $description 描述
 * @property int $total_amount 支付金额，单位分
 * @property string $currency 支付币种
 * @property string $state 交易状态
 * @property string $state_desc 交易状态描述
 * @property string $client_ip 客户端IP
 * @property array $payer 支付者信息
 * @property array $credential 客户端支付凭证
 * @property string|null $expire_time 交易结束时间
 * @property string|null $success_time 支付完成时间
 * @property string $create_time 创建时间
 * @property string|null $update_time 更新时间
 * @property string|null $delete_time 软删除时间
 *
 * @property-read bool $paid 是否已付款
 *
 * @property Model $source
 * @property Refund $refunds
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Charge extends Model
{
    use SoftDelete;

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'transaction_charges';

    /**
     * 主键名称
     * @var string
     */
    protected $pk = 'id';

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型
     * @var bool|string
     */
    protected $autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段 false表示关闭
     * @var false|string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段 false表示关闭
     * @var false|string
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'delete_time';

    /**
     * JSON数据表字段
     * @var array
     */
    protected $json = [
        'payer', 'credential'
    ];

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $type = [
        'id' => 'string',
        'total_amount' => 'int',
        'expire_time' => 'datetime',
        'success_time' => 'datetime',
    ];

    public const STATE_SUCCESS = 'SUCCESS';
    public const STATE_PENDING = 'PENDING';

    /**
     * 新增前事件
     * @param Charge $model
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Charge $model */
        $model->id = $model->generateId();
        $model->currency = $model->currency ?: 'CNY';
        $model->state = self::STATE_PENDING;
    }

    /**
     * 新增后事件
     * @param Charge $model
     */
    public static function onAfterInsert($model)
    {

    }

    /**
     * 关联退款
     * @return HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
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
     * 是否已经付款
     * @return bool
     */
    public function getPaidAttr(): bool
    {
        return $this->state == self::STATE_SUCCESS;
    }

    /**
     * 生成流水号
     * @return string
     */
    protected function generateId(): string
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = time() . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $row = static::where($this->key, '=', $id)->find();
        } while ($row);
        return $id;
    }
}