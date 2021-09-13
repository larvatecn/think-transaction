<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 *
 * @copyright Copyright (c) 2010-2099 Jinan Larva Information Technology Co., Ltd.
 * @link http://www.larva.com.cn/
 */
namespace Larva\Transaction\Models\Traits;

use think\Model;

/**
 * Trait UsingDatetimeAsPrimaryKey
 * @mixin Model
 * @author Tongle Xu <xutongle@gmail.com>
 */
trait UsingDatetimeAsPrimaryKey
{
    /**
     * 生成流水号
     * @return string
     */
    protected function generateKey(): string
    {
        $i = rand(0, 9999);
        do {
            if (9999 == $i) {
                $i = 0;
            }
            $i++;
            $id = date('YmdHis') . str_pad((string)$i, 4, '0', STR_PAD_LEFT);
            $row = static::where($this->key, '=', $id)->find();
        } while ($row);
        return (string)$id;
    }
}
