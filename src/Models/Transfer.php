<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Transaction\Models;

use Larva\Transaction\Events\TransferFailed;
use Larva\Transaction\Events\TransferSucceeded;
use Larva\Transaction\Transaction;
use think\facade\Event;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\relation\MorphTo;

/**
 * 企业付款模型，处理提现
 *
 * @property int $id 付款单ID
 * @property string $trade_channel 付款渠道
 * @property string $status 状态
 * @property int $amount 金额
 * @property string $currency 币种
 * @property string $description 描述
 * @property string $transaction_no 网关交易号
 * @property int $source_id
 * @property string $source_type
 * @property array $failure 失败信息
 * @property array $recipient 接收者
 * @property array $extra 扩展数据
 * @property string|null $succeed_at 成功时间
 * @property string|null $deleted_at 删除时间
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 *
 * @property-read boolean $succeed
 *
 * @property Model $source
 *
 * @author Tongle Xu <xutongle@gmail.com>
 */
class Transfer extends Model
{
    use SoftDelete, Traits\DateTimeFormatter, Traits\UsingDatetimeAsPrimaryKey;

    //付款状态
    public const STATUS_PENDING = 'PENDING';//待处理
    public const STATUS_SUCCESS = 'SUCCESS';//成功
    public const STATUS_ABNORMAL = 'ABNORMAL';//异常

    /**
     * 模型名称
     * @var string
     */
    protected $name = 'transaction_transfer';

    /**
     * 这个属性应该被转换为原生类型.
     *
     * @var array
     */
    protected $type = [
        'id' => 'int',
        'trade_channel' => 'string',
        'status' => 'string',
        'amount' => 'int',
        'currency' => 'string',
        'description' => 'string',
        'transaction_no' => 'string',
        'recipient' => 'array',
        'extra' => 'array',
        'failure' => 'array',
    ];

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
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'deleted_at';

    /**
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $statusMaps = [
        self::STATUS_PENDING => '待处理',
        self::STATUS_SUCCESS => '付款成功',
        self::STATUS_ABNORMAL => '付款异常',
    ];

    /**
     * 交易状态，枚举值
     * @var array|string[]
     */
    protected static array $statusDots = [
        self::STATUS_PENDING => 'info',
        self::STATUS_SUCCESS => 'success',
        self::STATUS_ABNORMAL => 'error',
    ];

    /**
     * 新增前事件
     * @param Transfer $model
     * @return void
     */
    public static function onBeforeInsert($model)
    {
        /** @var Transfer $model */
        $model->id = $model->generateKey();
        $model->currency = $model->currency ?: 'CNY';
        $model->status = static::STATUS_PENDING;
    }

    /**
     * 写入后执行
     * @param Transfer $model
     */
    public static function onAfterInsert(Transfer $model): void
    {
        $model->gatewayHandle();
    }

    /**
     * 获取 Status Label
     * @return string[]
     */
    public static function getStatusMaps(): array
    {
        return static::$statusMaps;
    }

    /**
     * 获取状态Dot
     * @return string[]
     */
    public static function getStateDots(): array
    {
        return static::$statusDots;
    }

    /**
     * 多态关联
     * @return MorphTo
     */
    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * 是否已付款
     * @return bool
     */
    public function getSucceedAttr(): bool
    {
        return $this->status == self::STATUS_SUCCESS;
    }

    /**
     * 设置已付款
     * @param string $transactionNo
     * @param array $extra
     * @return bool
     */
    public function markSucceeded(string $transactionNo, array $extra = []): bool
    {
        if ($this->succeed) {
            return true;
        }
        $state = $this->save([
            'transaction_no' => $transactionNo,
            'transferred_at' => $this->freshTimestamp(),
            'status' => static::STATUS_SUCCESS,
            'extra' => $extra
        ]);
        Event::trigger(new TransferSucceeded($this));
        return $state;
    }

    /**
     * 设置提现错误
     * @param string|int $code
     * @param string $desc
     * @param array $extra
     * @return bool
     */
    public function markFailed($code, string $desc, array $extra = []): bool
    {
        $state = $this->save([
            'status' => self::STATUS_ABNORMAL,
            'failure' => ['code' => $code, 'desc' => $desc],
            'extra' => $extra
        ]);
        Event::trigger(new TransferFailed($this));
        return $state;
    }

    /**
     * 主动发送付款请求到网关
     * @return Transfer
     */
    public function gatewayHandle(): Transfer
    {
        if ($this->trade_channel == Transaction::CHANNEL_WECHAT) {
            $config = [
                'partner_trade_no' => $this->id,
                'openid' => $this->recipient['account'],
                'check_name' => 'NO_CHECK',
                'amount' => $this->amount,
                'desc' => $this->description,
            ];
            if (isset($this->recipient['name'])) {
                $config['check_name'] = 'FORCE_CHECK';
                $config['re_user_name'] = $this->recipient['name'];
            }
            try {
                $response = Transaction::wechat()->transfer($config);
                $this->markSucceeded($response->payment_no, $response->toArray());
            } catch (\Exception $exception) {//设置付款失败
                $this->markFailed('FAIL', $exception->getMessage());
            }
        } elseif ($this->trade_channel == Transaction::CHANNEL_ALIPAY) {
            $config = [
                'out_biz_no' => $this->id,
                'payee_type' => $this->recipient['account_type'],
                'payee_account' => $this->recipient['account'],
                'amount' => $this->amount / 100,
                'remark' => $this->description,
            ];
            if (isset($this->recipient['name'])) {
                $config['payee_real_name'] = $this->recipient['name'];
            }
            try {
                $response = Transaction::alipay()->transfer($config);
                $this->markSucceeded($response->payment_no, $response->toArray());
            } catch (\Exception $exception) {//设置提现失败
                $this->markFailed('FAIL', $exception->getMessage());
            }
        }
        return $this;
    }
}
