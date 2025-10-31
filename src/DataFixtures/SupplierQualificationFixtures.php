<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;

class SupplierQualificationFixtures extends Fixture implements DependentFixtureInterface
{
    public const QUALIFICATION_A_ISO_REFERENCE = 'qualification-a-iso';
    public const QUALIFICATION_A_QUALITY_REFERENCE = 'qualification-a-quality';
    public const QUALIFICATION_B_SAFETY_REFERENCE = 'qualification-b-safety';
    public const QUALIFICATION_C_ENV_REFERENCE = 'qualification-c-env';

    public function load(ObjectManager $manager): void
    {
        /** @var Supplier $supplierA */
        $supplierA = $this->getReference(SupplierFixtures::SUPPLIER_A_REFERENCE, Supplier::class);

        // 供应商A的ISO认证
        $qualificationA1 = new SupplierQualification();
        $qualificationA1->setSupplier($supplierA);
        $qualificationA1->setName('ISO 9001:2015 质量管理体系认证');
        $qualificationA1->setType('quality');
        $qualificationA1->setCertificateNumber('ISO-9001-2024-001');
        $qualificationA1->setIssuingAuthority('中国质量认证中心');
        $qualificationA1->setIssuedDate(new \DateTimeImmutable('2024-01-15'));
        $qualificationA1->setExpiryDate(new \DateTimeImmutable('2027-01-15'));
        $qualificationA1->setFilePath('/uploads/qualifications/iso9001_supplier_a.pdf');
        $qualificationA1->setRemarks('三年有效期认证');

        // 供应商A的质量认证
        $qualificationA2 = new SupplierQualification();
        $qualificationA2->setSupplier($supplierA);
        $qualificationA2->setName('产品质量认证证书');
        $qualificationA2->setType('quality');
        $qualificationA2->setCertificateNumber('QC-2024-002');
        $qualificationA2->setIssuingAuthority('国家质检总局');
        $qualificationA2->setIssuedDate(new \DateTimeImmutable('2024-03-01'));
        $qualificationA2->setExpiryDate(new \DateTimeImmutable('2025-03-01'));
        $qualificationA2->setFilePath('/uploads/qualifications/quality_cert_a.pdf');

        /** @var Supplier $supplierB */
        $supplierB = $this->getReference(SupplierFixtures::SUPPLIER_B_REFERENCE, Supplier::class);

        // 供应商B的安全认证
        $qualificationB1 = new SupplierQualification();
        $qualificationB1->setSupplier($supplierB);
        $qualificationB1->setName('安全生产许可证');
        $qualificationB1->setType('safety');
        $qualificationB1->setCertificateNumber('SAFETY-2024-001');
        $qualificationB1->setIssuingAuthority('安全生产监督管理局');
        $qualificationB1->setIssuedDate(new \DateTimeImmutable('2023-12-01'));
        $qualificationB1->setExpiryDate(new \DateTimeImmutable('2026-12-01'));
        $qualificationB1->setFilePath('/uploads/qualifications/safety_permit_b.pdf');
        $qualificationB1->setRemarks('三年期安全许可');

        /** @var Supplier $supplierC */
        $supplierC = $this->getReference(SupplierFixtures::SUPPLIER_C_REFERENCE, Supplier::class);

        // 供应商C的环保认证
        $qualificationC1 = new SupplierQualification();
        $qualificationC1->setSupplier($supplierC);
        $qualificationC1->setName('环境管理体系认证');
        $qualificationC1->setType('environment');
        $qualificationC1->setCertificateNumber('ENV-14001-2024');
        $qualificationC1->setIssuingAuthority('环境保护认证中心');
        $qualificationC1->setIssuedDate(new \DateTimeImmutable('2024-02-20'));
        $qualificationC1->setExpiryDate(new \DateTimeImmutable('2025-12-31'));
        $qualificationC1->setFilePath('/uploads/qualifications/env_cert_c.pdf');
        $qualificationC1->setIsActive(false);
        $qualificationC1->setRemarks('已停用，待更新');

        $manager->persist($qualificationA1);
        $manager->persist($qualificationA2);
        $manager->persist($qualificationB1);
        $manager->persist($qualificationC1);

        $this->addReference(self::QUALIFICATION_A_ISO_REFERENCE, $qualificationA1);
        $this->addReference(self::QUALIFICATION_A_QUALITY_REFERENCE, $qualificationA2);
        $this->addReference(self::QUALIFICATION_B_SAFETY_REFERENCE, $qualificationB1);
        $this->addReference(self::QUALIFICATION_C_ENV_REFERENCE, $qualificationC1);

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
