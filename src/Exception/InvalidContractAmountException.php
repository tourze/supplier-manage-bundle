<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Exception;

class InvalidContractAmountException extends \InvalidArgumentException
{
    public function __construct(string $message = '合同金额不能为负数', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
