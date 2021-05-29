<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Transaction\Events;

use Larva\Transaction\Models\Refund;

class RefundFailure
{
    /**
     * @var Refund
     */
    public $refund;

    /**
     * RefundFailure constructor.
     * @param Refund $refund
     */
    public function __construct(Refund $refund)
    {
        $this->refund = $refund;
    }
}