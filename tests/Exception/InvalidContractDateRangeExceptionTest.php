<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SupplierManageBundle\Exception\InvalidContractDateRangeException;

/**
 * @internal
 */
#[CoversClass(InvalidContractDateRangeException::class)]
class InvalidContractDateRangeExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $exception = new InvalidContractDateRangeException('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionHasCorrectCode(): void
    {
        $exception = new InvalidContractDateRangeException('Test message', 456);

        $this->assertSame(456, $exception->getCode());
    }

    public function testExceptionCanHavePreviousException(): void
    {
        $previous = new \InvalidArgumentException('Previous error');
        $exception = new InvalidContractDateRangeException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
