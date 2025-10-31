<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\EventListener\ContractEntityListener;

/**
 * @internal
 */
#[CoversClass(ContractEntityListener::class)]
#[RunTestsInSeparateProcesses]
class ContractEntityListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 这个测试类不需要特殊的设置
    }

    public function testPreUpdate(): void
    {
        $listener = self::getService(ContractEntityListener::class);

        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $contract = new Contract();
        $contract->setSupplier($supplier);
        $contract->setContractNumber('CON-2024-001');
        $contract->setTitle('测试合同');
        $contract->setContractType('supply');
        $contract->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $contract->setAmount(100000.00);

        $originalUpdateTime = $contract->getUpdateTime();

        // 模拟一些时间流逝
        usleep(1000);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $event = new PreUpdateEventArgs($contract, $entityManager, $changeSet);
        $listener->preUpdate($contract, $event);

        $this->assertGreaterThan($originalUpdateTime, $contract->getUpdateTime());
    }
}
