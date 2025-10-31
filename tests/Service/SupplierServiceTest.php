<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Test;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Exception\SupplierException;
use Tourze\SupplierManageBundle\Repository\SupplierRepository;
use Tourze\SupplierManageBundle\Service\SupplierService;

/**
 * SupplierService 集成测试
 *
 * @internal
 */
#[CoversClass(SupplierService::class)]
#[RunTestsInSeparateProcesses]
final class SupplierServiceTest extends AbstractIntegrationTestCase
{
    private SupplierService $supplierService;

    private SupplierRepository $supplierRepository;

    protected function onSetUp(): void
    {
        $this->supplierService = self::getService(SupplierService::class);
        $this->supplierRepository = self::getService(SupplierRepository::class);
    }

    #[Test]
    public function testCreateSupplierWithValidData(): void
    {
        $data = [
            'name' => 'Test Supplier',
            'legalName' => 'Test Legal Supplier',
            'supplierType' => SupplierType::SUPPLIER->value,
            'legalAddress' => '123 Test St',
            'registrationNumber' => 'REG123456',
            'taxNumber' => 'TAX123456',
        ];

        $supplier = $this->supplierService->createSupplier($data);

        $this->assertInstanceOf(Supplier::class, $supplier);
        $this->assertSame('Test Supplier', $supplier->getName());
        $this->assertSame(SupplierType::SUPPLIER, $supplier->getSupplierType());
        $this->assertSame(SupplierStatus::DRAFT, $supplier->getStatus());
        $this->assertNotNull($supplier->getId());
    }

    #[Test]
    public function testValidateSupplierDataWithValidData(): void
    {
        $data = [
            'name' => 'Test Supplier',
            'legalName' => 'Test Legal Supplier',
            'supplierType' => SupplierType::SUPPLIER->value,
            'legalAddress' => '123 Test St',
            'registrationNumber' => 'REG123456',
            'taxNumber' => 'TAX123456',
        ];

        $result = $this->supplierService->validateSupplierData($data);

        $this->assertTrue($result);
    }

    #[Test]
    public function testValidateSupplierDataWithMissingFields(): void
    {
        $data = [
            'name' => 'Test Supplier',
            // 缺少必填字段
        ];

        $result = $this->supplierService->validateSupplierData($data);

        $this->assertFalse($result);
    }

    #[Test]
    public function testSubmitForReview(): void
    {
        $supplier = $this->createTestSupplier();

        $this->supplierService->submitForReview($supplier);

        $this->assertSame(SupplierStatus::PENDING_REVIEW, $supplier->getStatus());
    }

