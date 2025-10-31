<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\EventListener\EvaluationItemEntityListener;

/**
 * @internal
 */
#[CoversClass(EvaluationItemEntityListener::class)]
#[RunTestsInSeparateProcesses]
class EvaluationItemEntityListenerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 这个测试类不需要特殊的设置
    }

    public function testPreUpdate(): void
    {
        $listener = self::getService(EvaluationItemEntityListener::class);

        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2024-001');
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationPeriod('2024-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2024-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        $originalUpdateTime = $item->getUpdateTime();

        usleep(1000);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $changeSet = [];
        $event = new PreUpdateEventArgs($item, $entityManager, $changeSet);
        $listener->preUpdate($item, $event);

        $this->assertGreaterThan($originalUpdateTime, $item->getUpdateTime());
    }
}
