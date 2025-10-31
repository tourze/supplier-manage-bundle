<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\EventListener\SupplierEntityListener;

/**
 * @internal
 */
#[CoversClass(SupplierEntityListener::class)]
#[RunTestsInSeparateProcesses]
class SupplierEntityListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 这个测试类不需要特殊的设置
    }

    public function testPreUpdate(): void
    {
        $listener = self::getService(SupplierEntityListener::class);
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $originalUpdateTime = $supplier->getUpdateTime();

        // 模拟一些时间流逝
        usleep(1000);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $event = new PreUpdateEventArgs($supplier, $entityManager, $changeSet);
        $listener->preUpdate($supplier, $event);

        $this->assertGreaterThan($originalUpdateTime, $supplier->getUpdateTime());
    }
}
