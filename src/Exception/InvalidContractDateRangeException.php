<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Exception;

class InvalidContractDateRangeException extends \InvalidArgumentException
{
    public function __construct(string $message = '合同结束日期不能早于开始日期', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
