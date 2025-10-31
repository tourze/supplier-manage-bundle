<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;

class SupplierContactFixtures extends Fixture implements DependentFixtureInterface
{
    public const CONTACT_A_PRIMARY_REFERENCE = 'contact-a-primary';
    public const CONTACT_A_SECONDARY_REFERENCE = 'contact-a-secondary';
    public const CONTACT_B_PRIMARY_REFERENCE = 'contact-b-primary';
    public const CONTACT_C_PRIMARY_REFERENCE = 'contact-c-primary';

    public function load(ObjectManager $manager): void
    {
        /** @var Supplier $supplierA */
        $supplierA = $this->getReference(SupplierFixtures::SUPPLIER_A_REFERENCE, Supplier::class);

        // 供应商A的主要联系人
        $contactA1 = new SupplierContact();
        $contactA1->setSupplier($supplierA);
        $contactA1->setName('张三');
        $contactA1->setPosition('销售经理');
        $contactA1->setEmail('zhangsan@supplier-a.com');
        $contactA1->setPhone('13800138001');
        $contactA1->setIsPrimary(true);

        // 供应商A的次要联系人
        $contactA2 = new SupplierContact();
        $contactA2->setSupplier($supplierA);
        $contactA2->setName('李四');
        $contactA2->setPosition('技术支持');
        $contactA2->setEmail('lisi@supplier-a.com');
        $contactA2->setPhone('13800138002');
        $contactA2->setIsPrimary(false);

        /** @var Supplier $supplierB */
        $supplierB = $this->getReference(SupplierFixtures::SUPPLIER_B_REFERENCE, Supplier::class);

        // 供应商B的主要联系人
        $contactB1 = new SupplierContact();
        $contactB1->setSupplier($supplierB);
        $contactB1->setName('王五');
        $contactB1->setPosition('客户经理');
        $contactB1->setEmail('wangwu@merchant-b.com');
        $contactB1->setPhone('13900139001');
        $contactB1->setIsPrimary(true);

        /** @var Supplier $supplierC */
        $supplierC = $this->getReference(SupplierFixtures::SUPPLIER_C_REFERENCE, Supplier::class);

        // 供应商C的主要联系人
        $contactC1 = new SupplierContact();
        $contactC1->setSupplier($supplierC);
        $contactC1->setName('赵六');
        $contactC1->setPosition('业务主管');
        $contactC1->setEmail('zhaoliu@supplier-c.com');
        $contactC1->setPhone('15000150001');
        $contactC1->setIsPrimary(true);

        $manager->persist($contactA1);
        $manager->persist($contactA2);
        $manager->persist($contactB1);
        $manager->persist($contactC1);

        $this->addReference(self::CONTACT_A_PRIMARY_REFERENCE, $contactA1);
        $this->addReference(self::CONTACT_A_SECONDARY_REFERENCE, $contactA2);
        $this->addReference(self::CONTACT_B_PRIMARY_REFERENCE, $contactB1);
        $this->addReference(self::CONTACT_C_PRIMARY_REFERENCE, $contactC1);

        $manager->flush();
    }

    /**
     * @return array<class-string<Fixture>>
     */
    public function getDependencies(): array
    {
        return [
            SupplierFixtures::class,
        ];
    }
}
