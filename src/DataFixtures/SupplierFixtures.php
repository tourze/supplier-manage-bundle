<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;

class SupplierFixtures extends Fixture
{
    public const SUPPLIER_A_REFERENCE = 'supplier-a';
    public const SUPPLIER_B_REFERENCE = 'supplier-b';
    public const SUPPLIER_C_REFERENCE = 'supplier-c';

    public function load(ObjectManager $manager): void
    {
        $supplier1 = new Supplier();
        $supplier1->setName('测试供应商A');
        $supplier1->setLegalName('测试供应商A有限公司');
        $supplier1->setLegalAddress('北京市朝阳区测试路123号');
        $supplier1->setRegistrationNumber('11010101MA00ABC123');
        $supplier1->setTaxNumber('91110101MA00ABC123');
        $supplier1->setIndustry('制造业');
        $supplier1->setWebsite('https://images.unsplash.com/photo-1556740738-b6a63e27c4df');
        $supplier1->setIntroduction('专业的制造业供应商');
        $supplier1->setSupplierType(SupplierType::SUPPLIER);
        $supplier1->setCooperationModel(CooperationModel::DISTRIBUTION);
        $supplier1->setBusinessCategory('机械制造');
        $supplier1->setIsWarehouse(true);
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = new Supplier();
        $supplier2->setName('测试商家B');
        $supplier2->setLegalName('测试商家B有限公司');
        $supplier2->setLegalAddress('上海市浦东新区测试大道456号');
        $supplier2->setRegistrationNumber('31011501MA00DEF456');
        $supplier2->setTaxNumber('91310115MA00DEF456');
        $supplier2->setIndustry('信息技术');
        $supplier2->setWebsite('https://example.org');
        $supplier2->setIntroduction('专业的IT服务商家');
        $supplier2->setSupplierType(SupplierType::MERCHANT);
        $supplier2->setCooperationModel(CooperationModel::CONSIGNMENT);
        $supplier2->setBusinessCategory('软件开发');
        $supplier2->setIsWarehouse(false);
        $supplier2->setStatus(SupplierStatus::PENDING_REVIEW);

        $supplier3 = new Supplier();
        $supplier3->setName('测试供应商C');
        $supplier3->setLegalName('测试供应商C股份有限公司');
        $supplier3->setLegalAddress('广东省深圳市南山区测试街789号');
        $supplier3->setRegistrationNumber('44030007MA00GHI789');
        $supplier3->setTaxNumber('91440300MA00GHI789');
        $supplier3->setIndustry('电子商务');
        $supplier3->setWebsite('https://example.net');
        $supplier3->setIntroduction('专业的电商平台供应商');
        $supplier3->setSupplierType(SupplierType::SUPPLIER);
        $supplier3->setCooperationModel(CooperationModel::JOINT_VENTURE);
        $supplier3->setBusinessCategory('电商运营');
        $supplier3->setIsWarehouse(true);
        $supplier3->setStatus(SupplierStatus::DRAFT);

        $manager->persist($supplier1);
        $manager->persist($supplier2);
        $manager->persist($supplier3);

        $this->addReference(self::SUPPLIER_A_REFERENCE, $supplier1);
        $this->addReference(self::SUPPLIER_B_REFERENCE, $supplier2);
        $this->addReference(self::SUPPLIER_C_REFERENCE, $supplier3);

        $manager->flush();
    }
}
