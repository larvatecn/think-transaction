<?php

declare(strict_types=1);
/**
 * This is NOT a freeware, use is subject to license terms.
 */
namespace Larva\Transaction;

use RuntimeException;
use Throwable;

class TransactionException extends RuntimeException
{
    public function __construct($message = '', $code = 500, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
