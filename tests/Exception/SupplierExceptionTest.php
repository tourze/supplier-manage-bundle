<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SupplierManageBundle\Exception\SupplierException;

/**
 * @internal
 */
#[CoversClass(SupplierException::class)]
class SupplierExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionCreation(): void
    {
        $exception = new SupplierException('Test message');

        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    public function testExceptionWithPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new SupplierException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }
}
