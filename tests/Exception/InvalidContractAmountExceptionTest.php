<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SupplierManageBundle\Exception\InvalidContractAmountException;

/**
 * @internal
 */
#[CoversClass(InvalidContractAmountException::class)]
class InvalidContractAmountExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCanBeCreated(): void
    {
        $exception = new InvalidContractAmountException('Test message');

        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertSame('Test message', $exception->getMessage());
    }

    public function testExceptionHasCorrectCode(): void
    {
        $exception = new InvalidContractAmountException('Test message', 123);

        $this->assertSame(123, $exception->getCode());
    }

    public function testExceptionCanHavePreviousException(): void
    {
        $previous = new \InvalidArgumentException('Previous error');
        $exception = new InvalidContractAmountException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
