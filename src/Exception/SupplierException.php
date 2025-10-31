<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Exception;

class SupplierException extends \Exception
{
    public static function idNotGenerated(): self
    {
        return new self('ID has not been generated yet');
    }
}
