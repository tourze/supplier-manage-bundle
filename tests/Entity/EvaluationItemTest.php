<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;

/**
 * @internal
 */
#[CoversClass(EvaluationItem::class)]
class EvaluationItemTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new EvaluationItem();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'itemName' => ['itemName', '产品质量'];
        yield 'itemType' => ['itemType', EvaluationItemType::QUANTITATIVE];
        yield 'weight' => ['weight', 25.5];
        yield 'score' => ['score', 85.0];
        yield 'maxScore' => ['maxScore', 100.0];
        yield 'unit' => ['unit', '分'];
        yield 'description' => ['description', '产品合格率评估指标'];
    }

    public function testEvaluationItemCreation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);
        $item->setUnit('%');
        $item->setDescription('按时交付的订单比例');

        $this->assertInstanceOf(EvaluationItem::class, $item);
        $this->assertEquals($evaluation, $item->getEvaluation());
        $this->assertEquals('交付准时率', $item->getItemName());
        $this->assertEquals(EvaluationItemType::QUANTITATIVE, $item->getItemType());
        $this->assertEquals(30.0, $item->getWeight());
        $this->assertEquals(85.0, $item->getScore());
        $this->assertEquals(100.0, $item->getMaxScore());
        $this->assertEquals('%', $item->getUnit());
        $this->assertEquals('按时交付的订单比例', $item->getDescription());
        $this->assertNull($item->getCreateTime());
        $this->assertNull($item->getUpdateTime());
    }

    public function testItemTypeValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试定量类型
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $this->assertEquals(EvaluationItemType::QUANTITATIVE, $item->getItemType());

        // 测试定性类型
        $item->setItemType(EvaluationItemType::QUALITATIVE);
        $this->assertEquals(EvaluationItemType::QUALITATIVE, $item->getItemType());
    }

    public function testWeightValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试有效权重范围
        $item->setWeight(0.0);
        $this->assertEquals(0.0, $item->getWeight());

        $item->setWeight(100.0);
        $this->assertEquals(100.0, $item->getWeight());

        $item->setWeight(50.5);
        $this->assertEquals(50.5, $item->getWeight());
    }

    public function testScoreValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setMaxScore(100.0);

        // 测试得分不超过最大值
        $item->setScore(0.0);
        $this->assertEquals(0.0, $item->getScore());

        $item->setScore(100.0);
        $this->assertEquals(100.0, $item->getScore());

        $item->setScore(85.5);
        $this->assertEquals(85.5, $item->getScore());
    }

    public function testWeightedScoreCalculation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setWeight(30.0);
        $item->setScore(80.0);
        $item->setMaxScore(100.0);

        // 加权得分 = (得分/最大分) * 权重
        $expectedWeightedScore = (80.0 / 100.0) * 30.0;
        $this->assertEquals($expectedWeightedScore, $item->getWeightedScore());
    }

    public function testScorePercentage(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        // 得分百分比 = (得分/最大分) * 100
        $expectedPercentage = (85.0 / 100.0) * 100.0;
        $this->assertEquals($expectedPercentage, $item->getScorePercentage());
    }

    public function testItemTypeConstraints(): void
    {
        $item = new EvaluationItem();

        // 测试预定义的指标类型
        $validTypes = [
            EvaluationItemType::QUANTITATIVE,
            EvaluationItemType::QUALITATIVE,
        ];

        foreach ($validTypes as $type) {
            $item->setItemType($type);
            $this->assertEquals($type, $item->getItemType());
        }

        // 测试初始类型
        $newItem = new EvaluationItem();
        $this->assertEquals(EvaluationItemType::QUANTITATIVE, $newItem->getItemType());
    }

    public function testTimestamps(): void
    {
        $item = new EvaluationItem();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($item->getCreateTime());
        $this->assertNull($item->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $item->setCreateTime($createTime);
        $item->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $item->getCreateTime());
        $this->assertEquals($updateTime, $item->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $item->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testMaxScoreValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试最大分值
        $item->setMaxScore(50.0);
        $this->assertEquals(50.0, $item->getMaxScore());

        $item->setMaxScore(100.0);
        $this->assertEquals(100.0, $item->getMaxScore());

        $item->setMaxScore(10.0);
        $this->assertEquals(10.0, $item->getMaxScore());
    }

    public function testUnitValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试不同的单位
        $item->setUnit('%');
        $this->assertEquals('%', $item->getUnit());

        $item->setUnit('分');
        $this->assertEquals('分', $item->getUnit());

        $item->setUnit('个');
        $this->assertEquals('个', $item->getUnit());

        $item->setUnit('');
        $this->assertEquals('', $item->getUnit());
    }

    public function testDescriptionValidation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试描述
        $item->setDescription('这是指标描述');
        $this->assertEquals('这是指标描述', $item->getDescription());

        // 测试空描述
        $item->setDescription(null);
        $this->assertNull($item->getDescription());

        // 测试长描述
        $longDescription = str_repeat('这是一个很长的描述。', 50);
        $item->setDescription($longDescription);
        $this->assertEquals($longDescription, $item->getDescription());
    }

    public function testIsQuantitative(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 定量指标
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $this->assertTrue($item->isQuantitative());

        // 定性指标
        $item->setItemType(EvaluationItemType::QUALITATIVE);
        $this->assertFalse($item->isQuantitative());
    }

    public function testIsQualitative(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 定量指标
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $this->assertFalse($item->isQualitative());

        // 定性指标
        $item->setItemType(EvaluationItemType::QUALITATIVE);
        $this->assertTrue($item->isQualitative());
    }

    public function testEvaluationAssociation(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        // 验证双向关联
        $this->assertEquals($evaluation, $item->getEvaluation());

        // 将评估项添加到评估
        $evaluation->addEvaluationItem($item);
        $this->assertTrue($evaluation->getEvaluationItems()->contains($item));
    }

    public function testUpdatedAtAutoUpdate(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $item->setUpdateTime($originalUpdateTime);

        // 模拟时间流逝
        usleep(1000); // 1毫秒

        // 手动设置更新时间戳（模拟 Doctrine 监听器的行为）
        $newUpdateTime = new \DateTimeImmutable();
        $item->setUpdateTime($newUpdateTime);

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }

    public function testNullableFields(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        // 验证可空字段的默认值
        $this->assertNull($item->getUnit());
        $this->assertNull($item->getDescription());
        $this->assertEquals(EvaluationItemType::QUANTITATIVE, $item->getItemType());

        // 设置可空字段
        $item->setUnit('%');
        $item->setDescription('按时交付的订单比例');

        $this->assertEquals('%', $item->getUnit());
        $this->assertEquals('按时交付的订单比例', $item->getDescription());
    }

    public function testDefaultValues(): void
    {
        $item = new EvaluationItem();

        // 验证默认值
        $this->assertEquals(EvaluationItemType::QUANTITATIVE, $item->getItemType());
        $this->assertNull($item->getId());
        $this->assertNull($item->getUnit());
        $this->assertNull($item->getDescription());
    }

    public function testCascadingRelationship(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);

        // 添加评估项到评估
        $evaluation->addEvaluationItem($item);

        // 验证评估项已添加
        $this->assertTrue($evaluation->getEvaluationItems()->contains($item));
        $this->assertEquals($evaluation, $item->getEvaluation());

        // 移除评估项
        $evaluation->removeEvaluationItem($item);

        // 验证评估项已移除
        $this->assertFalse($evaluation->getEvaluationItems()->contains($item));
    }

    public function testEvaluationItemConstants(): void
    {
        // 测试指标类型常量
        $this->assertEquals('quantitative', EvaluationItemType::QUANTITATIVE->value);
        $this->assertEquals('qualitative', EvaluationItemType::QUALITATIVE->value);
    }

    public function testEdgeCaseCalculations(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);

        // 测试最大分值为0的情况
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(0.0);

        $this->assertEquals(0.0, $item->getWeightedScore());
        $this->assertEquals(0.0, $item->getScorePercentage());

        // 测试正常情况
        $item->setMaxScore(100.0);
        $this->assertEquals(25.5, $item->getWeightedScore()); // (85/100) * 30
        $this->assertEquals(85.0, $item->getScorePercentage()); // (85/100) * 100

        // 测试权重为0的情况
        $item->setWeight(0.0);
        $this->assertEquals(0.0, $item->getWeightedScore());
        $this->assertEquals(85.0, $item->getScorePercentage());

        // 测试得分为0的情况
        $item->setScore(0.0);
        $item->setWeight(30.0);
        $this->assertEquals(0.0, $item->getWeightedScore());
        $this->assertEquals(0.0, $item->getScorePercentage());
    }

    public function testBusinessLogicCombinations(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        // 定量指标
        $quantitativeItem = new EvaluationItem();
        $quantitativeItem->setEvaluation($evaluation);
        $quantitativeItem->setItemName('交付准时率');
        $quantitativeItem->setItemType(EvaluationItemType::QUANTITATIVE);
        $quantitativeItem->setWeight(40.0);
        $quantitativeItem->setScore(88.0);
        $quantitativeItem->setMaxScore(100.0);
        $quantitativeItem->setUnit('%');
        $quantitativeItem->setDescription('按时交付的订单比例');

        $this->assertTrue($quantitativeItem->isQuantitative());
        $this->assertFalse($quantitativeItem->isQualitative());
        $this->assertEquals(35.2, $quantitativeItem->getWeightedScore()); // (88/100) * 40
        $this->assertEquals(88.0, $quantitativeItem->getScorePercentage());

        // 定性指标
        $qualitativeItem = new EvaluationItem();
        $qualitativeItem->setEvaluation($evaluation);
        $qualitativeItem->setItemName('服务态度');
        $qualitativeItem->setItemType(EvaluationItemType::QUALITATIVE);
        $qualitativeItem->setWeight(20.0);
        $qualitativeItem->setScore(9.0);
        $qualitativeItem->setMaxScore(10.0);
        $qualitativeItem->setDescription('客户服务态度评价');

        $this->assertFalse($qualitativeItem->isQuantitative());
        $this->assertTrue($qualitativeItem->isQualitative());
        $this->assertEquals(18.0, $qualitativeItem->getWeightedScore()); // (9/10) * 20
        $this->assertEquals(90.0, $qualitativeItem->getScorePercentage());

        // 验证两个指标权重总和
        $totalWeight = $quantitativeItem->getWeight() + $qualitativeItem->getWeight();
        $this->assertEquals(60.0, $totalWeight);
    }

    public function testToString(): void
    {
        $evaluation = new PerformanceEvaluation();
        $evaluation->setEvaluationNumber('EVAL-2025-001');
        $evaluation->setTitle('Q1绩效评估');

        $item = new EvaluationItem();
        $item->setEvaluation($evaluation);
        $item->setItemName('交付准时率');
        $item->setItemType(EvaluationItemType::QUANTITATIVE);
        $item->setWeight(30.0);
        $item->setScore(85.0);
        $item->setMaxScore(100.0);
        $item->setUnit('%');

        $this->assertEquals('交付准时率 (30%)', (string) $item);
    }
}
