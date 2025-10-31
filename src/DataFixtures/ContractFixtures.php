<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\ContractStatus;

class ContractFixtures extends Fixture implements DependentFixtureInterface
{
    public const CONTRACT_A_SUPPLY_REFERENCE = 'contract-a-supply';
    public const CONTRACT_A_SERVICE_REFERENCE = 'contract-a-service';
    public const CONTRACT_B_PURCHASE_REFERENCE = 'contract-b-purchase';
    public const CONTRACT_C_LEASE_REFERENCE = 'contract-c-lease';

    public function load(ObjectManager $manager): void
    {
        /** @var Supplier $supplierA */
        $supplierA = $this->getReference(SupplierFixtures::SUPPLIER_A_REFERENCE, Supplier::class);

        // 供应商A的供应合同
        $contractA1 = new Contract();
        $contractA1->setSupplier($supplierA);
        $contractA1->setContractNumber('CON-2024-A-001');
        $contractA1->setTitle('原材料供应合同');
        $contractA1->setContractType('supply');
        $contractA1->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contractA1->setEndDate(new \DateTimeImmutable('2024-12-31'));
        $contractA1->setAmount(500000.00);
        $contractA1->setCurrency('CNY');
        $contractA1->setStatus(ContractStatus::ACTIVE);
        $contractA1->setDescription('年度原材料供应协议');
        $contractA1->setTerms('每月供货一次，质量保证，按时交付');

        // 供应商A的服务合同
        $contractA2 = new Contract();
        $contractA2->setSupplier($supplierA);
        $contractA2->setContractNumber('CON-2024-A-002');
        $contractA2->setTitle('技术服务合同');
        $contractA2->setContractType('service');
        $contractA2->setStartDate(new \DateTimeImmutable('2024-06-01'));
        $contractA2->setEndDate(new \DateTimeImmutable('2025-05-31'));
        $contractA2->setAmount(200000.00);
        $contractA2->setCurrency('CNY');
        $contractA2->setStatus(ContractStatus::APPROVED);
        $contractA2->setDescription('系统维护和技术支持服务');
        $contractA2->setTerms('7x24小时技术支持，响应时间4小时内');

        /** @var Supplier $supplierB */
        $supplierB = $this->getReference(SupplierFixtures::SUPPLIER_B_REFERENCE, Supplier::class);

        // 供应商B的采购合同
        $contractB1 = new Contract();
        $contractB1->setSupplier($supplierB);
        $contractB1->setContractNumber('CON-2024-B-001');
        $contractB1->setTitle('设备采购合同');
        $contractB1->setContractType('purchase');
        $contractB1->setStartDate(new \DateTimeImmutable('2024-03-01'));
        $contractB1->setEndDate(new \DateTimeImmutable('2024-08-31'));
        $contractB1->setAmount(800000.00);
        $contractB1->setCurrency('CNY');
        $contractB1->setStatus(ContractStatus::COMPLETED);
        $contractB1->setDescription('生产设备批量采购');
        $contractB1->setTerms('分三期交付，质保两年，免费培训');

        // 记录金额变更
        $contractB1->recordAmountChange(850000.00, '增加配件和安装服务费用');

        /** @var Supplier $supplierC */
        $supplierC = $this->getReference(SupplierFixtures::SUPPLIER_C_REFERENCE, Supplier::class);

        // 供应商C的租赁合同
        $contractC1 = new Contract();
        $contractC1->setSupplier($supplierC);
        $contractC1->setContractNumber('CON-2024-C-001');
        $contractC1->setTitle('厂房租赁合同');
        $contractC1->setContractType('lease');
        $contractC1->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contractC1->setEndDate(new \DateTimeImmutable('2026-12-31'));
        $contractC1->setAmount(1200000.00);
        $contractC1->setCurrency('CNY');
        $contractC1->setStatus(ContractStatus::ACTIVE);
        $contractC1->setDescription('生产厂房三年租赁协议');
        $contractC1->setTerms('年付租金，包含物业管理费，可续租');

        $manager->persist($contractA1);
        $manager->persist($contractA2);
        $manager->persist($contractB1);
        $manager->persist($contractC1);

        $this->addReference(self::CONTRACT_A_SUPPLY_REFERENCE, $contractA1);
        $this->addReference(self::CONTRACT_A_SERVICE_REFERENCE, $contractA2);
        $this->addReference(self::CONTRACT_B_PURCHASE_REFERENCE, $contractB1);
        $this->addReference(self::CONTRACT_C_LEASE_REFERENCE, $contractC1);

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
