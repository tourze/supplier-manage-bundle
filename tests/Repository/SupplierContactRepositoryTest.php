<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;
use Tourze\SupplierManageBundle\Repository\SupplierContactRepository;

/**
 * @internal
 */
#[CoversClass(SupplierContactRepository::class)]
#[RunTestsInSeparateProcesses]
class SupplierContactRepositoryTest extends AbstractRepositoryTestCase
{
    private SupplierContactRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SupplierContactRepository::class);

        // 清理现有数据，确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . SupplierContact::class . ' sc')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . Supplier::class . ' s')->execute();

        // 创建一个 DataFixture 测试数据以满足基类测试要求
        $supplier = new Supplier();
        $supplier->setName('DataFixture Test Supplier');
        $supplier->setLegalName('DataFixture Test Legal');
        $supplier->setLegalAddress('DataFixture Test Address');
        $supplier->setRegistrationNumber('DATA-FIXTURE-' . uniqid());
        $supplier->setTaxNumber('DATA-FIXTURE-TAX-' . uniqid());

        self::getEntityManager()->persist($supplier);

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('DataFixture Test Contact');
        $contact->setPosition('DataFixture Manager');
        $contact->setEmail('datafixture@test.com');
        $contact->setPhone('13800138000');
        $contact->setIsPrimary(true);

        self::getEntityManager()->persist($contact);
        self::getEntityManager()->flush();

        // 清除实体管理器缓存，确保测试方法能正常工作
        self::getEntityManager()->clear();
    }

    protected function createNewEntity(): SupplierContact
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('TEST' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());

        self::getEntityManager()->persist($supplier);

        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('张三');
        $contact->setPosition('销售经理');
        $contact->setEmail('zhangsan@test.com');
        $contact->setPhone('13800138000');
        $contact->setIsPrimary(false);

        return $contact;
    }

    /**
     * @return ServiceEntityRepository<SupplierContact>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindBySupplier(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For FindBy');
        $supplier->setLegalName('Test Legal Name For FindBy');
        $supplier->setLegalAddress('Test Address For FindBy');
        $supplier->setRegistrationNumber('TEST-FIND-BY-' . uniqid());
        $supplier->setTaxNumber('TAX-FIND-BY-' . uniqid());
        self::getEntityManager()->persist($supplier);

        // 创建两个联系人，都属于同一个供应商
        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier);
        $contact1->setName('张三');
        $contact1->setPosition('销售经理');
        $contact1->setEmail('zhangsan@test.com');
        $contact1->setPhone('13800138000');
        $contact1->setIsPrimary(true);

        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier);
        $contact2->setName('李四');
        $contact2->setEmail('lisi@test.com');
        $contact2->setPosition('技术总监');
        $contact2->setPhone('13900139000');
        $contact2->setIsPrimary(false);

        self::getEntityManager()->persist($contact1);
        self::getEntityManager()->persist($contact2);
        self::getEntityManager()->flush();

        $contacts = $this->repository->findBySupplier($supplier);
        $this->assertCount(2, $contacts);
        $this->assertEquals('张三', $contacts[0]->getName()); // 按主要联系人降序排列
        $this->assertEquals('李四', $contacts[1]->getName());
    }

    public function testFindPrimaryContactBySupplier(): void
    {
        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Primary');
        $supplier->setLegalName('Test Legal Name For Primary');
        $supplier->setLegalAddress('Test Address For Primary');
        $supplier->setRegistrationNumber('TEST-PRIMARY-' . uniqid());
        $supplier->setTaxNumber('TAX-PRIMARY-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $primaryContact = new SupplierContact();
        $primaryContact->setSupplier($supplier);
        $primaryContact->setName('张三');
        $primaryContact->setPosition('销售经理');
        $primaryContact->setEmail('zhangsan@test.com');
        $primaryContact->setPhone('13800138000');
        $primaryContact->setIsPrimary(true);

        $secondaryContact = new SupplierContact();
        $secondaryContact->setSupplier($supplier);
        $secondaryContact->setName('李四');
        $secondaryContact->setEmail('lisi@test.com');
        $secondaryContact->setPosition('技术总监');
        $secondaryContact->setPhone('13900139000');
        $secondaryContact->setIsPrimary(false);

        self::getEntityManager()->persist($primaryContact);
        self::getEntityManager()->persist($secondaryContact);
        self::getEntityManager()->flush();

        $primary = $this->repository->findPrimaryContactBySupplier($supplier);
        $this->assertNotNull($primary);
        $this->assertEquals('张三', $primary->getName());
        $this->assertTrue($primary->getIsPrimary());
    }

    public function testFindByEmail(): void
    {
        $contact = $this->createNewEntity();
        $uniqueEmail = 'unique-' . uniqid() . '@test.com';
        $contact->setEmail($uniqueEmail);

        self::getEntityManager()->persist($contact);
        self::getEntityManager()->flush();

        $found = $this->repository->findByEmail($uniqueEmail);
        $this->assertNotNull($found);
        $this->assertEquals($uniqueEmail, $found->getEmail());
        $this->assertEquals('张三', $found->getName());

        $notFound = $this->repository->findByEmail('nonexistent@test.com');
        $this->assertNull($notFound);
    }

    public function testSearch(): void
    {
        $uniqueId = uniqid();
        $contact = $this->createNewEntity();
        $contact->setName('张总经理' . $uniqueId);
        $contact->setEmail("zhangzong-{$uniqueId}@company.com");
        $contact->setPosition('总经理');

        self::getEntityManager()->persist($contact);
        self::getEntityManager()->flush();

        $resultsByName = $this->repository->search(['name' => '张总经理' . $uniqueId]);
        $this->assertCount(1, $resultsByName);
        $this->assertEquals('张总经理' . $uniqueId, $resultsByName[0]->getName());

        $resultsByEmail = $this->repository->search(['email' => "zhangzong-{$uniqueId}"]);
        $this->assertCount(1, $resultsByEmail);
        $this->assertEquals("zhangzong-{$uniqueId}@company.com", $resultsByEmail[0]->getEmail());

        // 只测试搜索本次创建的联系人
        $allByPrimary = $this->repository->search(['is_primary' => false]);
        $testContacts = array_filter($allByPrimary, fn ($c) => str_contains($c->getName(), $uniqueId));
        $this->assertCount(1, $testContacts);
        $this->assertEquals('张总经理' . $uniqueId, reset($testContacts)->getName());
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

        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier);
        $contact1->setName('张三');
        $contact1->setPosition('销售经理');
        $contact1->setEmail('zhangsan@test.com');
        $contact1->setPhone('13800138000');
        $contact1->setIsPrimary(true);

        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier);
        $contact2->setName('李四');
        $contact2->setEmail('lisi@test.com');
        $contact2->setPosition('技术总监');
        $contact2->setPhone('13900139000');
        $contact2->setIsPrimary(false);

        self::getEntityManager()->persist($contact1);
        self::getEntityManager()->persist($contact2);
        self::getEntityManager()->flush();

        $count = $this->repository->countBySupplier($supplier);
        $this->assertEquals(2, $count);
    }

    public function testSaveAndRemove(): void
    {
        $contact = $this->createNewEntity();

        $this->repository->save($contact, true);
        $this->assertNotNull($contact->getId());

        $found = $this->repository->find($contact->getId());
        $this->assertInstanceOf(SupplierContact::class, $found);
        $this->assertEquals('张三', $found->getName());
        $this->assertEquals('zhangsan@test.com', $found->getEmail());

        $savedId = $contact->getId();
        $this->repository->remove($contact, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testFindPrimaryContactsBySupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Primary Contacts Test Supplier');
        $supplier->setLegalName('Primary Contacts Legal');
        $supplier->setLegalAddress('Primary Contacts Address');
        $supplier->setRegistrationNumber('PRIMARY-CONTACTS-' . uniqid());
        $supplier->setTaxNumber('PRIMARY-CONTACTS-TAX-' . uniqid());
        self::getEntityManager()->persist($supplier);

        $primaryContact1 = new SupplierContact();
        $primaryContact1->setSupplier($supplier);
        $primaryContact1->setName('张三 - 主要联系人1');
        $primaryContact1->setPosition('销售经理');
        $primaryContact1->setEmail('primary1@test.com');
        $primaryContact1->setPhone('13800138001');
        $primaryContact1->setIsPrimary(true);

        $primaryContact2 = new SupplierContact();
        $primaryContact2->setSupplier($supplier);
        $primaryContact2->setName('李四 - 主要联系人2');
        $primaryContact2->setPosition('市场经理');
        $primaryContact2->setEmail('primary2@test.com');
        $primaryContact2->setPhone('13800138002');
        $primaryContact2->setIsPrimary(true);

        $secondaryContact = new SupplierContact();
        $secondaryContact->setSupplier($supplier);
        $secondaryContact->setName('王五 - 次要联系人');
        $secondaryContact->setPosition('技术总监');
        $secondaryContact->setEmail('secondary@test.com');
        $secondaryContact->setPhone('13800138003');
        $secondaryContact->setIsPrimary(false);

        self::getEntityManager()->persist($primaryContact1);
        self::getEntityManager()->persist($primaryContact2);
        self::getEntityManager()->persist($secondaryContact);
        self::getEntityManager()->flush();

        $primaryContacts = $this->repository->findPrimaryContactsBySupplier($supplier);
        $this->assertCount(2, $primaryContacts);

        $primaryNames = array_map(fn ($contact) => $contact->getName(), $primaryContacts);
        $this->assertContains('张三 - 主要联系人1', $primaryNames);
        $this->assertContains('李四 - 主要联系人2', $primaryNames);
        $this->assertNotContains('王五 - 次要联系人', $primaryNames);

        foreach ($primaryContacts as $contact) {
            $this->assertTrue($contact->getIsPrimary());
        }
    }

    public function testRemove(): void
    {
        $contact = $this->createNewEntity();
        self::getEntityManager()->persist($contact);
        self::getEntityManager()->flush();

        $contactId = $contact->getId();
        $this->assertNotNull($contactId);

        $foundBefore = $this->repository->find($contactId);
        $this->assertNotNull($foundBefore);
        $this->assertEquals('张三', $foundBefore->getName());

        $this->repository->remove($contact, true);

        $foundAfter = $this->repository->find($contactId);
        $this->assertNull($foundAfter);
    }
}
