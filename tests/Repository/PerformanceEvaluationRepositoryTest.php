<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Repository\PerformanceEvaluationRepository;

/**
 * @internal
 */
#[CoversClass(PerformanceEvaluationRepository::class)]
#[RunTestsInSeparateProcesses]
class PerformanceEvaluationRepositoryTest extends AbstractRepositoryTestCase
{
    private PerformanceEvaluationRepository $repository;

    protected function createNewEntity(): object
    {
        $supplier = new Supplier();
        $supplier->setName('测试供应商');
        $supplier->setLegalName('测试法人');
        $supplier->setLegalAddress('测试地址');
        $supplier->setRegistrationNumber('REG' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());
        $supplier->setSupplierType(SupplierType::SUPPLIER);

        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('E' . uniqid());
        $evaluation->setTitle('测试评估');
        $evaluation->setEvaluationPeriod('2024年度');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2024-12-31'));
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $evaluation->setEvaluator('测试评估员');
        $evaluation->setSummary('测试评论');

        return $evaluation;
    }

    /**
     * @return ServiceEntityRepository<PerformanceEvaluation>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PerformanceEvaluationRepository::class);

        // 创建数据库表结构
        $this->createSchema();

        // 清理现有的评估数据，确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . PerformanceEvaluation::class . ' e')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . Supplier::class . ' s')->execute();

        // 创建一个 DataFixture 测试数据以满足基类测试要求
        $supplier = new Supplier();
        $supplier->setName('DataFixture Test Supplier');
        $supplier->setLegalName('DataFixture Test Legal');
        $supplier->setLegalAddress('DataFixture Test Address');
        $supplier->setRegistrationNumber('DATA-FIXTURE-' . uniqid());
        $supplier->setTaxNumber('DATA-FIXTURE-TAX-' . uniqid());
        $supplier->setSupplierType(SupplierType::SUPPLIER);

        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('DATA-FIXTURE-EVAL-' . uniqid());
        $evaluation->setTitle('DataFixture Test Evaluation');
        $evaluation->setEvaluationPeriod('2024年度');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2024-12-31'));
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $evaluation->setEvaluator('DataFixture Test Evaluator');
        $evaluation->setSummary('DataFixture Test Summary');

        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->flush();
    }

    /**
     * 创建数据库表结构
     */
    private function createSchema(): void
    {
        $entityManager = self::getEntityManager();
        $schemaTool = new SchemaTool($entityManager);

        $metadata = [
            $entityManager->getClassMetadata(Supplier::class),
            $entityManager->getClassMetadata(PerformanceEvaluation::class),
        ];

        try {
            $schemaTool->dropSchema($metadata);
        } catch (\Exception $e) {
            // 忽略删除表时的错误，表可能不存在
        }

        $schemaTool->createSchema($metadata);
    }

