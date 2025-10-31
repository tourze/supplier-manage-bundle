<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;
use Tourze\SupplierManageBundle\EventListener\SupplierQualificationEntityListener;

/**
 * @internal
 */
#[CoversClass(SupplierQualificationEntityListener::class)]
#[RunTestsInSeparateProcesses]
class SupplierQualificationEntityListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 这个测试类不需要特殊的设置
    }

    public function testPreUpdate(): void
    {
        $listener = self::getService(SupplierQualificationEntityListener::class);

        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplier);
        $qualification->setName('ISO 9001');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('ISO-001');
        $qualification->setIssuingAuthority('ISO Organization');
        $qualification->setIssuedDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setExpiryDate(new \DateTimeImmutable('2027-01-01'));

        $originalUpdateTime = $qualification->getUpdateTime();

        // 模拟一些时间流逝
        usleep(1000);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $event = new PreUpdateEventArgs($qualification, $entityManager, $changeSet);
        $listener->preUpdate($qualification, $event);

        $this->assertGreaterThan($originalUpdateTime, $qualification->getUpdateTime());
    }
}
