<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Transaction\Models\Traits;

use think\Model;

/**
 * Trait DateTimeFormatter
 * @mixin Model
 * @author Tongle Xu <xutongle@gmail.com>
 */
trait DateTimeFormatter
{
    /**
     * 返回当前时间
     * @return string 获取当前时间
     */
    public function freshTimestamp(): string
    {
        return $this->formatDateTime('Y-m-d H:i:s.u');
    }
}
