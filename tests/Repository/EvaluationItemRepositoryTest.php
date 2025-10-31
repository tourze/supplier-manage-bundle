<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\Repository\EvaluationItemRepository;

/**
 * @internal
 */
#[CoversClass(EvaluationItemRepository::class)]
#[RunTestsInSeparateProcesses]
class EvaluationItemRepositoryTest extends AbstractRepositoryTestCase
{
    private EvaluationItemRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(EvaluationItemRepository::class);

        // 清理现有数据，确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . EvaluationItem::class . ' ei')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . PerformanceEvaluation::class . ' pe')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . Supplier::class . ' s')->execute();

        // 创建一个 DataFixture 测试数据以满足基类测试要求
        $supplier = new Supplier();
        $supplier->setName('DataFixture Test Supplier');
        $supplier->setLegalName('DataFixture Test Legal');
        $supplier->setLegalAddress('DataFixture Test Address');
        $supplier->setRegistrationNumber('DATA-FIXTURE-' . uniqid());
        $supplier->setTaxNumber('DATA-FIXTURE-TAX-' . uniqid());

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('DataFixture Test Evaluation');
        $evaluation->setEvaluationNumber('DATA-FIXTURE-EVAL-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('DataFixture Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('DataFixture Test Item');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(25.0);
        $item->setScore(85.0);
        $item->setMaxScore(90.0);
        $item->setDescription('DataFixture Test Description');

        self::getEntityManager()->persist($supplier);
        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();

        // 清除实体管理器缓存，确保测试方法能正常工作
        self::getEntityManager()->clear();
    }

