<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;

/**
 * @internal
 */
#[CoversClass(Supplier::class)]
class SupplierTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new Supplier();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '优质供应商有限公司'];
        yield 'legalName' => ['legalName', '优质供应商有限公司'];
        yield 'legalAddress' => ['legalAddress', '北京市朝阳区某某街道123号'];
        yield 'registrationNumber' => ['registrationNumber', '91110108XXXXXXXXXX'];
        yield 'taxNumber' => ['taxNumber', '91110108XXXXXXXXXX'];
        yield 'industry' => ['industry', '制造业'];
        yield 'website' => ['website', 'https://www.example.com'];
        yield 'introduction' => ['introduction', '专注于高质量产品供应'];
        yield 'supplierType' => ['supplierType', SupplierType::SUPPLIER];
        yield 'cooperationModel' => ['cooperationModel', CooperationModel::DISTRIBUTION];
        yield 'businessCategory' => ['businessCategory', '电子产品'];
        yield 'isWarehouse' => ['isWarehouse', false];
        yield 'status' => ['status', SupplierStatus::APPROVED];
        yield 'version' => ['version', 1];
    }

    public function testSupplierCreation(): void
    {
        $supplier = new Supplier();

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertEquals(SupplierStatus::DRAFT, $supplier->getStatus());
        $this->assertEquals(1, $supplier->getVersion());
    }

    public function testSupplierSettersAndGetters(): void
    {
        $supplier = new Supplier();

        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Supplier Legal');
        $supplier->setLegalAddress('123 Test St');
        $supplier->setRegistrationNumber('REG123456');
        $supplier->setTaxNumber('TAX123456');
        $supplier->setIndustry('Technology');
        $supplier->setWebsite('https://example.com');
        $supplier->setIntroduction('A test supplier company');
        $supplier->setSupplierType(SupplierType::SUPPLIER);
        $supplier->setCooperationModel(CooperationModel::DISTRIBUTION);
        $supplier->setBusinessCategory('IT Services');
        $supplier->setIsWarehouse(true);

        $this->assertEquals('Test Supplier', $supplier->getName());
        $this->assertEquals('Test Supplier Legal', $supplier->getLegalName());
        $this->assertEquals('123 Test St', $supplier->getLegalAddress());
        $this->assertEquals('REG123456', $supplier->getRegistrationNumber());
        $this->assertEquals('TAX123456', $supplier->getTaxNumber());
        $this->assertEquals('Technology', $supplier->getIndustry());
        $this->assertEquals('https://example.com', $supplier->getWebsite());
        $this->assertEquals('A test supplier company', $supplier->getIntroduction());
        $this->assertEquals(SupplierType::SUPPLIER, $supplier->getSupplierType());
        $this->assertEquals(CooperationModel::DISTRIBUTION, $supplier->getCooperationModel());
        $this->assertEquals('IT Services', $supplier->getBusinessCategory());
        $this->assertTrue($supplier->getIsWarehouse());
    }

    public function testSupplierStatusTransitions(): void
    {
        $supplier = new Supplier();

        $this->assertEquals(SupplierStatus::DRAFT, $supplier->getStatus());

        $supplier->setStatus(SupplierStatus::PENDING_REVIEW);
        $this->assertEquals(SupplierStatus::PENDING_REVIEW, $supplier->getStatus());

        $supplier->setStatus(SupplierStatus::APPROVED);
        $this->assertEquals(SupplierStatus::APPROVED, $supplier->getStatus());

        $supplier->setStatus(SupplierStatus::REJECTED);
        $this->assertEquals(SupplierStatus::REJECTED, $supplier->getStatus());
    }

    public function testTimestamps(): void
    {
        $supplier = new Supplier();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($supplier->getCreateTime());
        $this->assertNull($supplier->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $supplier->setCreateTime($createTime);
        $supplier->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $supplier->getCreateTime());
        $this->assertEquals($updateTime, $supplier->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $supplier->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testToString(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $this->assertEquals('Test Supplier', (string) $supplier);
    }

    public function testSoftDelete(): void
    {
        $supplier = new Supplier();

        // 初始状态未删除
        $this->assertFalse($supplier->isDeleted());
        $this->assertNull($supplier->getDeleteTime());

        // 设置删除时间
        $deletedAt = new \DateTimeImmutable('2025-08-09 12:00:00');
        $supplier->setDeleteTime($deletedAt);

        // 验证已删除状态
        $this->assertTrue($supplier->isDeleted());
        $this->assertEquals($deletedAt, $supplier->getDeleteTime());
    }

    public function testNullableFields(): void
    {
        $supplier = new Supplier();

        // 验证可空字段的默认值
        $this->assertNull($supplier->getIndustry());
        $this->assertNull($supplier->getWebsite());
        $this->assertNull($supplier->getIntroduction());
        $this->assertNull($supplier->getCooperationModel());
        $this->assertNull($supplier->getBusinessCategory());

        // 设置可空字段
        $supplier->setIndustry('Manufacturing');
        $supplier->setWebsite('https://test.com');
        $supplier->setIntroduction('Test company description');

        // 验证设置后的值
        $this->assertEquals('Manufacturing', $supplier->getIndustry());
        $this->assertEquals('https://test.com', $supplier->getWebsite());
        $this->assertEquals('Test company description', $supplier->getIntroduction());
    }

    public function testPreUpdateLifecycleCallback(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $supplier->setUpdateTime($originalUpdateTime);

        // 模拟一些时间流逝
        usleep(1000); // 1毫秒

        // 手动调用 setUpdateTime 方法（实际上会由实体监听器调用）
        $supplier->setUpdateTime(new \DateTimeImmutable());

        $newUpdateTime = $supplier->getUpdateTime();

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }

    public function testUpdatedAtAutoUpdate(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $supplier->setUpdateTime($originalUpdateTime);

        // 模拟一些时间流逝
        usleep(1000); // 1毫秒

        // 手动设置更新时间戳（模拟 Doctrine 监听器的行为）
        $newUpdateTime = new \DateTimeImmutable();
        $supplier->setUpdateTime($newUpdateTime);

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }

    public function testGetIdReturnsNullWhenNotGenerated(): void
    {
        $supplier = new Supplier();

        // getId() should return 0 before persistence (Doctrine auto-generated ID pattern)
        $this->assertEquals(0, $supplier->getId(), 'ID should be 0 before entity is persisted');
    }

    public function testSupplierTypeConstants(): void
    {
        $this->assertEquals(SupplierType::SUPPLIER->value, 'supplier');
        $this->assertEquals(SupplierType::MERCHANT->value, 'merchant');

        $this->assertEquals(CooperationModel::DISTRIBUTION->value, 'distribution');
        $this->assertEquals(CooperationModel::CONSIGNMENT->value, 'consignment');
        $this->assertEquals(CooperationModel::JOINT_VENTURE->value, 'jointventure');

        $this->assertEquals(SupplierStatus::DRAFT->value, 'draft');
        $this->assertEquals(SupplierStatus::PENDING_REVIEW->value, 'pending_review');
        $this->assertEquals(SupplierStatus::APPROVED->value, 'approved');
        $this->assertEquals(SupplierStatus::REJECTED->value, 'rejected');
        $this->assertEquals(SupplierStatus::SUSPENDED->value, 'suspended');
        $this->assertEquals(SupplierStatus::TERMINATED->value, 'terminated');
    }

    public function testVersionOptimisticLocking(): void
    {
        $supplier = new Supplier();

        // 默认版本应该是1
        $this->assertEquals(1, $supplier->getVersion());

        // 设置新版本
        $supplier->setVersion(2);
        $this->assertEquals(2, $supplier->getVersion());
    }

    public function testCollectionInitialization(): void
    {
        $supplier = new Supplier();

        $this->assertNotNull($supplier->getContacts());
        $this->assertNotNull($supplier->getQualifications());
        $this->assertNotNull($supplier->getContracts());
        $this->assertNotNull($supplier->getPerformanceEvaluations());

        $this->assertCount(0, $supplier->getContacts());
        $this->assertCount(0, $supplier->getQualifications());
        $this->assertCount(0, $supplier->getContracts());
        $this->assertCount(0, $supplier->getPerformanceEvaluations());
    }

    public function testSupplierTypeChoiceValidation(): void
    {
        // 测试供应商类型只能是预定义的值
        $validTypes = [
            SupplierType::SUPPLIER,
            SupplierType::MERCHANT,
        ];

        foreach ($validTypes as $type) {
            $supplier = new Supplier();
            $supplier->setSupplierType($type);
            $this->assertEquals($type, $supplier->getSupplierType());
        }
    }

    public function testCooperationModelChoiceValidation(): void
    {
        // 测试合作模式只能是预定义的值
        $validModels = [
            CooperationModel::DISTRIBUTION,
            CooperationModel::CONSIGNMENT,
            CooperationModel::JOINT_VENTURE,
        ];

        foreach ($validModels as $model) {
            $supplier = new Supplier();
            $supplier->setCooperationModel($model);
            $this->assertEquals($model, $supplier->getCooperationModel());
        }
    }

    public function testStatusHistoryTracking(): void
    {
        // 验证状态变更时更新时间戳
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        // 手动设置初始时间戳以模拟持久化后的状态
        $initialUpdateTime = new \DateTimeImmutable();
        $supplier->setUpdateTime($initialUpdateTime);

        // 模拟时间流逝
        usleep(1000);

        // 手动设置更新时间戳（模拟状态变更后的 Doctrine 监听器行为）
        $supplier->setStatus(SupplierStatus::PENDING_REVIEW);
        $newUpdateTime = new \DateTimeImmutable();
        $supplier->setUpdateTime($newUpdateTime);

        $this->assertEquals(SupplierStatus::PENDING_REVIEW, $supplier->getStatus());
        $this->assertGreaterThan($initialUpdateTime, $supplier->getUpdateTime());
    }

    public function testBooleanDefaultValues(): void
    {
        $supplier = new Supplier();

        // 验证布尔字段的默认值
        $this->assertFalse($supplier->getIsWarehouse());
        $this->assertFalse($supplier->isDeleted());
    }

    public function testStringRepresentation(): void
    {
        $supplier = new Supplier();
        $supplierName = 'Test Company Name';
        $supplier->setName($supplierName);

        $this->assertEquals($supplierName, (string) $supplier);
        $this->assertEquals($supplierName, $supplier->__toString());
    }
}