    public function testFindBySupplier(): void
    {
        // 创建一个供应商和两个评估
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For FindBy');
        $supplier->setLegalName('Test Legal Name For FindBy');
        $supplier->setLegalAddress('Test Address For FindBy');
        $supplier->setRegistrationNumber('TEST-FIND-BY-' . uniqid());
        $supplier->setTaxNumber('TAX-FIND-BY-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation1 = new PerformanceEvaluation();
        $evaluation1->setSupplier($supplier);
        $evaluation1->setTitle('第一次评估');
        $evaluation1->setEvaluationNumber('EVAL-1-' . uniqid());
        $evaluation1->setEvaluationDate(new \DateTimeImmutable());
        $evaluation1->setEvaluationPeriod('Q1-2024');
        $evaluation1->setEvaluator('Test Evaluator');
        $evaluation1->setOverallScore(85.5);
        $evaluation1->setGrade(PerformanceGrade::B);

        $evaluation2 = new PerformanceEvaluation();
        $evaluation2->setSupplier($supplier);
        $evaluation2->setTitle('第二次评估');
        $evaluation2->setEvaluationNumber('EVAL-2-' . uniqid());
        $evaluation2->setEvaluationDate(new \DateTimeImmutable('+1 day'));
        $evaluation2->setEvaluationPeriod('Q2-2024');
        $evaluation2->setEvaluator('Test Evaluator');
        $evaluation2->setOverallScore(90.0);
        $evaluation2->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($evaluation1);
        self::getEntityManager()->persist($evaluation2);
        self::getEntityManager()->flush();

        $evaluations = $this->repository->findBySupplier($supplier);
        $this->assertCount(2, $evaluations);
        $this->assertEquals('第二次评估', $evaluations[0]->getTitle()); // 按日期降序排列
        $this->assertEquals('第一次评估', $evaluations[1]->getTitle());
    }

    public function testFindByStatus(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Status');
        $supplier->setLegalName('Test Legal Name For Status');
        $supplier->setLegalAddress('Test Address For Status');
        $supplier->setRegistrationNumber('TEST-STATUS-' . uniqid());
        $supplier->setTaxNumber('TAX-STATUS-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $draftEvaluation = new PerformanceEvaluation();
        $draftEvaluation->setSupplier($supplier);
        $draftEvaluation->setTitle('草稿评估');
        $draftEvaluation->setEvaluationNumber('DRAFT-' . uniqid());
        $draftEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $draftEvaluation->setEvaluationPeriod('Q1-2024');
        $draftEvaluation->setEvaluator('Test Evaluator');
        $draftEvaluation->setOverallScore(85.5);
        $draftEvaluation->setGrade(PerformanceGrade::B);
        $draftEvaluation->setStatus(PerformanceEvaluationStatus::DRAFT);

        $completedEvaluation = new PerformanceEvaluation();
        $completedEvaluation->setSupplier($supplier);
        $completedEvaluation->setTitle('已完成评估');
        $completedEvaluation->setEvaluationNumber('COMPLETED-' . uniqid());
        $completedEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $completedEvaluation->setEvaluationPeriod('Q1-2024');
        $completedEvaluation->setEvaluator('Test Evaluator');
        $completedEvaluation->setOverallScore(90.0);
        $completedEvaluation->setGrade(PerformanceGrade::A);
        $completedEvaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        self::getEntityManager()->persist($draftEvaluation);
        self::getEntityManager()->persist($completedEvaluation);
        self::getEntityManager()->flush();

        $draftEvaluations = $this->repository->findByStatus('draft');
        $this->assertCount(2, $draftEvaluations);  // 包括 DataFixture 中创建的一个 draft 状态评估
        // 验证我们创建的测试评估在结果中
        $titles = array_map(fn ($eval) => $eval->getTitle(), $draftEvaluations);
        $this->assertContains('草稿评估', $titles);

        $completedEvaluations = $this->repository->findByStatus('confirmed');
        $this->assertCount(1, $completedEvaluations);
        $this->assertEquals('已完成评估', $completedEvaluations[0]->getTitle());
    }

    public function testFindByPeriod(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Period');
        $supplier->setLegalName('Test Legal Name For Period');
        $supplier->setLegalAddress('Test Address For Period');
        $supplier->setRegistrationNumber('TEST-PERIOD-' . uniqid());
        $supplier->setTaxNumber('TAX-PERIOD-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $q1Evaluation = new PerformanceEvaluation();
        $q1Evaluation->setSupplier($supplier);
        $q1Evaluation->setTitle('Q1评估');
        $q1Evaluation->setEvaluationNumber('Q1-' . uniqid());
        $q1Evaluation->setEvaluationDate(new \DateTimeImmutable());
        $q1Evaluation->setEvaluationPeriod('Q1-2024');
        $q1Evaluation->setEvaluator('Test Evaluator');
        $q1Evaluation->setOverallScore(85.5);
        $q1Evaluation->setGrade(PerformanceGrade::B);

        $q2Evaluation = new PerformanceEvaluation();
        $q2Evaluation->setSupplier($supplier);
        $q2Evaluation->setTitle('Q2评估');
        $q2Evaluation->setEvaluationNumber('Q2-' . uniqid());
        $q2Evaluation->setEvaluationDate(new \DateTimeImmutable());
        $q2Evaluation->setEvaluationPeriod('Q2-2024');
        $q2Evaluation->setEvaluator('Test Evaluator');
        $q2Evaluation->setOverallScore(90.0);
        $q2Evaluation->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($q1Evaluation);
        self::getEntityManager()->persist($q2Evaluation);
        self::getEntityManager()->flush();

        $q1Evaluations = $this->repository->findByPeriod('Q1-2024');
        $this->assertCount(1, $q1Evaluations);
        $this->assertEquals('Q1-2024', $q1Evaluations[0]->getEvaluationPeriod());

        $q2Evaluations = $this->repository->findByPeriod('Q2-2024');
        $this->assertCount(1, $q2Evaluations);
        $this->assertEquals('Q2-2024', $q2Evaluations[0]->getEvaluationPeriod());
    }

    public function testFindByGrade(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Grade');
        $supplier->setLegalName('Test Legal Name For Grade');
        $supplier->setLegalAddress('Test Address For Grade');
        $supplier->setRegistrationNumber('TEST-GRADE-' . uniqid());
        $supplier->setTaxNumber('TAX-GRADE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $gradeAEvaluation = new PerformanceEvaluation();
        $gradeAEvaluation->setSupplier($supplier);
        $gradeAEvaluation->setTitle('A级评估');
        $gradeAEvaluation->setEvaluationNumber('GRADE-A-' . uniqid());
        $gradeAEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $gradeAEvaluation->setEvaluationPeriod('Q1-2024');
        $gradeAEvaluation->setEvaluator('Test Evaluator');
        $gradeAEvaluation->setOverallScore(95.0);
        $gradeAEvaluation->setGrade(PerformanceGrade::A);

        $gradeBEvaluation = new PerformanceEvaluation();
        $gradeBEvaluation->setSupplier($supplier);
        $gradeBEvaluation->setTitle('B级评估');
        $gradeBEvaluation->setEvaluationNumber('GRADE-B-' . uniqid());
        $gradeBEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $gradeBEvaluation->setEvaluationPeriod('Q1-2024');
        $gradeBEvaluation->setEvaluator('Test Evaluator');
        $gradeBEvaluation->setOverallScore(85.0);
        $gradeBEvaluation->setGrade(PerformanceGrade::B);

        self::getEntityManager()->persist($gradeAEvaluation);
        self::getEntityManager()->persist($gradeBEvaluation);
        self::getEntityManager()->flush();

        $gradeAEvaluations = $this->repository->findByGrade('A');
        $this->assertCount(1, $gradeAEvaluations);
        $this->assertEquals(PerformanceGrade::A, $gradeAEvaluations[0]->getGrade());

        $gradeBEvaluations = $this->repository->findByGrade('B');
        $this->assertCount(2, $gradeBEvaluations);  // 包括 DataFixture 中创建的一个 B 级评估
        // 验证我们创建的测试评估在结果中
        $titles = array_map(fn ($eval) => $eval->getTitle(), $gradeBEvaluations);
        $this->assertContains('B级评估', $titles);
    }

    public function testFindByEvaluator(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Evaluator');
        $supplier->setLegalName('Test Legal Name For Evaluator');
        $supplier->setLegalAddress('Test Address For Evaluator');
        $supplier->setRegistrationNumber('TEST-EVALUATOR-' . uniqid());
        $supplier->setTaxNumber('TAX-EVALUATOR-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation1 = new PerformanceEvaluation();
        $evaluation1->setSupplier($supplier);
        $evaluation1->setTitle('张三的评估');
        $evaluation1->setEvaluationNumber('EVAL-ZHANG-' . uniqid());
        $evaluation1->setEvaluationDate(new \DateTimeImmutable());
        $evaluation1->setEvaluationPeriod('Q1-2024');
        $evaluation1->setEvaluator('张三');
        $evaluation1->setOverallScore(85.5);
        $evaluation1->setGrade(PerformanceGrade::B);

        $evaluation2 = new PerformanceEvaluation();
        $evaluation2->setSupplier($supplier);
        $evaluation2->setTitle('李四的评估');
        $evaluation2->setEvaluationNumber('EVAL-LI-' . uniqid());
        $evaluation2->setEvaluationDate(new \DateTimeImmutable());
        $evaluation2->setEvaluationPeriod('Q1-2024');
        $evaluation2->setEvaluator('李四');
        $evaluation2->setOverallScore(90.0);
        $evaluation2->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($evaluation1);
        self::getEntityManager()->persist($evaluation2);
        self::getEntityManager()->flush();

        $zhangSanEvaluations = $this->repository->findByEvaluator('张三');
        $this->assertCount(1, $zhangSanEvaluations);
        $this->assertEquals('张三', $zhangSanEvaluations[0]->getEvaluator());

        $liSiEvaluations = $this->repository->findByEvaluator('李四');
        $this->assertCount(1, $liSiEvaluations);
        $this->assertEquals('李四的评估', $liSiEvaluations[0]->getTitle());
    }

    public function testFindByDateRange(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Date Range');
        $supplier->setLegalName('Test Legal Name For Date Range');
        $supplier->setLegalAddress('Test Address For Date Range');
        $supplier->setRegistrationNumber('TEST-DATERANGE-' . uniqid());
        $supplier->setTaxNumber('TAX-DATERANGE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation1 = new PerformanceEvaluation();
        $evaluation1->setSupplier($supplier);
        $evaluation1->setTitle('1月评估');
        $evaluation1->setEvaluationNumber('JAN-' . uniqid());
        $evaluation1->setEvaluationDate(new \DateTimeImmutable('2024-01-15'));
        $evaluation1->setEvaluationPeriod('Q1-2024');
        $evaluation1->setEvaluator('Test Evaluator');
        $evaluation1->setOverallScore(85.5);
        $evaluation1->setGrade(PerformanceGrade::B);

        $evaluation2 = new PerformanceEvaluation();
        $evaluation2->setSupplier($supplier);
        $evaluation2->setTitle('2月评估');
        $evaluation2->setEvaluationNumber('FEB-' . uniqid());
        $evaluation2->setEvaluationDate(new \DateTimeImmutable('2024-02-15'));
        $evaluation2->setEvaluationPeriod('Q1-2024');
        $evaluation2->setEvaluator('Test Evaluator');
        $evaluation2->setOverallScore(90.0);
        $evaluation2->setGrade(PerformanceGrade::A);

        $evaluation3 = new PerformanceEvaluation();
        $evaluation3->setSupplier($supplier);
        $evaluation3->setTitle('3月评估');
        $evaluation3->setEvaluationNumber('MAR-' . uniqid());
        $evaluation3->setEvaluationDate(new \DateTimeImmutable('2024-03-15'));
        $evaluation3->setEvaluationPeriod('Q1-2024');
        $evaluation3->setEvaluator('Test Evaluator');
        $evaluation3->setOverallScore(88.0);
        $evaluation3->setGrade(PerformanceGrade::B);

        self::getEntityManager()->persist($evaluation1);
        self::getEntityManager()->persist($evaluation2);
        self::getEntityManager()->persist($evaluation3);
        self::getEntityManager()->flush();

        $janFebEvaluations = $this->repository->findByDateRange(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-02-28')
        );
        $this->assertCount(2, $janFebEvaluations);
    }

    public function testFindByScoreRange(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Score Range');
        $supplier->setLegalName('Test Legal Name For Score Range');
        $supplier->setLegalAddress('Test Address For Score Range');
        $supplier->setRegistrationNumber('TEST-SCORERANGE-' . uniqid());
        $supplier->setTaxNumber('TAX-SCORERANGE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $lowScoreEvaluation = new PerformanceEvaluation();
        $lowScoreEvaluation->setSupplier($supplier);
        $lowScoreEvaluation->setTitle('低分评估');
        $lowScoreEvaluation->setEvaluationNumber('LOW-' . uniqid());
        $lowScoreEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $lowScoreEvaluation->setEvaluationPeriod('Q1-2024');
        $lowScoreEvaluation->setEvaluator('Test Evaluator');
        $lowScoreEvaluation->setOverallScore(60.0);
        $lowScoreEvaluation->setGrade(PerformanceGrade::D);

        $midScoreEvaluation = new PerformanceEvaluation();
        $midScoreEvaluation->setSupplier($supplier);
        $midScoreEvaluation->setTitle('中等评分评估');
        $midScoreEvaluation->setEvaluationNumber('MID-' . uniqid());
        $midScoreEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $midScoreEvaluation->setEvaluationPeriod('Q1-2024');
        $midScoreEvaluation->setEvaluator('Test Evaluator');
        $midScoreEvaluation->setOverallScore(80.0);
        $midScoreEvaluation->setGrade(PerformanceGrade::C);

        $highScoreEvaluation = new PerformanceEvaluation();
        $highScoreEvaluation->setSupplier($supplier);
        $highScoreEvaluation->setTitle('高分评估');
        $highScoreEvaluation->setEvaluationNumber('HIGH-' . uniqid());
        $highScoreEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $highScoreEvaluation->setEvaluationPeriod('Q1-2024');
        $highScoreEvaluation->setEvaluator('Test Evaluator');
        $highScoreEvaluation->setOverallScore(95.0);
        $highScoreEvaluation->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($lowScoreEvaluation);
        self::getEntityManager()->persist($midScoreEvaluation);
        self::getEntityManager()->persist($highScoreEvaluation);
        self::getEntityManager()->flush();

        $midRangeEvaluations = $this->repository->findByScoreRange(75.0, 85.0);
        $this->assertCount(1, $midRangeEvaluations);
        $this->assertEquals('中等评分评估', $midRangeEvaluations[0]->getTitle());
    }

    public function testFindByEvaluationNumber(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Evaluation Number');
        $supplier->setLegalName('Test Legal Name For Evaluation Number');
        $supplier->setLegalAddress('Test Address For Evaluation Number');
        $supplier->setRegistrationNumber('TEST-EVALNUM-' . uniqid());
        $supplier->setTaxNumber('TAX-EVALNUM-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluationNumber = 'UNIQUE-EVAL-' . uniqid();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('唯一编号评估');
        $evaluation->setEvaluationNumber($evaluationNumber);
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->flush();

        $found = $this->repository->findByEvaluationNumber($evaluationNumber);
        $this->assertNotNull($found);
        $this->assertEquals($evaluationNumber, $found->getEvaluationNumber());

        $notFound = $this->repository->findByEvaluationNumber('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testCountBySupplier(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Count');
        $supplier->setLegalName('Test Legal Name For Count');
        $supplier->setLegalAddress('Test Address For Count');
        $supplier->setRegistrationNumber('TEST-COUNT-' . uniqid());
        $supplier->setTaxNumber('TAX-COUNT-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation1 = new PerformanceEvaluation();
        $evaluation1->setSupplier($supplier);
        $evaluation1->setTitle('第一次评估');
        $evaluation1->setEvaluationNumber('FIRST-' . uniqid());
        $evaluation1->setEvaluationDate(new \DateTimeImmutable());
        $evaluation1->setEvaluationPeriod('Q1-2024');
        $evaluation1->setEvaluator('Test Evaluator');
        $evaluation1->setOverallScore(85.5);
        $evaluation1->setGrade(PerformanceGrade::B);

        $evaluation2 = new PerformanceEvaluation();
        $evaluation2->setSupplier($supplier);
        $evaluation2->setTitle('第二次评估');
        $evaluation2->setEvaluationNumber('SECOND-' . uniqid());
        $evaluation2->setEvaluationDate(new \DateTimeImmutable());
        $evaluation2->setEvaluationPeriod('Q1-2024');
        $evaluation2->setEvaluator('Test Evaluator');
        $evaluation2->setOverallScore(90.0);
        $evaluation2->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($evaluation1);
        self::getEntityManager()->persist($evaluation2);
        self::getEntityManager()->flush();

        $count = $this->repository->countBySupplier($supplier);
        $this->assertEquals(2, $count);
    }

    public function testGetLatestBySupplier(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Latest');
        $supplier->setLegalName('Test Legal Name For Latest');
        $supplier->setLegalAddress('Test Address For Latest');
        $supplier->setRegistrationNumber('TEST-LATEST-' . uniqid());
        $supplier->setTaxNumber('TAX-LATEST-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $oldEvaluation = new PerformanceEvaluation();
        $oldEvaluation->setSupplier($supplier);
        $oldEvaluation->setTitle('旧评估');
        $oldEvaluation->setEvaluationNumber('OLD-' . uniqid());
        $oldEvaluation->setEvaluationDate(new \DateTimeImmutable('-1 month'));
        $oldEvaluation->setEvaluationPeriod('Q1-2024');
        $oldEvaluation->setEvaluator('Test Evaluator');
        $oldEvaluation->setOverallScore(85.5);
        $oldEvaluation->setGrade(PerformanceGrade::B);

        $latestEvaluation = new PerformanceEvaluation();
        $latestEvaluation->setSupplier($supplier);
        $latestEvaluation->setTitle('最新评估');
        $latestEvaluation->setEvaluationNumber('LATEST-' . uniqid());
        $latestEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $latestEvaluation->setEvaluationPeriod('Q2-2024');
        $latestEvaluation->setEvaluator('Test Evaluator');
        $latestEvaluation->setOverallScore(90.0);
        $latestEvaluation->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($oldEvaluation);
        self::getEntityManager()->persist($latestEvaluation);
        self::getEntityManager()->flush();

        $latest = $this->repository->getLatestBySupplier($supplier);
        $this->assertNotNull($latest);
        $this->assertEquals('最新评估', $latest->getTitle());
    }

    public function testSearch(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Search');
        $supplier->setLegalName('Test Legal Name For Search');
        $supplier->setLegalAddress('Test Address For Search');
        $supplier->setRegistrationNumber('TEST-SEARCH-' . uniqid());
        $supplier->setTaxNumber('TAX-SEARCH-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('质量管理体系评估');
        $evaluation->setEvaluationNumber('QMS-2024-001');
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('张质量经理');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->flush();

        $resultsByTitle = $this->repository->search('质量管理');
        $this->assertCount(1, $resultsByTitle);
        $this->assertEquals('质量管理体系评估', $resultsByTitle[0]->getTitle());

        $resultsByNumber = $this->repository->search('QMS-2024');
        $this->assertCount(1, $resultsByNumber);
        $this->assertEquals('QMS-2024-001', $resultsByNumber[0]->getEvaluationNumber());

        $resultsByEvaluator = $this->repository->search('张质量');
        $this->assertCount(1, $resultsByEvaluator);
        $this->assertEquals('张质量经理', $resultsByEvaluator[0]->getEvaluator());
    }

    public function testFindHighPerformers(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For High Performers');
        $supplier->setLegalName('Test Legal Name For High Performers');
        $supplier->setLegalAddress('Test Address For High Performers');
        $supplier->setRegistrationNumber('TEST-HIGH-' . uniqid());
        $supplier->setTaxNumber('TAX-HIGH-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $highPerformer = new PerformanceEvaluation();
        $highPerformer->setSupplier($supplier);
        $highPerformer->setTitle('高分评估');
        $highPerformer->setEvaluationNumber('HIGH-' . uniqid());
        $highPerformer->setEvaluationDate(new \DateTimeImmutable());
        $highPerformer->setEvaluationPeriod('Q1-2024');
        $highPerformer->setEvaluator('Test Evaluator');
        $highPerformer->setOverallScore(95.0);
        $highPerformer->setGrade(PerformanceGrade::A);

        $lowPerformer = new PerformanceEvaluation();
        $lowPerformer->setSupplier($supplier);
        $lowPerformer->setTitle('低分评估');
        $lowPerformer->setEvaluationNumber('LOW-' . uniqid());
        $lowPerformer->setEvaluationDate(new \DateTimeImmutable());
        $lowPerformer->setEvaluationPeriod('Q1-2024');
        $lowPerformer->setEvaluator('Test Evaluator');
        $lowPerformer->setOverallScore(75.0);
        $lowPerformer->setGrade(PerformanceGrade::C);

        self::getEntityManager()->persist($highPerformer);
        self::getEntityManager()->persist($lowPerformer);
        self::getEntityManager()->flush();

        $highPerformers = $this->repository->findHighPerformers(90.0);
        $this->assertCount(1, $highPerformers);
        $this->assertEquals(95.0, $highPerformers[0]->getOverallScore());
    }

    public function testFindLowPerformers(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Low Performers');
        $supplier->setLegalName('Test Legal Name For Low Performers');
        $supplier->setLegalAddress('Test Address For Low Performers');
        $supplier->setRegistrationNumber('TEST-LOW-' . uniqid());
        $supplier->setTaxNumber('TAX-LOW-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $highPerformer = new PerformanceEvaluation();
        $highPerformer->setSupplier($supplier);
        $highPerformer->setTitle('高分评估');
        $highPerformer->setEvaluationNumber('HIGH-' . uniqid());
        $highPerformer->setEvaluationDate(new \DateTimeImmutable());
        $highPerformer->setEvaluationPeriod('Q1-2024');
        $highPerformer->setEvaluator('Test Evaluator');
        $highPerformer->setOverallScore(95.0);
        $highPerformer->setGrade(PerformanceGrade::A);

        $lowPerformer = new PerformanceEvaluation();
        $lowPerformer->setSupplier($supplier);
        $lowPerformer->setTitle('低分评估');
        $lowPerformer->setEvaluationNumber('LOW-' . uniqid());
        $lowPerformer->setEvaluationDate(new \DateTimeImmutable());
        $lowPerformer->setEvaluationPeriod('Q1-2024');
        $lowPerformer->setEvaluator('Test Evaluator');
        $lowPerformer->setOverallScore(55.0);
        $lowPerformer->setGrade(PerformanceGrade::D);

        self::getEntityManager()->persist($highPerformer);
        self::getEntityManager()->persist($lowPerformer);
        self::getEntityManager()->flush();

        $lowPerformers = $this->repository->findLowPerformers(60.0);
        $this->assertCount(1, $lowPerformers);
        $this->assertEquals(55.0, $lowPerformers[0]->getOverallScore());
    }

    public function testGetAverageScoreBySupplier(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Average Score');
        $supplier->setLegalName('Test Legal Name For Average Score');
        $supplier->setLegalAddress('Test Address For Average Score');
        $supplier->setRegistrationNumber('TEST-AVG-' . uniqid());
        $supplier->setTaxNumber('TAX-AVG-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation1 = new PerformanceEvaluation();
        $evaluation1->setSupplier($supplier);
        $evaluation1->setTitle('第一次评估');
        $evaluation1->setEvaluationNumber('FIRST-' . uniqid());
        $evaluation1->setEvaluationDate(new \DateTimeImmutable());
        $evaluation1->setEvaluationPeriod('Q1-2024');
        $evaluation1->setEvaluator('Test Evaluator');
        $evaluation1->setOverallScore(80.0);
        $evaluation1->setGrade(PerformanceGrade::B);

        $evaluation2 = new PerformanceEvaluation();
        $evaluation2->setSupplier($supplier);
        $evaluation2->setTitle('第二次评估');
        $evaluation2->setEvaluationNumber('SECOND-' . uniqid());
        $evaluation2->setEvaluationDate(new \DateTimeImmutable());
        $evaluation2->setEvaluationPeriod('Q1-2024');
        $evaluation2->setEvaluator('Test Evaluator');
        $evaluation2->setOverallScore(90.0);
        $evaluation2->setGrade(PerformanceGrade::A);

        self::getEntityManager()->persist($evaluation1);
        self::getEntityManager()->persist($evaluation2);
        self::getEntityManager()->flush();

        $averageScore = $this->repository->getAverageScoreBySupplier($supplier);
        $this->assertEquals(85.0, $averageScore);
    }

    public function testGetGradeDistribution(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Grade Distribution');
        $supplier->setLegalName('Test Legal Name For Grade Distribution');
        $supplier->setLegalAddress('Test Address For Grade Distribution');
        $supplier->setRegistrationNumber('TEST-GRADE-DIST-' . uniqid());
        $supplier->setTaxNumber('TAX-GRADE-DIST-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $gradeAEvaluation1 = new PerformanceEvaluation();
        $gradeAEvaluation1->setSupplier($supplier);
        $gradeAEvaluation1->setTitle('A级评估1');
        $gradeAEvaluation1->setEvaluationNumber('A1-' . uniqid());
        $gradeAEvaluation1->setEvaluationDate(new \DateTimeImmutable());
        $gradeAEvaluation1->setEvaluationPeriod('Q1-2024');
        $gradeAEvaluation1->setEvaluator('Test Evaluator');
        $gradeAEvaluation1->setOverallScore(95.0);
        $gradeAEvaluation1->setGrade(PerformanceGrade::A);

        $gradeAEvaluation2 = new PerformanceEvaluation();
        $gradeAEvaluation2->setSupplier($supplier);
        $gradeAEvaluation2->setTitle('A级评估2');
        $gradeAEvaluation2->setEvaluationNumber('A2-' . uniqid());
        $gradeAEvaluation2->setEvaluationDate(new \DateTimeImmutable());
        $gradeAEvaluation2->setEvaluationPeriod('Q1-2024');
        $gradeAEvaluation2->setEvaluator('Test Evaluator');
        $gradeAEvaluation2->setOverallScore(92.0);
        $gradeAEvaluation2->setGrade(PerformanceGrade::A);

        $gradeBEvaluation = new PerformanceEvaluation();
        $gradeBEvaluation->setSupplier($supplier);
        $gradeBEvaluation->setTitle('B级评估');
        $gradeBEvaluation->setEvaluationNumber('B1-' . uniqid());
        $gradeBEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $gradeBEvaluation->setEvaluationPeriod('Q1-2024');
        $gradeBEvaluation->setEvaluator('Test Evaluator');
        $gradeBEvaluation->setOverallScore(85.0);
        $gradeBEvaluation->setGrade(PerformanceGrade::B);

        self::getEntityManager()->persist($gradeAEvaluation1);
        self::getEntityManager()->persist($gradeAEvaluation2);
        self::getEntityManager()->persist($gradeBEvaluation);
        self::getEntityManager()->flush();

        $distribution = $this->repository->getGradeDistribution();

        $this->assertArrayHasKey('A', $distribution);
        $this->assertArrayHasKey('B', $distribution);
        $this->assertEquals(2, $distribution['A']);
        $this->assertEquals(2, $distribution['B']);  // 包括 DataFixture 中的 1 个 B 级评估
    }

    public function testSaveAndRemove(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Save Remove');
        $supplier->setLegalName('Test Legal Name For Save Remove');
        $supplier->setLegalAddress('Test Address For Save Remove');
        $supplier->setRegistrationNumber('TEST-SAVE-REMOVE-' . uniqid());
        $supplier->setTaxNumber('TAX-SAVE-REMOVE-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setTitle('供应商绩效评估');
        $evaluation->setEvaluationNumber('SAVE-REMOVE-' . uniqid());
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluationPeriod('Q1-2024');
        $evaluation->setEvaluator('Test Evaluator');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        $this->repository->save($evaluation, true);
        $this->assertNotNull($evaluation->getId());

        $found = $this->repository->find($evaluation->getId());
        $this->assertInstanceOf(PerformanceEvaluation::class, $found);
        $this->assertEquals('供应商绩效评估', $found->getTitle());

        $savedId = $evaluation->getId();
        $this->repository->remove($evaluation, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testFindExistingEvaluationById(): void
    {
        $supplier = new Supplier();
        $supplier->setName('测试供应商');
        $supplier->setLegalName('测试法人');
        $supplier->setLegalAddress('测试地址');
        $supplier->setRegistrationNumber('REG' . time());
        $supplier->setTaxNumber('TAX' . time());
        $supplier->setSupplierType(SupplierType::SUPPLIER);
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('E' . time());
        $evaluation->setTitle('测试查找评估');
        $evaluation->setEvaluationPeriod('2024年度');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2024-12-31'));
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $evaluation->setEvaluator('测试评估员');
        $evaluation->setSummary('测试评论');

        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->flush();

        $found = $this->repository->find($evaluation->getId());
        $this->assertInstanceOf(PerformanceEvaluation::class, $found);
        $this->assertEquals('测试查找评估', $found->getTitle());
    }

    public function testRemove(): void
    {
        $supplier = new Supplier();
        $supplier->setName('测试供应商');
        $supplier->setLegalName('测试法人');
        $supplier->setLegalAddress('测试地址');
        $supplier->setRegistrationNumber('REG' . time());
        $supplier->setTaxNumber('TAX' . time());
        $supplier->setSupplierType(SupplierType::SUPPLIER);
        self::getEntityManager()->persist($supplier);

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('E' . time());
        $evaluation->setTitle('测试删除评估');
        $evaluation->setEvaluationPeriod('2024年度');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2024-12-31'));
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $evaluation->setEvaluator('测试评估员');
        $evaluation->setSummary('测试评论');

        $this->repository->save($evaluation, true);
        $evaluationId = $evaluation->getId();
        $this->assertNotNull($evaluationId);

        $this->repository->remove($evaluation, true);

        $removed = $this->repository->find($evaluationId);
        $this->assertNull($removed);
    }
}