    #[Test]
    public function testSubmitForReviewWithNonDraftStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::APPROVED);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有草稿状态的供应商可以提交审核');

        $this->supplierService->submitForReview($supplier);
    }

    #[Test]
    public function testApproveSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::PENDING_REVIEW);

        $this->supplierService->approveSupplier($supplier);

        $this->assertSame(SupplierStatus::APPROVED, $supplier->getStatus());
    }

    #[Test]
    public function testApproveSupplierWithWrongStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有待审核状态的供应商可以批准');

        $this->supplierService->approveSupplier($supplier);
    }

    #[Test]
    public function testRejectSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::PENDING_REVIEW);

        $this->supplierService->rejectSupplier($supplier, 'Test rejection reason');

        $this->assertSame(SupplierStatus::REJECTED, $supplier->getStatus());
    }

    #[Test]
    public function testSuspendSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::APPROVED);

        $this->supplierService->suspendSupplier($supplier, 'Test suspension reason');

        $this->assertSame(SupplierStatus::SUSPENDED, $supplier->getStatus());
    }

    #[Test]
    public function testActivateSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::SUSPENDED);

        $this->supplierService->activateSupplier($supplier);

        $this->assertSame(SupplierStatus::APPROVED, $supplier->getStatus());
    }

    #[Test]
    public function testTerminateSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::APPROVED);

        $this->supplierService->terminateSupplier($supplier, 'Test termination reason');

        $this->assertSame(SupplierStatus::TERMINATED, $supplier->getStatus());
    }

    #[Test]
    public function testGetActiveSuppliers(): void
    {
        $supplier1 = $this->createTestSupplier();
        $supplier1->setStatus(SupplierStatus::APPROVED);
        $this->supplierRepository->save($supplier1);

        $supplier2 = $this->createTestSupplier();
        $supplier2->setStatus(SupplierStatus::SUSPENDED);
        $this->supplierRepository->save($supplier2);

        $activeSuppliers = $this->supplierService->getActiveSuppliers();

        $this->assertIsArray($activeSuppliers);
        $this->assertContains($supplier1, $activeSuppliers);
        $this->assertContains($supplier2, $activeSuppliers);
    }

    #[Test]
    public function testGetSupplierStatistics(): void
    {
        $this->createTestSupplier();

        $stats = $this->supplierService->getSupplierStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('draft', $stats);
        $this->assertArrayHasKey('pending_review', $stats);
        $this->assertArrayHasKey('approved', $stats);
        $this->assertArrayHasKey('rejected', $stats);
        $this->assertArrayHasKey('suspended', $stats);
        $this->assertArrayHasKey('terminated', $stats);
        $this->assertArrayHasKey('total', $stats);
        $this->assertIsInt($stats['total']);
    }

    #[Test]
    public function testUpdateSupplier(): void
    {
        $supplier = $this->createTestSupplier();
        $originalName = $supplier->getName();

        $updateData = [
            'name' => 'Updated Supplier Name',
            'legalName' => 'Updated Legal Name',
            'supplierType' => SupplierType::MERCHANT->value,
        ];

        $updatedSupplier = $this->supplierService->updateSupplier($supplier, $updateData);

        $this->assertSame($supplier, $updatedSupplier);
        $this->assertSame('Updated Supplier Name', $supplier->getName());
        $this->assertSame('Updated Legal Name', $supplier->getLegalName());
        $this->assertSame(SupplierType::MERCHANT, $supplier->getSupplierType());
    }

    #[Test]
    public function testSearchSuppliers(): void
    {
        $supplier = $this->createTestSupplier();

        $results = $this->supplierService->searchSuppliers('Test Supplier');

        $this->assertIsArray($results);
        $this->assertContains($supplier, $results);
    }

    #[Test]
    public function testCheckDuplicateRegistration(): void
    {
        $supplier = $this->createTestSupplier();

        $isDuplicate = $this->supplierService->checkDuplicateRegistration($supplier->getRegistrationNumber());

        $this->assertTrue($isDuplicate);

        $isNotDuplicate = $this->supplierService->checkDuplicateRegistration('UNIQUE_REG_NUMBER');

        $this->assertFalse($isNotDuplicate);
    }

    #[Test]
    public function testCheckDuplicateTaxNumber(): void
    {
        $supplier = $this->createTestSupplier();

        $isDuplicate = $this->supplierService->checkDuplicateTaxNumber($supplier->getTaxNumber());

        $this->assertTrue($isDuplicate);

        $isNotDuplicate = $this->supplierService->checkDuplicateTaxNumber('UNIQUE_TAX_NUMBER');

        $this->assertFalse($isNotDuplicate);
    }

    #[Test]
    public function testRejectSupplierWithWrongStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有待审核状态的供应商可以拒绝');

        $this->supplierService->rejectSupplier($supplier, 'Test rejection reason');
    }

    #[Test]
    public function testSuspendSupplierWithWrongStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有已批准的供应商可以暂停');

        $this->supplierService->suspendSupplier($supplier, 'Test suspension reason');
    }

    #[Test]
    public function testActivateSupplierWithWrongStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有暂停状态的供应商可以激活');

        $this->supplierService->activateSupplier($supplier);
    }

    #[Test]
    public function testTerminateSupplierWithWrongStatus(): void
    {
        $supplier = $this->createTestSupplier();
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->expectException(SupplierException::class);
        $this->expectExceptionMessage('只有已批准或暂停的供应商可以终止合作');

        $this->supplierService->terminateSupplier($supplier, 'Test termination reason');
    }

    private function createTestSupplier(): Supplier
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setSupplierType(SupplierType::SUPPLIER);
        $supplier->setLegalAddress('Test Legal Address');
        $supplier->setRegistrationNumber('REG' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());
        $supplier->setStatus(SupplierStatus::DRAFT);

        $this->supplierRepository->save($supplier);

        return $supplier;
    }
}
