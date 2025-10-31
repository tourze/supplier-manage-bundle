<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;
use Tourze\SupplierManageBundle\Repository\SupplierQualificationRepository;

/**
 * @internal
 */
#[CoversClass(SupplierQualificationRepository::class)]
#[RunTestsInSeparateProcesses]
class SupplierQualificationRepositoryTest extends AbstractRepositoryTestCase
{
    private SupplierQualificationRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SupplierQualificationRepository::class);

        // 清理现有数据，确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . SupplierQualification::class . ' sq')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . Supplier::class . ' s')->execute();

        // 创建一个 DataFixture 测试数据以满足基类测试要求
        $supplier = new Supplier();
        $supplier->setName('DataFixture Test Supplier');
        $supplier->setLegalName('DataFixture Test Legal');
        $supplier->setLegalAddress('DataFixture Test Address');
        $supplier->setRegistrationNumber('DATA-FIXTURE-' . uniqid());
        $supplier->setTaxNumber('DATA-FIXTURE-TAX-' . uniqid());

        self::getEntityManager()->persist($supplier);

        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplier);
        $qualification->setName('DataFixture ISO 9001认证');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('DATA-FIXTURE-ISO-' . uniqid());
        $qualification->setIssuingAuthority('DataFixture Authority');
        $qualification->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualification->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualification->setIsActive(true);
        $qualification->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualification);
        self::getEntityManager()->flush();

        // 清除实体管理器缓存，确保测试方法能正常工作
        self::getEntityManager()->clear();
    }

    protected function createNewEntity(): SupplierQualification
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('TEST' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());

        self::getEntityManager()->persist($supplier);

        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplier);
        $qualification->setName('ISO 9001质量管理体系认证');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('ISO-' . uniqid());
        $qualification->setIssuingAuthority('国际标准化组织');
        $qualification->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualification->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualification->setIsActive(true);
        $qualification->setStatus(SupplierQualificationStatus::APPROVED);

        return $qualification;
    }

    /**
     * @return ServiceEntityRepository<SupplierQualification>
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

        // 创建两个资质，都属于同一个供应商
        $qualification1 = new SupplierQualification();
        $qualification1->setSupplier($supplier);
        $qualification1->setName('ISO 9001质量管理体系认证');
        $qualification1->setType('quality');
        $qualification1->setCertificateNumber('ISO-' . uniqid());
        $qualification1->setIssuingAuthority('国际标准化组织');
        $qualification1->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualification1->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualification1->setIsActive(true);
        $qualification1->setStatus(SupplierQualificationStatus::APPROVED);

        $qualification2 = new SupplierQualification();
        $qualification2->setSupplier($supplier);
        $qualification2->setName('安全生产许可证');
        $qualification2->setType('safety');
        $qualification2->setCertificateNumber('SAFETY-' . uniqid());
        $qualification2->setIssuingAuthority('安全生产监督管理局');
        $qualification2->setIssuedDate(new \DateTimeImmutable('-6 months'));
        $qualification2->setExpiryDate(new \DateTimeImmutable('+1 year'));
        $qualification2->setIsActive(true);
        $qualification2->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualification1);
        self::getEntityManager()->persist($qualification2);
        self::getEntityManager()->flush();

        $qualifications = $this->repository->findBySupplier($supplier);
        $this->assertCount(2, $qualifications);
    }

    public function testFindActiveBySupplier(): void
    {
        $activeQualification = $this->createNewEntity();
        $activeQualification->setIsActive(true);
        $activeQualification->setExpiryDate(new \DateTimeImmutable('+1 year'));

        $inactiveQualification = $this->createNewEntity();
        $inactiveQualification->setIsActive(false);
        $inactiveQualification->setName('已停用资质');
        $inactiveQualification->setCertificateNumber('INACTIVE-' . uniqid());

        $expiredQualification = $this->createNewEntity();
        $expiredQualification->setIsActive(true);
        $expiredQualification->setExpiryDate(new \DateTimeImmutable('-1 day'));
        $expiredQualification->setName('已过期资质');
        $expiredQualification->setCertificateNumber('EXPIRED-' . uniqid());

        self::getEntityManager()->persist($activeQualification);
        self::getEntityManager()->persist($inactiveQualification);
        self::getEntityManager()->persist($expiredQualification);
        self::getEntityManager()->flush();

        $activeQualifications = $this->repository->findActiveBySupplier($activeQualification->getSupplier());
        $this->assertCount(1, $activeQualifications);
        $this->assertEquals('ISO 9001质量管理体系认证', $activeQualifications[0]->getName());
    }

    public function testFindExpiringWithinDays(): void
    {
        $expiringQualification = $this->createNewEntity();
        $expiringQualification->setExpiryDate(new \DateTimeImmutable('+15 days'));
        $expiringQualification->setIsActive(true);

        $futureQualification = $this->createNewEntity();
        $futureQualification->setExpiryDate(new \DateTimeImmutable('+60 days'));
        $futureQualification->setName('远期到期资质');
        $futureQualification->setCertificateNumber('FUTURE-' . uniqid());
        $futureQualification->setIsActive(true);

        self::getEntityManager()->persist($expiringQualification);
        self::getEntityManager()->persist($futureQualification);
        self::getEntityManager()->flush();

        $expiringQualifications = $this->repository->findExpiringWithinDays(30);
        $this->assertCount(1, $expiringQualifications);
        $this->assertEquals('ISO 9001质量管理体系认证', $expiringQualifications[0]->getName());
    }

    public function testFindByType(): void
    {
        // 使用唯一类型避免冲突
        $uniqueId = uniqid();
        $qualityType = 'quality-' . $uniqueId;
        $safetyType = 'safety-' . $uniqueId;

        // 创建一个供应商
        $supplier = new Supplier();
        $supplier->setName('Test Supplier For Type');
        $supplier->setLegalName('Test Legal Name For Type');
        $supplier->setLegalAddress('Test Address For Type');
        $supplier->setRegistrationNumber('TEST-TYPE-' . $uniqueId);
        $supplier->setTaxNumber('TAX-TYPE-' . $uniqueId);
        self::getEntityManager()->persist($supplier);

        $qualityQualification = new SupplierQualification();
        $qualityQualification->setSupplier($supplier);
        $qualityQualification->setName('ISO 9001质量管理体系认证');
        $qualityQualification->setType($qualityType);
        $qualityQualification->setCertificateNumber('ISO-' . $uniqueId);
        $qualityQualification->setIssuingAuthority('国际标准化组织');
        $qualityQualification->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualityQualification->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualityQualification->setIsActive(true);
        $qualityQualification->setStatus(SupplierQualificationStatus::APPROVED);

        $safetyQualification = new SupplierQualification();
        $safetyQualification->setSupplier($supplier);
        $safetyQualification->setName('安全生产许可证');
        $safetyQualification->setType($safetyType);
        $safetyQualification->setCertificateNumber('SAFETY-' . $uniqueId);
        $safetyQualification->setIssuingAuthority('安全生产监督管理局');
        $safetyQualification->setIssuedDate(new \DateTimeImmutable('-6 months'));
        $safetyQualification->setExpiryDate(new \DateTimeImmutable('+1 year'));
        $safetyQualification->setIsActive(true);
        $safetyQualification->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualityQualification);
        self::getEntityManager()->persist($safetyQualification);
        self::getEntityManager()->flush();

        $qualityQualifications = $this->repository->findByType($qualityType);
        $this->assertCount(1, $qualityQualifications);
        $this->assertEquals('ISO 9001质量管理体系认证', $qualityQualifications[0]->getName());

        $safetyQualifications = $this->repository->findByType($safetyType);
        $this->assertCount(1, $safetyQualifications);
        $this->assertEquals('安全生产许可证', $safetyQualifications[0]->getName());
    }

    public function testSearch(): void
    {
        // 创建一个专门的测试用实体，避免与createNewEntity冲突
        $supplier = new Supplier();
        $supplier->setName('Search Test Supplier');
        $supplier->setLegalName('Search Test Legal Name');
        $supplier->setLegalAddress('Search Test Address');
        $supplier->setRegistrationNumber('SEARCH' . uniqid());
        $supplier->setTaxNumber('SEARCHTAX' . uniqid());

        self::getEntityManager()->persist($supplier);

        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplier);
        $qualification->setName('ISO质量认证测试专用');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('ISO-CERT-123');
        $qualification->setIssuingAuthority('测试专用质量认证中心');
        $qualification->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualification->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualification->setIsActive(true);
        $qualification->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualification);
        self::getEntityManager()->flush();

        $resultsByName = $this->repository->search(['name' => 'ISO质量认证测试专用']);
        $this->assertCount(1, $resultsByName);
        $this->assertEquals('ISO质量认证测试专用', $resultsByName[0]->getName());

        $resultsByCertNumber = $this->repository->search(['certificate_number' => 'CERT-123']);
        $this->assertCount(1, $resultsByCertNumber);
        $this->assertEquals('ISO质量认证测试专用', $resultsByCertNumber[0]->getName());

        $resultsByAuthority = $this->repository->search(['issuing_authority' => '测试专用质量认证中心']);
        $this->assertCount(1, $resultsByAuthority);
        $this->assertEquals('ISO质量认证测试专用', $resultsByAuthority[0]->getName());
    }

    public function testCountBySupplier(): void
    {
        $qualification1 = $this->createNewEntity();

        // 使用相同的supplier创建第二个qualification
        $qualification2 = new SupplierQualification();
        $qualification2->setSupplier($qualification1->getSupplier());
        $qualification2->setName('环境管理体系认证');
        $qualification2->setType('environment');
        $qualification2->setCertificateNumber('ENV-' . uniqid());
        $qualification2->setIssuingAuthority('环保部门');
        $qualification2->setIssuedDate(new \DateTimeImmutable('-6 months'));
        $qualification2->setExpiryDate(new \DateTimeImmutable('+18 months'));
        $qualification2->setIsActive(true);
        $qualification2->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualification1);
        self::getEntityManager()->persist($qualification2);
        self::getEntityManager()->flush();

        $count = $this->repository->countBySupplier($qualification1->getSupplier());
        $this->assertEquals(2, $count);
    }

    public function testSaveAndRemove(): void
    {
        $qualification = $this->createNewEntity();

        $this->repository->save($qualification, true);
        $this->assertNotNull($qualification->getId());

        $found = $this->repository->find($qualification->getId());
        $this->assertInstanceOf(SupplierQualification::class, $found);
        $this->assertEquals('ISO 9001质量管理体系认证', $found->getName());

        $savedId = $qualification->getId();
        $this->repository->remove($qualification, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testRemove(): void
    {
        $qualification = $this->createNewEntity();
        self::getEntityManager()->persist($qualification);
        self::getEntityManager()->flush();

        $qualificationId = $qualification->getId();
        $this->assertNotNull($qualificationId);

        $foundBefore = $this->repository->find($qualificationId);
        $this->assertNotNull($foundBefore);
        $this->assertEquals('ISO 9001质量管理体系认证', $foundBefore->getName());

        $this->repository->remove($qualification, true);

        $foundAfter = $this->repository->find($qualificationId);
        $this->assertNull($foundAfter);
    }
}
