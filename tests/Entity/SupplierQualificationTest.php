<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;

/**
 * @internal
 */
#[CoversClass(SupplierQualification::class)]
class SupplierQualificationTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new SupplierQualification();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', 'ISO9001质量管理体系认证'];
        yield 'type' => ['type', 'quality'];
        yield 'certificateNumber' => ['certificateNumber', 'ISO9001-2024-001'];
        yield 'issuingAuthority' => ['issuingAuthority', '中国质量认证中心'];
        yield 'filePath' => ['filePath', '/uploads/certificates/iso9001.pdf'];
        yield 'isActive' => ['isActive', true];
        yield 'status' => ['status', SupplierQualificationStatus::APPROVED];
        yield 'remarks' => ['remarks', '年度审核通过'];
    }

    public function testCanBeCreated(): void
    {
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
        $qualification->setCertificateNumber('ISO-2024-001');
        $qualification->setIssuingAuthority('ISO Organization');
        $qualification->setIssuedDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setExpiryDate(new \DateTimeImmutable('2027-01-01'));
        $qualification->setFilePath('/uploads/certificates/iso9001.pdf');

        $this->assertInstanceOf(SupplierQualification::class, $qualification);
        $this->assertSame($supplier, $qualification->getSupplier());
        $this->assertSame('ISO 9001', $qualification->getName());
        $this->assertSame('quality', $qualification->getType());
        $this->assertSame('ISO-2024-001', $qualification->getCertificateNumber());
        $this->assertSame('ISO Organization', $qualification->getIssuingAuthority());
        $this->assertEquals(new \DateTimeImmutable('2024-01-01'), $qualification->getIssuedDate());
        $this->assertEquals(new \DateTimeImmutable('2027-01-01'), $qualification->getExpiryDate());
        $this->assertSame('/uploads/certificates/iso9001.pdf', $qualification->getFilePath());
        $this->assertTrue($qualification->getIsActive());
    }

    public function testQualificationExpiry(): void
    {
        $qualification = new SupplierQualification();

        // 未过期的资质
        $qualification->setExpiryDate(new \DateTimeImmutable('+1 year'));
        $this->assertFalse($qualification->isExpired());

        // 已过期的资质
        $qualification->setExpiryDate(new \DateTimeImmutable('-1 day'));
        $this->assertTrue($qualification->isExpired());

        // 今天过期
        $qualification->setExpiryDate(new \DateTimeImmutable('today'));
        $this->assertTrue($qualification->isExpired());
    }

    public function testQualificationValidityPeriod(): void
    {
        $qualification = new SupplierQualification();
        $qualification->setIssuedDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setExpiryDate(new \DateTimeImmutable('2027-01-01'));

        $validityDays = $qualification->getValidityDays();
        $this->assertEquals(1096, $validityDays); // 3年 + 1天闰年
    }

    public function testCanDeactivateQualification(): void
    {
        $qualification = new SupplierQualification();
        $this->assertTrue($qualification->getIsActive());

        $qualification->setIsActive(false);
        $this->assertFalse($qualification->getIsActive());
    }

    public function testSupplierRelation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplier);

        // 验证双向关系
        $supplier->addQualification($qualification);
        $this->assertTrue($supplier->getQualifications()->contains($qualification));
    }

    public function testTimestamps(): void
    {
        $qualification = new SupplierQualification();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($qualification->getCreateTime());
        $this->assertNull($qualification->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $qualification->setCreateTime($createTime);
        $qualification->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $qualification->getCreateTime());
        $this->assertEquals($updateTime, $qualification->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $qualification->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testStringRepresentation(): void
    {
        $qualification = new SupplierQualification();
        $qualification->setName('ISO 9001');
        $qualification->setCertificateNumber('ISO-2024-001');

        $expected = 'ISO 9001 (ISO-2024-001)';
        $this->assertSame($expected, (string) $qualification);
    }

    public function testQualificationTypes(): void
    {
        $qualification = new SupplierQualification();

        // 测试各种资质类型
        $types = ['quality', 'safety', 'environment', 'industry', 'other'];

        foreach ($types as $type) {
            $qualification->setType($type);
            $this->assertSame($type, $qualification->getType());
        }
    }

    public function testRequiredFields(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $qualification = new SupplierQualification();

        // 必须字段测试
        $qualification->setSupplier($supplier);
        $qualification->setName('Test Qualification');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('CERT-001');
        $qualification->setIssuingAuthority('Test Authority');
        $qualification->setIssuedDate(new \DateTimeImmutable('2024-01-01'));
        $qualification->setExpiryDate(new \DateTimeImmutable('2027-01-01'));

        $this->assertNotNull($qualification->getSupplier());
        $this->assertNotNull($qualification->getName());
        $this->assertNotNull($qualification->getType());
        $this->assertNotNull($qualification->getCertificateNumber());
        $this->assertNotNull($qualification->getIssuingAuthority());
        $this->assertNotNull($qualification->getIssuedDate());
        $this->assertNotNull($qualification->getExpiryDate());
    }
}