    protected function createNewEntity(): EvaluationItem
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('TEST' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('E' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('服务质量评估');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(25.0);
        $item->setScore(85.0);
        $item->setMaxScore(90.0);
        $item->setDescription('测试评估项描述');

        self::getEntityManager()->persist($supplier);
        self::getEntityManager()->persist($evaluation);

        return $item;
    }

    /**
     * @return ServiceEntityRepository<EvaluationItem>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindByEvaluation(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For FindBy');
        $supplier->setLegalName('Test Legal Name For FindBy');
        $supplier->setLegalAddress('Test Address For FindBy');
        $supplier->setRegistrationNumber('TEST-FIND-BY-' . uniqid());
        $supplier->setTaxNumber('TAX-FIND-BY-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-FIND-BY-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        // 创建两个评估项，都属于同一个评估
        $item1 = new EvaluationItem();
        $item1->setEvaluation($evaluation);
        $item1->setItemName('服务质量');
        $item1->setItemType(EvaluationItemType::QUANTITATIVE);
        $item1->setWeight(25.0);
        $item1->setScore(85.0);
        $item1->setMaxScore(90.0);

        $item2 = new EvaluationItem();
        $item2->setEvaluation($evaluation);
        $item2->setItemName('交付及时性');
        $item2->setItemType(EvaluationItemType::QUANTITATIVE);
        $item2->setWeight(30.0);
        $item2->setScore(90.0);
        $item2->setMaxScore(85.0);

        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->flush();

        $items = $this->repository->findByEvaluation($evaluation);
        $this->assertCount(2, $items);
    }

    public function testFindByItemType(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Type');
        $supplier->setLegalName('Test Legal Name For Type');
        $supplier->setLegalAddress('Test Address For Type');
        $supplier->setRegistrationNumber('TEST-TYPE-' . uniqid());
        $supplier->setTaxNumber('TAX-TYPE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-TYPE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $quantitativeItem = new EvaluationItem();
        $quantitativeItem->setEvaluation($evaluation);
        $quantitativeItem->setItemName('数量指标');
        $quantitativeItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitativeItem->setWeight(25.0);
        $quantitativeItem->setScore(85.0);
        $quantitativeItem->setMaxScore(90.0);

        $qualitativeItem = new EvaluationItem();
        $qualitativeItem->setEvaluation($evaluation);
        $qualitativeItem->setItemName('质量指标');
        $qualitativeItem->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitativeItem->setWeight(30.0);
        $qualitativeItem->setScore(90.0);
        $qualitativeItem->setMaxScore(85.0);

        self::getEntityManager()->persist($quantitativeItem);
        self::getEntityManager()->persist($qualitativeItem);
        self::getEntityManager()->flush();

        $quantitativeItems = $this->repository->findByItemType(EvaluationItemType::QUANTITATIVE->value);
        $this->assertGreaterThanOrEqual(1, count($quantitativeItems));

        $qualitativeItems = $this->repository->findByItemType(EvaluationItemType::QUALITATIVE->value);
        $this->assertGreaterThanOrEqual(1, count($qualitativeItems));
    }

    public function testFindByWeightRange(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Weight');
        $supplier->setLegalName('Test Legal Name For Weight');
        $supplier->setLegalAddress('Test Address For Weight');
        $supplier->setRegistrationNumber('TEST-WEIGHT-' . uniqid());
        $supplier->setTaxNumber('TAX-WEIGHT-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-WEIGHT-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $lowWeightItem = new EvaluationItem();
        $lowWeightItem->setEvaluation($evaluation);
        $lowWeightItem->setItemName('低权重指标');
        $lowWeightItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $lowWeightItem->setWeight(10.0);
        $lowWeightItem->setScore(85.0);
        $lowWeightItem->setMaxScore(90.0);

        $midWeightItem = new EvaluationItem();
        $midWeightItem->setEvaluation($evaluation);
        $midWeightItem->setItemName('中权重指标');
        $midWeightItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $midWeightItem->setWeight(25.0);
        $midWeightItem->setScore(90.0);
        $midWeightItem->setMaxScore(85.0);

        $highWeightItem = new EvaluationItem();
        $highWeightItem->setEvaluation($evaluation);
        $highWeightItem->setItemName('高权重指标');
        $highWeightItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $highWeightItem->setWeight(40.0);
        $highWeightItem->setScore(95.0);
        $highWeightItem->setMaxScore(90.0);

        self::getEntityManager()->persist($lowWeightItem);
        self::getEntityManager()->persist($midWeightItem);
        self::getEntityManager()->persist($highWeightItem);
        self::getEntityManager()->flush();

        $midRangeItems = $this->repository->findByWeightRange(24.0, 26.0);
        $this->assertGreaterThanOrEqual(1, count($midRangeItems));
        // 检查是否包含我们创建的中权重指标
        $names = array_map(fn ($item) => $item->getItemName(), $midRangeItems);
        $this->assertContains('中权重指标', $names);
    }

    public function testFindByScoreRange(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Score');
        $supplier->setLegalName('Test Legal Name For Score');
        $supplier->setLegalAddress('Test Address For Score');
        $supplier->setRegistrationNumber('TEST-SCORE-' . uniqid());
        $supplier->setTaxNumber('TAX-SCORE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-SCORE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $lowScoreItem = new EvaluationItem();
        $lowScoreItem->setEvaluation($evaluation);
        $lowScoreItem->setItemName('低分指标');
        $lowScoreItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $lowScoreItem->setWeight(25.0);
        $lowScoreItem->setScore(70.0);
        $lowScoreItem->setMaxScore(90.0);

        $midScoreItem = new EvaluationItem();
        $midScoreItem->setEvaluation($evaluation);
        $midScoreItem->setItemName('中分指标');
        $midScoreItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $midScoreItem->setWeight(30.0);
        $midScoreItem->setScore(85.0);
        $midScoreItem->setMaxScore(85.0);

        $highScoreItem = new EvaluationItem();
        $highScoreItem->setEvaluation($evaluation);
        $highScoreItem->setItemName('高分指标');
        $highScoreItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $highScoreItem->setWeight(25.0);
        $highScoreItem->setScore(95.0);
        $highScoreItem->setMaxScore(90.0);

        self::getEntityManager()->persist($lowScoreItem);
        self::getEntityManager()->persist($midScoreItem);
        self::getEntityManager()->persist($highScoreItem);
        self::getEntityManager()->flush();

        $midRangeItems = $this->repository->findByScoreRange(84.0, 86.0);
        $this->assertGreaterThanOrEqual(1, count($midRangeItems));
        // 检查是否包含我们创建的中分指标
        $names = array_map(fn ($item) => $item->getItemName(), $midRangeItems);
        $this->assertContains('中分指标', $names);
    }

    public function testSearch(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Search');
        $supplier->setLegalName('Test Legal Name For Search');
        $supplier->setLegalAddress('Test Address For Search');
        $supplier->setRegistrationNumber('TEST-SEARCH-' . uniqid());
        $supplier->setTaxNumber('TAX-SEARCH-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-SEARCH-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('质量管理体系评估');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(25.0);
        $item->setScore(85.0);
        $item->setMaxScore(90.0);
        $item->setDescription('质量管理体系的详细评估，包括ISO 9001标准的执行情况');

        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();

        $resultsByName = $this->repository->search('质量管理');
        $this->assertCount(1, $resultsByName);
        $this->assertEquals('质量管理体系评估', $resultsByName[0]->getItemName());

        $resultsByDescription = $this->repository->search('ISO 9001');
        $this->assertCount(1, $resultsByDescription);
        $this->assertEquals('质量管理体系评估', $resultsByDescription[0]->getItemName());
    }

    public function testGetAverageScore(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Average');
        $supplier->setLegalName('Test Legal Name For Average');
        $supplier->setLegalAddress('Test Address For Average');
        $supplier->setRegistrationNumber('TEST-AVG-' . uniqid());
        $supplier->setTaxNumber('TAX-AVG-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-AVG-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $item1 = new EvaluationItem();
        $item1->setEvaluation($evaluation);
        $item1->setItemName('指标1');
        $item1->setItemType(EvaluationItemType::QUANTITATIVE);
        $item1->setWeight(25.0);
        $item1->setScore(80.0);
        $item1->setMaxScore(90.0);

        $item2 = new EvaluationItem();
        $item2->setEvaluation($evaluation);
        $item2->setItemName('指标2');
        $item2->setItemType(EvaluationItemType::QUANTITATIVE);
        $item2->setWeight(30.0);
        $item2->setScore(90.0);
        $item2->setMaxScore(85.0);

        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->flush();

        $averageScore = $this->repository->getAverageScoreByEvaluation($evaluation);
        $this->assertEquals(85.0, $averageScore);
    }

    public function testCalculateWeightedScore(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Weighted');
        $supplier->setLegalName('Test Legal Name For Weighted');
        $supplier->setLegalAddress('Test Address For Weighted');
        $supplier->setRegistrationNumber('TEST-WEIGHTED-' . uniqid());
        $supplier->setTaxNumber('TAX-WEIGHTED-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-WEIGHTED-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $item1 = new EvaluationItem();
        $item1->setEvaluation($evaluation);
        $item1->setItemName('指标1');
        $item1->setItemType(EvaluationItemType::QUANTITATIVE);
        $item1->setWeight(0.3);
        $item1->setScore(80.0);
        $item1->setMaxScore(90.0);

        $item2 = new EvaluationItem();
        $item2->setEvaluation($evaluation);
        $item2->setItemName('指标2');
        $item2->setItemType(EvaluationItemType::QUANTITATIVE);
        $item2->setWeight(0.7);
        $item2->setScore(90.0);
        $item2->setMaxScore(85.0);

        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->flush();

        $weightedScore = $this->repository->getWeightedScoreByEvaluation($evaluation);
        // 计算: (80/90)*0.3 + (90/85)*0.7 = 0.2667 + 0.7412 = 1.0079
        $this->assertEquals(1.01, round($weightedScore, 2));
    }

    public function testGetTotalWeightByEvaluation(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Total Weight');
        $supplier->setLegalName('Test Legal Name For Total Weight');
        $supplier->setLegalAddress('Test Address For Total Weight');
        $supplier->setRegistrationNumber('TEST-TOTAL-' . uniqid());
        $supplier->setTaxNumber('TAX-TOTAL-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationNumber('EVAL-TOTAL-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        self::getEntityManager()->persist($evaluation);

        $item1 = new EvaluationItem();
        $item1->setEvaluation($evaluation);
        $item1->setItemName('指标1');
        $item1->setItemType(EvaluationItemType::QUANTITATIVE);
        $item1->setWeight(25.0);
        $item1->setScore(80.0);
        $item1->setMaxScore(90.0);

        $item2 = new EvaluationItem();
        $item2->setEvaluation($evaluation);
        $item2->setItemName('指标2');
        $item2->setItemType(EvaluationItemType::QUANTITATIVE);
        $item2->setWeight(30.0);
        $item2->setScore(90.0);
        $item2->setMaxScore(85.0);

        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->flush();

        $totalWeight = $this->repository->getTotalWeightByEvaluation($evaluation);
        $this->assertEquals(55.0, $totalWeight);
    }

    public function testSaveAndRemove(): void
    {
        $item = $this->createNewEntity();

        $this->repository->save($item, true);
        $this->assertNotNull($item->getId());

        $found = $this->repository->find($item->getId());
        $this->assertInstanceOf(EvaluationItem::class, $found);
        $this->assertEquals('服务质量评估', $found->getItemName());

        $savedId = $item->getId();
        $this->repository->remove($item, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testRemove(): void
    {
        $item = $this->createNewEntity();
        self::getEntityManager()->persist($item);
        self::getEntityManager()->flush();

        $itemId = $item->getId();
        $this->assertNotNull($itemId);

        $foundBefore = $this->repository->find($itemId);
        $this->assertNotNull($foundBefore);
        $this->assertEquals('服务质量评估', $foundBefore->getItemName());

        $this->repository->remove($item, true);

        $foundAfter = $this->repository->find($itemId);
        $this->assertNull($foundAfter);
    }

    public function testCountByEvaluation(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Count By Evaluation');
        $supplier->setLegalName('Test Legal Name For Count');
        $supplier->setLegalAddress('Test Address For Count');
        $supplier->setRegistrationNumber('TEST-COUNT-EVAL-' . uniqid());
        $supplier->setTaxNumber('TAX-COUNT-EVAL-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试评估计数');
        $evaluation->setEvaluationNumber('EVAL-COUNT-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 为同一个评估创建多个评估项
        $item1 = new EvaluationItem();
        $item1->setEvaluation($evaluation);
        $item1->setItemName('评估项1');
        $item1->setItemType(EvaluationItemType::QUANTITATIVE);
        $item1->setWeight(25.0);
        $item1->setScore(85.0);
        $item1->setMaxScore(90.0);

        $item2 = new EvaluationItem();
        $item2->setEvaluation($evaluation);
        $item2->setItemName('评估项2');
        $item2->setItemType(EvaluationItemType::QUALITATIVE);
        $item2->setWeight(30.0);
        $item2->setScore(90.0);
        $item2->setMaxScore(100.0);

        $item3 = new EvaluationItem();
        $item3->setEvaluation($evaluation);
        $item3->setItemName('评估项3');
        $item3->setItemType(EvaluationItemType::QUANTITATIVE);
        $item3->setWeight(20.0);
        $item3->setScore(75.0);
        $item3->setMaxScore(80.0);

        self::getEntityManager()->persist($item1);
        self::getEntityManager()->persist($item2);
        self::getEntityManager()->persist($item3);
        self::getEntityManager()->flush();

        $count = $this->repository->countByEvaluation($evaluation);
        $this->assertEquals(3, $count);

        // 测试空评估的计数
        $emptyEvaluation = new PerformanceEvaluation();
        $emptyEvaluation->setSupplier($supplier);
        $emptyEvaluation->setTitle('空评估');
        $emptyEvaluation->setEvaluationNumber('EMPTY-EVAL-' . uniqid());
        $emptyEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $emptyEvaluation->setEvaluationPeriod('Q2-2024');
        $emptyEvaluation->setEvaluator('Test Evaluator');
        $emptyEvaluation->setOverallScore(0.0);
        $emptyEvaluation->setGrade(PerformanceGrade::C);
        $emptyEvaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($emptyEvaluation);
        self::getEntityManager()->flush();

        $emptyCount = $this->repository->countByEvaluation($emptyEvaluation);
        $this->assertEquals(0, $emptyCount);
    }

    public function testCountByItemType(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Count By Type');
        $supplier->setLegalName('Test Legal Name For Count Type');
        $supplier->setLegalAddress('Test Address For Count Type');
        $supplier->setRegistrationNumber('TEST-COUNT-TYPE-' . uniqid());
        $supplier->setTaxNumber('TAX-COUNT-TYPE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试类型计数评估');
        $evaluation->setEvaluationNumber('EVAL-TYPE-COUNT-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 创建不同类型的评估项
        $quantitative1 = new EvaluationItem();
        $quantitative1->setEvaluation($evaluation);
        $quantitative1->setItemName('定量评估项1');
        $quantitative1->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitative1->setWeight(25.0);
        $quantitative1->setScore(85.0);
        $quantitative1->setMaxScore(90.0);

        $quantitative2 = new EvaluationItem();
        $quantitative2->setEvaluation($evaluation);
        $quantitative2->setItemName('定量评估项2');
        $quantitative2->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitative2->setWeight(30.0);
        $quantitative2->setScore(90.0);
        $quantitative2->setMaxScore(100.0);

        $qualitative1 = new EvaluationItem();
        $qualitative1->setEvaluation($evaluation);
        $qualitative1->setItemName('定性评估项1');
        $qualitative1->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitative1->setWeight(20.0);
        $qualitative1->setScore(75.0);
        $qualitative1->setMaxScore(80.0);

        self::getEntityManager()->persist($quantitative1);
        self::getEntityManager()->persist($quantitative2);
        self::getEntityManager()->persist($qualitative1);
        self::getEntityManager()->flush();

        $quantitativeCount = $this->repository->countByItemType(EvaluationItemType::QUANTITATIVE->value);
        $this->assertGreaterThanOrEqual(2, $quantitativeCount); // 至少有我们刚创建的2个

        $qualitativeCount = $this->repository->countByItemType(EvaluationItemType::QUALITATIVE->value);
        $this->assertGreaterThanOrEqual(1, $qualitativeCount); // 至少有我们刚创建的1个

        // 测试不存在的类型
        $nonExistentCount = $this->repository->countByItemType('non-existent-type');
        $this->assertEquals(0, $nonExistentCount);
    }

    public function testFindHighWeightItems(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For High Weight');
        $supplier->setLegalName('Test Legal Name For High Weight');
        $supplier->setLegalAddress('Test Address For High Weight');
        $supplier->setRegistrationNumber('TEST-HIGH-WEIGHT-' . uniqid());
        $supplier->setTaxNumber('TAX-HIGH-WEIGHT-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试高权重评估');
        $evaluation->setEvaluationNumber('EVAL-HIGH-WEIGHT-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 创建不同权重的评估项
        $lowWeightItem = new EvaluationItem();
        $lowWeightItem->setEvaluation($evaluation);
        $lowWeightItem->setItemName('低权重项');
        $lowWeightItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $lowWeightItem->setWeight(10.0);
        $lowWeightItem->setScore(85.0);
        $lowWeightItem->setMaxScore(90.0);

        $midWeightItem = new EvaluationItem();
        $midWeightItem->setEvaluation($evaluation);
        $midWeightItem->setItemName('中权重项');
        $midWeightItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $midWeightItem->setWeight(25.0);
        $midWeightItem->setScore(90.0);
        $midWeightItem->setMaxScore(100.0);

        $highWeightItem = new EvaluationItem();
        $highWeightItem->setEvaluation($evaluation);
        $highWeightItem->setItemName('高权重项');
        $highWeightItem->setItemType(EvaluationItemType::QUALITATIVE);
        $highWeightItem->setWeight(35.0);
        $highWeightItem->setScore(75.0);
        $highWeightItem->setMaxScore(80.0);

        self::getEntityManager()->persist($lowWeightItem);
        self::getEntityManager()->persist($midWeightItem);
        self::getEntityManager()->persist($highWeightItem);
        self::getEntityManager()->flush();

        // 测试默认阈值（20.0）
        $defaultHighWeightItems = $this->repository->findHighWeightItems();
        $this->assertGreaterThanOrEqual(2, count($defaultHighWeightItems)); // 至少包含中权重项和高权重项
        $itemNames = array_map(fn ($item) => $item->getItemName(), $defaultHighWeightItems);
        $this->assertContains('中权重项', $itemNames);
        $this->assertContains('高权重项', $itemNames);
        $this->assertNotContains('低权重项', $itemNames);

        // 测试自定义阈值（30.0）
        $customHighWeightItems = $this->repository->findHighWeightItems(30.0);
        $this->assertGreaterThanOrEqual(1, count($customHighWeightItems)); // 至少包含高权重项
        $customItemNames = array_map(fn ($item) => $item->getItemName(), $customHighWeightItems);
        $this->assertContains('高权重项', $customItemNames);

        // 验证结果按权重降序排列
        if (count($defaultHighWeightItems) > 1) {
            for ($i = 0; $i < count($defaultHighWeightItems) - 1; ++$i) {
                $this->assertGreaterThanOrEqual(
                    $defaultHighWeightItems[$i + 1]->getWeight(),
                    $defaultHighWeightItems[$i]->getWeight()
                );
            }
        }
    }

    public function testFindLowScoreItems(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Low Score');
        $supplier->setLegalName('Test Legal Name For Low Score');
        $supplier->setLegalAddress('Test Address For Low Score');
        $supplier->setRegistrationNumber('TEST-LOW-SCORE-' . uniqid());
        $supplier->setTaxNumber('TAX-LOW-SCORE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试低分数评估');
        $evaluation->setEvaluationNumber('EVAL-LOW-SCORE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 创建不同分数的评估项
        $lowScoreItem = new EvaluationItem();
        $lowScoreItem->setEvaluation($evaluation);
        $lowScoreItem->setItemName('低分项');
        $lowScoreItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $lowScoreItem->setWeight(25.0);
        $lowScoreItem->setScore(45.0);
        $lowScoreItem->setMaxScore(90.0);

        $midScoreItem = new EvaluationItem();
        $midScoreItem->setEvaluation($evaluation);
        $midScoreItem->setItemName('中分项');
        $midScoreItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $midScoreItem->setWeight(30.0);
        $midScoreItem->setScore(65.0);
        $midScoreItem->setMaxScore(100.0);

        $highScoreItem = new EvaluationItem();
        $highScoreItem->setEvaluation($evaluation);
        $highScoreItem->setItemName('高分项');
        $highScoreItem->setItemType(EvaluationItemType::QUALITATIVE);
        $highScoreItem->setWeight(20.0);
        $highScoreItem->setScore(85.0);
        $highScoreItem->setMaxScore(80.0);

        self::getEntityManager()->persist($lowScoreItem);
        self::getEntityManager()->persist($midScoreItem);
        self::getEntityManager()->persist($highScoreItem);
        self::getEntityManager()->flush();

        // 测试默认阈值（60.0）
        $defaultLowScoreItems = $this->repository->findLowScoreItems();
        $this->assertGreaterThanOrEqual(1, count($defaultLowScoreItems)); // 至少包含低分项
        $itemNames = array_map(fn ($item) => $item->getItemName(), $defaultLowScoreItems);
        $this->assertContains('低分项', $itemNames);
        $this->assertNotContains('高分项', $itemNames);

        // 测试自定义阈值（70.0）
        $customLowScoreItems = $this->repository->findLowScoreItems(70.0);
        $this->assertGreaterThanOrEqual(2, count($customLowScoreItems)); // 至少包含低分项和中分项
        $customItemNames = array_map(fn ($item) => $item->getItemName(), $customLowScoreItems);
        $this->assertContains('低分项', $customItemNames);
        $this->assertContains('中分项', $customItemNames);

        // 验证结果按分数升序排列
        if (count($defaultLowScoreItems) > 1) {
            for ($i = 0; $i < count($defaultLowScoreItems) - 1; ++$i) {
                $this->assertLessThanOrEqual(
                    $defaultLowScoreItems[$i + 1]->getScore(),
                    $defaultLowScoreItems[$i]->getScore()
                );
            }
        }
    }

    public function testFindQualitativeItems(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Qualitative');
        $supplier->setLegalName('Test Legal Name For Qualitative');
        $supplier->setLegalAddress('Test Address For Qualitative');
        $supplier->setRegistrationNumber('TEST-QUALITATIVE-' . uniqid());
        $supplier->setTaxNumber('TAX-QUALITATIVE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试定性评估');
        $evaluation->setEvaluationNumber('EVAL-QUALITATIVE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 创建不同类型的评估项
        $quantitativeItem = new EvaluationItem();
        $quantitativeItem->setEvaluation($evaluation);
        $quantitativeItem->setItemName('定量评估项');
        $quantitativeItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitativeItem->setWeight(25.0);
        $quantitativeItem->setScore(85.0);
        $quantitativeItem->setMaxScore(90.0);

        $qualitativeItem1 = new EvaluationItem();
        $qualitativeItem1->setEvaluation($evaluation);
        $qualitativeItem1->setItemName('定性评估项1');
        $qualitativeItem1->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitativeItem1->setWeight(30.0);
        $qualitativeItem1->setScore(90.0);
        $qualitativeItem1->setMaxScore(100.0);

        $qualitativeItem2 = new EvaluationItem();
        $qualitativeItem2->setEvaluation($evaluation);
        $qualitativeItem2->setItemName('定性评估项2');
        $qualitativeItem2->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitativeItem2->setWeight(20.0);
        $qualitativeItem2->setScore(75.0);
        $qualitativeItem2->setMaxScore(80.0);

        self::getEntityManager()->persist($quantitativeItem);
        self::getEntityManager()->persist($qualitativeItem1);
        self::getEntityManager()->persist($qualitativeItem2);
        self::getEntityManager()->flush();

        $qualitativeItems = $this->repository->findQualitativeItems();
        $this->assertGreaterThanOrEqual(2, count($qualitativeItems)); // 至少包含我们创建的2个定性评估项

        $itemNames = array_map(fn ($item) => $item->getItemName(), $qualitativeItems);
        $this->assertContains('定性评估项1', $itemNames);
        $this->assertContains('定性评估项2', $itemNames);
        $this->assertNotContains('定量评估项', $itemNames);

        // 验证所有返回的项都是定性评估项
        foreach ($qualitativeItems as $item) {
            $this->assertEquals(EvaluationItemType::QUALITATIVE, $item->getItemType());
        }

        // 验证结果按权重降序排列
        if (count($qualitativeItems) > 1) {
            for ($i = 0; $i < count($qualitativeItems) - 1; ++$i) {
                $this->assertGreaterThanOrEqual(
                    $qualitativeItems[$i + 1]->getWeight(),
                    $qualitativeItems[$i]->getWeight()
                );
            }
        }
    }

    public function testFindQuantitativeItems(): void
    {
        // 创建一个供应商和评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Quantitative');
        $supplier->setLegalName('Test Legal Name For Quantitative');
        $supplier->setLegalAddress('Test Address For Quantitative');
        $supplier->setRegistrationNumber('TEST-QUANTITATIVE-' . uniqid());
        $supplier->setTaxNumber('TAX-QUANTITATIVE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('测试定量评估');
        $evaluation->setEvaluationNumber('EVAL-QUANTITATIVE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        self::getEntityManager()->persist($evaluation);

        // 创建不同类型的评估项
        $qualitativeItem = new EvaluationItem();
        $qualitativeItem->setEvaluation($evaluation);
        $qualitativeItem->setItemName('定性评估项');
        $qualitativeItem->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitativeItem->setWeight(25.0);
        $qualitativeItem->setScore(85.0);
        $qualitativeItem->setMaxScore(90.0);

        $quantitativeItem1 = new EvaluationItem();
        $quantitativeItem1->setEvaluation($evaluation);
        $quantitativeItem1->setItemName('定量评估项1');
        $quantitativeItem1->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitativeItem1->setWeight(30.0);
        $quantitativeItem1->setScore(90.0);
        $quantitativeItem1->setMaxScore(100.0);

        $quantitativeItem2 = new EvaluationItem();
        $quantitativeItem2->setEvaluation($evaluation);
        $quantitativeItem2->setItemName('定量评估项2');
        $quantitativeItem2->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitativeItem2->setWeight(20.0);
        $quantitativeItem2->setScore(75.0);
        $quantitativeItem2->setMaxScore(80.0);

        self::getEntityManager()->persist($qualitativeItem);
        self::getEntityManager()->persist($quantitativeItem1);
        self::getEntityManager()->persist($quantitativeItem2);
        self::getEntityManager()->flush();

        $quantitativeItems = $this->repository->findQuantitativeItems();
        $this->assertGreaterThanOrEqual(2, count($quantitativeItems)); // 至少包含我们创建的2个定量评估项

        $itemNames = array_map(fn ($item) => $item->getItemName(), $quantitativeItems);
        $this->assertContains('定量评估项1', $itemNames);
        $this->assertContains('定量评估项2', $itemNames);
        $this->assertNotContains('定性评估项', $itemNames);

        // 验证所有返回的项都是定量评估项
        foreach ($quantitativeItems as $item) {
            $this->assertEquals(EvaluationItemType::QUANTITATIVE, $item->getItemType());
        }

        // 验证结果按权重降序排列
        if (count($quantitativeItems) > 1) {
            for ($i = 0; $i < count($quantitativeItems) - 1; ++$i) {
                $this->assertGreaterThanOrEqual(
                    $quantitativeItems[$i + 1]->getWeight(),
                    $quantitativeItems[$i]->getWeight()
                );
            }
        }
    }
}
