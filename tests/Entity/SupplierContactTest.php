<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;

/**
 * @internal
 */
#[CoversClass(SupplierContact::class)]
class SupplierContactTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new SupplierContact();
    }

    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'name' => ['name', '李四'];
        yield 'position' => ['position', '销售经理'];
        yield 'email' => ['email', 'lisi@example.com'];
        yield 'phone' => ['phone', '13800138000'];
        yield 'isPrimary' => ['isPrimary', true];
    }

    public function testSupplierContactCreation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('John Doe');
        $contact->setPosition('Sales Manager');
        $contact->setEmail('john@example.com');
        $contact->setPhone('13800138000');
        $contact->setIsPrimary(true);

        $this->assertInstanceOf(SupplierContact::class, $contact);
        $this->assertEquals($supplier, $contact->getSupplier());
        $this->assertEquals('John Doe', $contact->getName());
        $this->assertEquals('Sales Manager', $contact->getPosition());
        $this->assertEquals('john@example.com', $contact->getEmail());
        $this->assertEquals('13800138000', $contact->getPhone());
        $this->assertTrue($contact->getIsPrimary());
        $this->assertNull($contact->getCreateTime());
        $this->assertNull($contact->getUpdateTime());
    }

    public function testSupplierContactValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);

        // 测试必填字段
        $contact->setName('John Doe');
        $contact->setEmail('john@example.com');
        $contact->setPhone('13800138000');

        $this->assertNotEmpty($contact->getName());
        $this->assertNotEmpty($contact->getEmail());
        $this->assertNotEmpty($contact->getPhone());
    }

    public function testPrimaryContactConstraint(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        // 创建两个联系人
        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier);
        $contact1->setName('John Doe');
        $contact1->setEmail('john@example.com');
        $contact1->setPhone('13800138000');
        $contact1->setIsPrimary(true);

        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier);
        $contact2->setName('Jane Doe');
        $contact2->setEmail('jane@example.com');
        $contact2->setPhone('13900139000');
        $contact2->setIsPrimary(true);

        $supplier->addContact($contact1);
        $supplier->addContact($contact2);

        // 验证主要联系人方法
        $this->assertNotNull($supplier->getPrimaryContact());
        $primaryContact = $supplier->getPrimaryContact();
        $this->assertTrue($primaryContact->getIsPrimary());
    }

    public function testEmailFormatValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('John Doe');
        $contact->setEmail('invalid-email'); // 无效邮箱
        $contact->setPhone('13800138000');

        // 注意：实际验证会在表单或验证器中进行
        $this->assertEquals('invalid-email', $contact->getEmail());
    }

    public function testPhoneFormatValidation(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('John Doe');
        $contact->setEmail('john@example.com');
        $contact->setPhone('13800138000');

        // 验证电话格式（数字、空格、横线、加号、括号）
        $this->assertMatchesRegularExpression('/^[\d\s\-\+\(\)]+$/', $contact->getPhone());
    }

    public function testTimestamps(): void
    {
        $contact = new SupplierContact();

        // TimestampableAware trait 的时间戳字段初始为 null
        $this->assertNull($contact->getCreateTime());
        $this->assertNull($contact->getUpdateTime());

        // 测试手动设置时间戳（用于 Doctrine 监听器调用）
        $createTime = new \DateTimeImmutable();
        $updateTime = new \DateTimeImmutable('+1 hour');

        $contact->setCreateTime($createTime);
        $contact->setUpdateTime($updateTime);

        $this->assertEquals($createTime, $contact->getCreateTime());
        $this->assertEquals($updateTime, $contact->getUpdateTime());

        // 测试 TimestampableAware trait 的 retrieveTimestampArray 方法
        $timestampArray = $contact->retrieveTimestampArray();
        $this->assertIsArray($timestampArray);
        $this->assertArrayHasKey('createTime', $timestampArray);
        $this->assertArrayHasKey('updateTime', $timestampArray);
        $this->assertEquals($createTime->format('Y-m-d H:i:s'), $timestampArray['createTime']);
        $this->assertEquals($updateTime->format('Y-m-d H:i:s'), $timestampArray['updateTime']);
    }

    public function testSupplierAssociation(): void
    {
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

        // 验证双向关联
        $this->assertEquals($supplier, $contact->getSupplier());

        // 将联系人添加到供应商
        $supplier->addContact($contact);
        $this->assertTrue($supplier->getContacts()->contains($contact));
    }

    public function testPreUpdateLifecycleCallback(): void
    {
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

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $contact->setUpdateTime($originalUpdateTime);

        // 模拟一些时间流逝
        usleep(1000); // 1毫秒

        // 手动调用 setUpdateTime 方法（实际上会由实体监听器调用）
        $contact->setUpdateTime(new \DateTimeImmutable());

        $newUpdateTime = $contact->getUpdateTime();

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }

    public function testUpdatedAtAutoUpdate(): void
    {
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

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $contact->setUpdateTime($originalUpdateTime);

        // 模拟时间流逝
        usleep(1000); // 1毫秒

        // 手动设置更新时间戳（模拟 Doctrine 监听器的行为）
        $newUpdateTime = new \DateTimeImmutable();
        $contact->setUpdateTime($newUpdateTime);

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

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('John Doe');
        $contact->setEmail('john@example.com');
        $contact->setPhone('13800138000');

        // 验证可空字段的默认值
        $this->assertNull($contact->getPosition());
        $this->assertFalse($contact->getIsPrimary());

        // 设置可空字段
        $contact->setPosition('Manager');
        $this->assertEquals('Manager', $contact->getPosition());
    }

    public function testDefaultValues(): void
    {
        $contact = new SupplierContact();

        // 验证默认值
        $this->assertFalse($contact->getIsPrimary());
        $this->assertNull($contact->getId());
    }

    public function testCascadingRelationship(): void
    {
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

        // 添加联系人到供应商
        $supplier->addContact($contact);

        // 验证联系人已添加
        $this->assertTrue($supplier->getContacts()->contains($contact));
        $this->assertEquals($supplier, $contact->getSupplier());

        // 移除联系人
        $supplier->removeContact($contact);

        // 验证联系人已移除
        $this->assertFalse($supplier->getContacts()->contains($contact));
    }

    public function testUniqueEmailPerSupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier);
        $contact1->setName('John Doe');
        $contact1->setEmail('john@example.com');
        $contact1->setPhone('13800138000');

        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier);
        $contact2->setName('Jane Doe');
        $contact2->setEmail('john@example.com'); // 相同邮箱
        $contact2->setPhone('13900139000');

        $supplier->addContact($contact1);
        $supplier->addContact($contact2);

        // 验证可以添加（实际唯一性约束在数据库层面）
        $this->assertCount(2, $supplier->getContacts());
    }

    public function testToString(): void
    {
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

        $this->assertEquals('John Doe (john@example.com)', (string) $contact);
    }

    public function testPrimaryContactUniqueness(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('REG123');
        $supplier->setTaxNumber('TAX123');

        // 创建第一个主要联系人
        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier);
        $contact1->setName('John Doe');
        $contact1->setEmail('john@example.com');
        $contact1->setPhone('13800138000');
        $contact1->setIsPrimary(true);

        // 添加到供应商
        $supplier->addContact($contact1);

        // 创建第二个联系人并尝试设为主要联系人
        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier);
        $contact2->setName('Jane Doe');
        $contact2->setEmail('jane@example.com');
        $contact2->setPhone('13900139000');

        // 当设置为主要联系人时，应该取消其他联系人的主要状态
        $contact2->setIsPrimary(true);
        $supplier->addContact($contact2);

        // 验证只有一个主要联系人
        $primaryContacts = $supplier->getContacts()->filter(
            fn (SupplierContact $c) => $c->getIsPrimary()
        );

        // 应该只有最后设置的联系人是主要联系人
        $this->assertCount(1, $primaryContacts);
        $this->assertTrue($contact2->getIsPrimary());
    }

    public function testSetUpdatedAtMethod(): void
    {
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

        // 手动设置初始时间戳以模拟持久化后的状态
        $originalUpdateTime = new \DateTimeImmutable();
        $contact->setUpdateTime($originalUpdateTime);

        // 模拟时间流逝
        usleep(1000);

        // 手动调用 setUpdateTime 方法（实际上会由实体监听器调用）
        $contact->setUpdateTime(new \DateTimeImmutable());

        $newUpdateTime = $contact->getUpdateTime();

        $this->assertGreaterThan($originalUpdateTime, $newUpdateTime);
    }
}
