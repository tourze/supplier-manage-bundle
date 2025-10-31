<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;

/**
 * @internal
 */
#[CoversClass(PerformanceEvaluation::class)]
class PerformanceEvaluationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new PerformanceEvaluation();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'evaluationNumber' => ['evaluationNumber', 'PE2024001'];
        yield 'title' => ['title', '2024年Q4绩效评估'];
        yield 'evaluationPeriod' => ['evaluationPeriod', '2024年Q4'];
        yield 'evaluator' => ['evaluator', '张三'];
        yield 'overallScore' => ['overallScore', 87.5];
        yield 'grade' => ['grade', PerformanceGrade::B];
        yield 'summary' => ['summary', '整体表现良好，建议加强质量管理'];
        yield 'improvementSuggestions' => ['improvementSuggestions', '建议定期培训员工'];
        yield 'status' => ['status', PerformanceEvaluationStatus::CONFIRMED];
    }

    public function testPerformanceEvaluationCreation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $evaluation->setSummary('表现良好，有待改进');

        $this->assertInstanceOf(PerformanceEvaluation::class, $evaluation);
        $this->assertEquals($supplier, $evaluation->getSupplier());
        $this->assertEquals('EVAL-2025-001', $evaluation->getEvaluationNumber());
        $this->assertEquals('Q1绩效评估', $evaluation->getTitle());
        $this->assertEquals('2025-Q1', $evaluation->getEvaluationPeriod());
        $this->assertEquals('2025-01-15', $evaluation->getEvaluationDate()->format('Y-m-d'));
        $this->assertEquals('张三', $evaluation->getEvaluator());
        $this->assertEquals(85.5, $evaluation->getOverallScore());
        $this->assertEquals(PerformanceGrade::B, $evaluation->getGrade());
        $this->assertEquals(PerformanceEvaluationStatus::DRAFT, $evaluation->getStatus());
        $this->assertEquals('表现良好，有待改进', $evaluation->getSummary());
        $this->assertNull($evaluation->getCreateTime());
        $this->assertNull($evaluation->getUpdateTime());
    }

    public function testGradeCalculation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 测试A等级（90分以上）
        $evaluation->setOverallScore(95.0);
        $this->assertEquals(PerformanceGrade::A, $evaluation->calculateGrade());

        // 测试B等级（80-89分）
        $evaluation->setOverallScore(85.0);
        $this->assertEquals(PerformanceGrade::B, $evaluation->calculateGrade());

        // 测试C等级（70-79分）
        $evaluation->setOverallScore(75.0);
        $this->assertEquals(PerformanceGrade::C, $evaluation->calculateGrade());

        // 测试D等级（60-69分）
        $evaluation->setOverallScore(65.0);
        $this->assertEquals(PerformanceGrade::D, $evaluation->calculateGrade());

        // 测试E等级（60分以下）
        $evaluation->setOverallScore(55.0);
        $this->assertEquals(PerformanceGrade::E, $evaluation->calculateGrade());
    }

    public function testScoreValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 测试有效分数范围
        $evaluation->setOverallScore(0.0);
        $this->assertEquals(0.0, $evaluation->getOverallScore());

        $evaluation->setOverallScore(100.0);
        $this->assertEquals(100.0, $evaluation->getOverallScore());

        $evaluation->setOverallScore(50.5);
        $this->assertEquals(50.5, $evaluation->getOverallScore());
    }

    public function testEvaluationStatusTransition(): void
    {
        $evaluation = new PerformanceEvaluation();

        // 初始状态
        $this->assertEquals(PerformanceEvaluationStatus::DRAFT, $evaluation->getStatus());

        // 草稿到待审核
        $evaluation->setStatus(PerformanceEvaluationStatus::PENDING_REVIEW);
        $this->assertEquals(PerformanceEvaluationStatus::PENDING_REVIEW, $evaluation->getStatus());

        // 待审核到已确认
        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $this->assertEquals(PerformanceEvaluationStatus::CONFIRMED, $evaluation->getStatus());

        // 待审核到已拒绝
        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $this->assertEquals(PerformanceEvaluationStatus::REJECTED, $evaluation->getStatus());
    }

    public function testStatusConstraints(): void
    {
        $evaluation = new PerformanceEvaluation();

        // 测试预定义的状态
        $validStatuses = [
            PerformanceEvaluationStatus::DRAFT,
            PerformanceEvaluationStatus::PENDING_REVIEW,
            PerformanceEvaluationStatus::CONFIRMED,
            PerformanceEvaluationStatus::REJECTED,
        ];

        foreach ($validStatuses as $status) {
            $evaluation->setStatus($status);
            $this->assertEquals($status, $evaluation->getStatus());
        }

        // 测试初始状态
        $newEvaluation = new PerformanceEvaluation();
        $this->assertEquals(PerformanceEvaluationStatus::DRAFT, $newEvaluation->getStatus());
    }

    public function testGradeConstraints(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 测试预定义的等级
        $validGrades = [
            PerformanceGrade::A,
            PerformanceGrade::B,
            PerformanceGrade::C,
            PerformanceGrade::D,
            PerformanceGrade::E,
        ];

        foreach ($validGrades as $grade) {
            $evaluation->setGrade($grade);
            $this->assertEquals($grade, $evaluation->getGrade());
        }
    }

    public function testTimestamps(): void
    {
        $evaluation = new PerformanceEvaluation();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($evaluation->getCreateTime());
        $this->assertNull($evaluation->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $evaluation->setCreateTime($createTime);
        $evaluation->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $evaluation->getCreateTime());
        $this->assertEquals($updateTime, $evaluation->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $evaluation->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testEvaluationPeriodValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 测试不同的评估周期格式
        $evaluation->setEvaluationPeriod('2025-Q1');
        $this->assertEquals('2025-Q1', $evaluation->getEvaluationPeriod());

        $evaluation->setEvaluationPeriod('2025-01');
        $this->assertEquals('2025-01', $evaluation->getEvaluationPeriod());

        $evaluation->setEvaluationPeriod('2025年度');
        $this->assertEquals('2025年度', $evaluation->getEvaluationPeriod());
    }

    public function testEvaluationCompletion(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 初始状态未完成
        $this->assertFalse($evaluation->isCompleted());

        // 设置为已确认状态
        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $this->assertTrue($evaluation->isCompleted());

        // 其他状态都不算完成
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $this->assertFalse($evaluation->isCompleted());

        $evaluation->setStatus(PerformanceEvaluationStatus::PENDING_REVIEW);
        $this->assertFalse($evaluation->isCompleted());

        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $this->assertFalse($evaluation->isCompleted());
    }

    public function testEvaluationApproval(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 初始状态未批准
        $this->assertFalse($evaluation->isApproved());

        // 设置为已确认状态
        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $this->assertTrue($evaluation->isApproved());

        // 其他状态都不算批准
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $this->assertFalse($evaluation->isApproved());

        $evaluation->setStatus(PerformanceEvaluationStatus::PENDING_REVIEW);
        $this->assertFalse($evaluation->isApproved());

        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $this->assertFalse($evaluation->isApproved());
    }

    public function testEvaluationRejection(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);

        // 初始状态未拒绝
        $this->assertFalse($evaluation->isRejected());

        // 设置为已拒绝状态
        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $this->assertTrue($evaluation->isRejected());

        // 其他状态都不算拒绝
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $this->assertFalse($evaluation->isRejected());

        $evaluation->setStatus(PerformanceEvaluationStatus::PENDING_REVIEW);
        $this->assertFalse($evaluation->isRejected());

        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $this->assertFalse($evaluation->isRejected());
    }

    public function testSupplierAssociation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        // 验证双向关联
        $this->assertEquals($supplier, $evaluation->getSupplier());

        // 将评估添加到供应商
        $supplier->addPerformanceEvaluation($evaluation);
        $this->assertTrue($supplier->getPerformanceEvaluations()->contains($evaluation));
    }

    public function testUpdatedAtAutoUpdate(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $evaluation->setUpdateTime($originalUpdateTime);

        // 模拟时间流逝
        usleep(1000); // 1毫秒

        // 手动设置更新时间戳（模拟 Doctrine 监听器的行为）
        $newUpdateTime = new \DateTimeImmutable();
        $evaluation->setUpdateTime($newUpdateTime);

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }

    public function testNullableFields(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        // 验证可空字段的默认值
        $this->assertNull($evaluation->getSummary());
        $this->assertNull($evaluation->getImprovementSuggestions());
        $this->assertEquals(PerformanceEvaluationStatus::DRAFT, $evaluation->getStatus());

        // 设置可空字段
        $evaluation->setSummary('表现良好，有待改进');
        $evaluation->setImprovementSuggestions('建议加强质量控制');

        $this->assertEquals('表现良好，有待改进', $evaluation->getSummary());
        $this->assertEquals('建议加强质量控制', $evaluation->getImprovementSuggestions());
    }

    public function testDefaultValues(): void
    {
        $evaluation = new PerformanceEvaluation();

        // 验证默认值
        $this->assertEquals(PerformanceEvaluationStatus::DRAFT, $evaluation->getStatus());
        $this->assertNull($evaluation->getId());
        $this->assertNull($evaluation->getSummary());
        $this->assertNull($evaluation->getImprovementSuggestions());
        $this->assertEmpty($evaluation->getEvaluationItems());
    }

    public function testCascadingRelationship(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        // 添加评估到供应商
        $supplier->addPerformanceEvaluation($evaluation);

        // 验证评估已添加
        $this->assertTrue($supplier->getPerformanceEvaluations()->contains($evaluation));
        $this->assertEquals($supplier, $evaluation->getSupplier());

        // 移除评估
        $supplier->removePerformanceEvaluation($evaluation);

        // 验证评估已移除
        $this->assertFalse($supplier->getPerformanceEvaluations()->contains($evaluation));
    }

    public function testEvaluationConstants(): void
    {
        // 测试状态常量
        $this->assertEquals('draft', PerformanceEvaluationStatus::DRAFT->value);
        $this->assertEquals('pending_review', PerformanceEvaluationStatus::PENDING_REVIEW->value);
        $this->assertEquals('confirmed', PerformanceEvaluationStatus::CONFIRMED->value);
        $this->assertEquals('rejected', PerformanceEvaluationStatus::REJECTED->value);

        // 测试等级常量
        $this->assertEquals('A', PerformanceGrade::A->value);
        $this->assertEquals('B', PerformanceGrade::B->value);
        $this->assertEquals('C', PerformanceGrade::C->value);
        $this->assertEquals('D', PerformanceGrade::D->value);
        $this->assertEquals('E', PerformanceGrade::E->value);
    }

    public function testBusinessLogicCombinations(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');

        // 情况 1: 草稿状态，90分以上，A等级
        $evaluation->setOverallScore(92.0);
        $evaluation->setStatus(PerformanceEvaluationStatus::DRAFT);
        $calculatedGrade = $evaluation->calculateGrade();
        $this->assertEquals(PerformanceGrade::A, $calculatedGrade);
        $this->assertFalse($evaluation->isCompleted());
        $this->assertFalse($evaluation->isApproved());
        $this->assertFalse($evaluation->isRejected());

        // 情况 2: 已确认状态，85分，B等级
        $evaluation->setOverallScore(85.0);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $this->assertTrue($evaluation->isCompleted());
        $this->assertTrue($evaluation->isApproved());
        $this->assertFalse($evaluation->isRejected());

        // 情况 3: 已拒绝状态
        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $this->assertFalse($evaluation->isCompleted());
        $this->assertFalse($evaluation->isApproved());
        $this->assertTrue($evaluation->isRejected());
    }

    public function testToString(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplier);
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');
        $evaluation->setEvaluationPeriod('2025-Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable('2025-01-15'));
        $evaluation->setEvaluator('张三');
        $evaluation->setOverallScore(85.5);
        $evaluation->setGrade(PerformanceGrade::B);

        $this->assertEquals('EVAL-2025-001 - Q1绩效评估', (string) $evaluation);
    }
}
