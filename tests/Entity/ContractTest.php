<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\ContractStatus;

/**
 * @internal
 */
#[CoversClass(Contract::class)]
class ContractTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Contract();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'title' => ['title', '测试合同'];
        yield 'contractType' => ['contractType', 'supply'];
        yield 'amount' => ['amount', 100000.00];
        yield 'currency' => ['currency', 'CNY'];
        yield 'description' => ['description', '这是一个测试合同描述'];
        yield 'terms' => ['terms', '这是合同的详细条款内容'];
        yield 'status' => ['status', ContractStatus::DRAFT];
    }

    public function testCanBeCreated(): void
    {
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
        $contract->setCurrency('CNY');
        $contract->setStatus(ContractStatus::DRAFT);
        $contract->setDescription('这是一个测试合同');

        $this->assertInstanceOf(Contract::class, $contract);
        $this->assertSame($supplier, $contract->getSupplier());
        $this->assertSame('CON-2024-001', $contract->getContractNumber());
        $this->assertSame('测试合同', $contract->getTitle());
        $this->assertSame('supply', $contract->getContractType());
        $this->assertEquals(new \DateTimeImmutable('2024-01-01'), $contract->getStartDate());
        $this->assertEquals(new \DateTimeImmutable('2024-12-31'), $contract->getEndDate());
        $this->assertEquals(100000.00, $contract->getAmount());
        $this->assertSame('CNY', $contract->getCurrency());
        $this->assertSame(ContractStatus::DRAFT, $contract->getStatus());
        $this->assertSame('这是一个测试合同', $contract->getDescription());
    }

    public function testContractDateValidation(): void
    {
        $contract = new Contract();

        // 正常情况：开始日期早于结束日期
        $contract->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2024-12-31'));

        $this->assertTrue($contract->isDateRangeValid());

        // 异常情况：结束日期早于开始日期
        $contract->setStartDate(new \DateTimeImmutable('2024-12-31'));
        $contract->setEndDate(new \DateTimeImmutable('2024-01-01'));

        $this->assertFalse($contract->isDateRangeValid());

        // 边界情况：开始和结束日期相同
        $contract->setStartDate(new \DateTimeImmutable('2024-06-15'));
        $contract->setEndDate(new \DateTimeImmutable('2024-06-15'));

        $this->assertTrue($contract->isDateRangeValid());
    }

    public function testAmountChangeHistory(): void
    {
        $contract = new Contract();
        $contract->setAmount(100000.00);

        // 初始金额变更历史应该为空
        $this->assertEmpty($contract->getAmountChangeHistory());

        // 记录金额变更
        $contract->recordAmountChange(120000.00, '合同修正，增加工程量');

        $history = $contract->getAmountChangeHistory();
        $this->assertCount(1, $history);
        $this->assertEquals(100000.00, $history[0]['old_amount']);
        $this->assertEquals(120000.00, $history[0]['new_amount']);
        $this->assertEquals('合同修正，增加工程量', $history[0]['reason']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $history[0]['changed_at']);

        // 当前金额应该更新
        $this->assertEquals(120000.00, $contract->getAmount());
    }

    public function testContractStatusManagement(): void
    {
        $contract = new Contract();

        // 默认状态应该是草稿
        $this->assertSame(ContractStatus::DRAFT, $contract->getStatus());

        // 测试状态转换
        $statuses = [
            'draft' => ContractStatus::DRAFT,
            'pending_approval' => ContractStatus::PENDING_REVIEW,
            'approved' => ContractStatus::APPROVED,
            'active' => ContractStatus::ACTIVE,
            'completed' => ContractStatus::COMPLETED,
            'terminated' => ContractStatus::TERMINATED,
        ];

        foreach ($statuses as $statusString => $statusEnum) {
            $contract->setStatus($statusEnum);
            $this->assertSame($statusEnum, $contract->getStatus());
        }
    }

    public function testContractDuration(): void
    {
        $contract = new Contract();
        $contract->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract->setEndDate(new \DateTimeImmutable('2024-12-31'));

        $duration = $contract->getDurationInDays();
        $this->assertEquals(365, $duration); // 2024年是闰年，但1月1日到12月31日是365天
    }

    public function testContractExpiration(): void
    {
        $contract = new Contract();

        // 未过期的合同
        $contract->setEndDate(new \DateTimeImmutable('+1 year'));
        $this->assertFalse($contract->isExpired());

        // 已过期的合同
        $contract->setEndDate(new \DateTimeImmutable('-1 day'));
        $this->assertTrue($contract->isExpired());

        // 今天过期的合同
        $contract->setEndDate(new \DateTimeImmutable('today'));
        $this->assertTrue($contract->isExpired());
    }

    public function testContractActivePeriod(): void
    {
        $contract = new Contract();
        $now = new \DateTimeImmutable();

        // 当前时间在合同期内
        $contract->setStartDate($now->modify('-1 month'));
        $contract->setEndDate($now->modify('+1 month'));
        $this->assertTrue($contract->isCurrentlyActive());

        // 合同尚未开始
        $contract->setStartDate($now->modify('+1 day'));
        $contract->setEndDate($now->modify('+1 month'));
        $this->assertFalse($contract->isCurrentlyActive());

        // 合同已结束
        $contract->setStartDate($now->modify('-2 months'));
        $contract->setEndDate($now->modify('-1 day'));
        $this->assertFalse($contract->isCurrentlyActive());
    }

    public function testSupplierRelation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $contract = new Contract();
        $contract->setSupplier($supplier);

        // 验证双向关系
        $supplier->addContract($contract);
        $this->assertTrue($supplier->getContracts()->contains($contract));
    }

    public function testTimestamps(): void
    {
        $contract = new Contract();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($contract->getCreateTime());
        $this->assertNull($contract->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $contract->setCreateTime($createTime);
        $contract->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $contract->getCreateTime());
        $this->assertEquals($updateTime, $contract->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $contract->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testStringRepresentation(): void
    {
        $contract = new Contract();
        $contract->setContractNumber('CON-2024-001');
        $contract->setTitle('测试合同');

        $expected = 'CON-2024-001 - 测试合同';
        $this->assertSame($expected, (string) $contract);
    }

    public function testContractTypes(): void
    {
        $contract = new Contract();

        // 测试各种合同类型
        $types = ['supply', 'service', 'purchase', 'lease', 'other'];

        foreach ($types as $type) {
            $contract->setContractType($type);
            $this->assertSame($type, $contract->getContractType());
        }
    }

    public function testNegativeAmount(): void
    {
        $contract = new Contract();

        // 合同金额应该不能为负数
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('合同金额不能为负数');

        $contract->setAmount(-1000.00);
    }

    public function testInvalidDateRange(): void
    {
        $contract = new Contract();

        // 设置无效的日期范围应该抛出异常
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('合同结束日期不能早于开始日期');

        $contract->setStartDate(new \DateTimeImmutable('2024-12-31'));
        $contract->setEndDate(new \DateTimeImmutable('2024-01-01'));

        $contract->validateDateRange();
    }
}
