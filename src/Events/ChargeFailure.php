<?php
/**
 * This is NOT a freeware, use is subject to license terms
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */

namespace Larva\Transaction\Events;

use Larva\Transaction\Models\Charge;

class ChargeFailure
{
    /**
     * @var Charge
     */
    public $charge;

    /**
     * ChargeClosed constructor.
     * @param Charge $charge
     */
    public function __construct(Charge $charge)
    {
        $this->charge = $charge;
    }
}