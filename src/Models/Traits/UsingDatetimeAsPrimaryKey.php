<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
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
        $i = rand(0, 99);
        do {
            if (99 == $i) {
                $i = 0;
            }
            $i++;
            $id = date('YmdHis') . str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $row = static::where($this->key, '=', $id)->find();
        } while ($row);
        return (string)$id;
    }
}
