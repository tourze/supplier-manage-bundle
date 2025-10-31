<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;
use Tourze\SupplierManageBundle\EventListener\SupplierContactEntityListener;

/**
 * @internal
 */
#[CoversClass(SupplierContactEntityListener::class)]
#[RunTestsInSeparateProcesses]
class SupplierContactEntityListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 这个测试类不需要特殊的设置
    }

    public function testPreUpdate(): void
    {
        $listener = self::getService(SupplierContactEntityListener::class);

        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('John Doe');
        $contact->setEmail('john@example.com');
        $contact->setPhone('13800138000');

        $originalUpdateTime = $contact->getUpdateTime();

        // 模拟一些时间流逝
        usleep(1000);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $event = new PreUpdateEventArgs($contact, $entityManager, $changeSet);
        $listener->preUpdate($contact, $event);

        $this->assertGreaterThan($originalUpdateTime, $contact->getUpdateTime());
    }
}
