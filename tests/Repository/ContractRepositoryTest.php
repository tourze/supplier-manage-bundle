<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\ContractStatus;
use Tourze\SupplierManageBundle\Repository\ContractRepository;

/**
 * @internal
 */
#[CoversClass(ContractRepository::class)]
#[RunTestsInSeparateProcesses]
class ContractRepositoryTest extends AbstractRepositoryTestCase
{
    private ContractRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(ContractRepository::class);

        // 清理现有数据，确保测试隔离
        self::getEntityManager()->createQuery('DELETE FROM ' . Contract::class . ' c')->execute();
        self::getEntityManager()->createQuery('DELETE FROM ' . Supplier::class . ' s')->execute();

        // 创建一个 DataFixture 测试数据以满足基类测试要求
        $supplier = new Supplier();
        $supplier->setName('DataFixture Test Supplier');
        $supplier->setLegalName('DataFixture Test Legal');
        $supplier->setLegalAddress('DataFixture Test Address');
        $supplier->setRegistrationNumber('DATA-FIXTURE-' . uniqid());
        $supplier->setTaxNumber('DATA-FIXTURE-TAX-' . uniqid());

        self::getEntityManager()->persist($supplier);

        $contract = new Contract();
        $contract->setSupplier($supplier);
        $contract->setContractNumber('DATA-FIXTURE-CONTRACT-' . uniqid());
        $contract->setTitle('DataFixture Test Contract');
        $contract->setContractType('service');
        $contract->setStartDate(new \DateTimeImmutable());
        $contract->setEndDate(new \DateTimeImmutable('+1 year'));
        $contract->setAmount(100000.00);
        $contract->setStatus(ContractStatus::DRAFT);

        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        // 清除实体管理器缓存，确保测试方法能正常工作
        self::getEntityManager()->clear();
    }

    protected function createNewEntity(): Contract
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('TEST' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());

        self::getEntityManager()->persist($supplier);

        $contract = new Contract();
        $contract->setSupplier($supplier);
        $contract->setContractNumber('CONTRACT-' . uniqid());
        $contract->setTitle('测试合同');
        $contract->setContractType('service'); // 添加必需的合同类型
        $contract->setStartDate(new \DateTimeImmutable());
        $contract->setEndDate(new \DateTimeImmutable('+1 year'));
        $contract->setAmount(100000.00);

        return $contract;
    }

    /**
     * @return ServiceEntityRepository<Contract>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testRepositoryHasRequiredMethods(): void
    {
        $existingMethods = [
            'findBySupplier',
            'findActiveContracts',
            'findExpiringWithinDays',
            'findByType',
            'findByStatus',
            'search',
            'countBySupplier',
            'findByContractNumber',
            'findCurrentActiveBySupplier',
        ];

        $requiredNewMethods = [
            'findExpiringContracts',
            'findByDateRange',
            'findByAmountRange',
            'getContractStatistics',
            'countByStatus',
            'getExpiringContractsAlert',
            'searchContracts',
            'findContractsBySupplierAndStatus',
            'getContractPerformanceStats',
            'findDraftContractsOlderThan',
            'findContractsByAmountThreshold',
            'getContractCompletionRate',
            'findRenewableContracts',
            'getSupplierContractSummary',
        ];

        $allMethods = array_merge($existingMethods, $requiredNewMethods);

        foreach ($allMethods as $method) {
            $this->assertTrue(method_exists($this->repository, $method), "方法 {$method} 不存在");
        }
    }

    public function testFindBySupplier(): void
    {
        $contract1 = $this->createNewEntity();
        $contract2 = $this->createNewEntity();

        self::getEntityManager()->persist($contract1);
        self::getEntityManager()->persist($contract2);
        self::getEntityManager()->flush();

        $contracts = $this->repository->findBySupplier($contract1->getSupplier());
        $this->assertCount(1, $contracts);
        $this->assertEquals($contract1->getContractNumber(), $contracts[0]->getContractNumber());
    }

    public function testFindByContractNumber(): void
    {
        $contract = $this->createNewEntity();
        $contractNumber = 'UNIQUE-CONTRACT-' . uniqid();
        $contract->setContractNumber($contractNumber);

        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        $found = $this->repository->findByContractNumber($contractNumber);
        $this->assertNotNull($found);
        $this->assertEquals($contractNumber, $found->getContractNumber());

        $notFound = $this->repository->findByContractNumber('NONEXISTENT');
        $this->assertNull($notFound);
    }

    public function testFindByStatus(): void
    {
        $contract1 = $this->createNewEntity();
        $contract1->setStatus(ContractStatus::ACTIVE);
        $contract1->setContractNumber('STATUS-ACTIVE-' . uniqid());

        $contract2 = $this->createNewEntity();
        $contract2->setStatus(ContractStatus::DRAFT);
        $contract2->setContractNumber('STATUS-DRAFT-' . uniqid());

        self::getEntityManager()->persist($contract1);
        self::getEntityManager()->persist($contract2);
        self::getEntityManager()->flush();

        $activeContracts = $this->repository->findByStatus(ContractStatus::ACTIVE->value);
        $this->assertGreaterThanOrEqual(1, count($activeContracts));

        $foundActiveContract = false;
        foreach ($activeContracts as $contract) {
            if (ContractStatus::ACTIVE === $contract->getStatus() && str_contains($contract->getContractNumber(), 'STATUS-ACTIVE')) {
                $foundActiveContract = true;
                break;
            }
        }
        $this->assertTrue($foundActiveContract, '应该找到我们创建的活跃状态合同');

        $draftContracts = $this->repository->findByStatus(ContractStatus::DRAFT->value);
        $this->assertGreaterThanOrEqual(1, count($draftContracts));

        $foundDraftContract = false;
        foreach ($draftContracts as $contract) {
            if (ContractStatus::DRAFT === $contract->getStatus() && str_contains($contract->getContractNumber(), 'STATUS-DRAFT')) {
                $foundDraftContract = true;
                break;
            }
        }
        $this->assertTrue($foundDraftContract, '应该找到我们创建的草稿状态合同');
    }

    public function testFindActiveContracts(): void
    {
        $activeContract = $this->createNewEntity();
        $activeContract->setStatus(ContractStatus::ACTIVE);
        $activeContract->setContractNumber('ACTIVE-TEST-' . uniqid());

        $draftContract = $this->createNewEntity();
        $draftContract->setStatus(ContractStatus::DRAFT);
        $draftContract->setContractNumber('DRAFT-TEST-' . uniqid());

        $expiredActiveContract = $this->createNewEntity();
        $expiredActiveContract->setStatus(ContractStatus::ACTIVE);
        $expiredActiveContract->setEndDate(new \DateTimeImmutable('-1 day'));
        $expiredActiveContract->setContractNumber('EXPIRED-TEST-' . uniqid());

        self::getEntityManager()->persist($activeContract);
        self::getEntityManager()->persist($draftContract);
        self::getEntityManager()->persist($expiredActiveContract);
        self::getEntityManager()->flush();

        $activeContracts = $this->repository->findActiveContracts();
        $this->assertGreaterThanOrEqual(1, count($activeContracts));

        $foundActiveContract = false;
        $foundExpiredContract = false;
        foreach ($activeContracts as $contract) {
            if (str_contains($contract->getContractNumber(), 'ACTIVE-TEST-')) {
                $foundActiveContract = true;
                $this->assertEquals(ContractStatus::ACTIVE, $contract->getStatus());
            }
            if (str_contains($contract->getContractNumber(), 'EXPIRED-TEST-')) {
                $foundExpiredContract = true;
            }
        }
        $this->assertTrue($foundActiveContract, '应该找到未过期的活跃合同');
        $this->assertFalse($foundExpiredContract, '不应该找到已过期的活跃合同');
    }

    public function testFindExpiringContracts(): void
    {
        $expiringContract = $this->createNewEntity();
        $expiringContract->setStatus(ContractStatus::ACTIVE);
        $expiringContract->setEndDate(new \DateTimeImmutable('+15 days'));

        $futureContract = $this->createNewEntity();
        $futureContract->setStatus(ContractStatus::ACTIVE);
        $futureContract->setEndDate(new \DateTimeImmutable('+90 days'));

        self::getEntityManager()->persist($expiringContract);
        self::getEntityManager()->persist($futureContract);
        self::getEntityManager()->flush();

        $expiringContracts = $this->repository->findExpiringContracts(30);
        $this->assertCount(1, $expiringContracts);
    }

    public function testFindByDateRange(): void
    {
        $contract1 = $this->createNewEntity();
        $contract1->setStartDate(new \DateTimeImmutable('2024-01-01'));
        $contract1->setEndDate(new \DateTimeImmutable('2024-06-30'));
        $contract1->setContractNumber('2024-Q1-' . uniqid());

        $contract2 = $this->createNewEntity();
        $contract2->setStartDate(new \DateTimeImmutable('2025-01-01'));
        $contract2->setEndDate(new \DateTimeImmutable('2025-06-30'));
        $contract2->setContractNumber('2025-Q1-' . uniqid());

        self::getEntityManager()->persist($contract1);
        self::getEntityManager()->persist($contract2);
        self::getEntityManager()->flush();

        $contracts2024First = $this->repository->findByDateRange(
            new \DateTimeImmutable('2024-01-01'),
            new \DateTimeImmutable('2024-03-31')
        );
        $this->assertGreaterThanOrEqual(1, count($contracts2024First));

        $found2024Contract = false;
        foreach ($contracts2024First as $contract) {
            if ('2024-01-01' === $contract->getStartDate()->format('Y-m-d')) {
                $found2024Contract = true;
                break;
            }
        }
        $this->assertTrue($found2024Contract, '应该找到2024年1月1日开始的合同');

        $noOverlapContracts = $this->repository->findByDateRange(
            new \DateTimeImmutable('2023-01-01'),
            new \DateTimeImmutable('2023-12-31')
        );
        $this->assertCount(0, $noOverlapContracts);
    }

    public function testFindByAmountRange(): void
    {
        $smallContract = $this->createNewEntity();
        $smallContract->setAmount(50000.00);

        $largeContract = $this->createNewEntity();
        $largeContract->setAmount(150000.00);

        self::getEntityManager()->persist($smallContract);
        self::getEntityManager()->persist($largeContract);
        self::getEntityManager()->flush();

        $contractsInRange = $this->repository->findByAmountRange(40000.00, 60000.00);
        $this->assertCount(1, $contractsInRange);
        $this->assertEquals(50000.00, $contractsInRange[0]->getAmount());
    }

    public function testCountByStatus(): void
    {
        $activeContract = $this->createNewEntity();
        $activeContract->setStatus(ContractStatus::ACTIVE);
        $activeContract->setContractNumber('COUNT-ACTIVE-' . uniqid());

        $draftContract1 = $this->createNewEntity();
        $draftContract1->setStatus(ContractStatus::DRAFT);
        $draftContract1->setContractNumber('COUNT-DRAFT-1-' . uniqid());

        $draftContract2 = $this->createNewEntity();
        $draftContract2->setStatus(ContractStatus::DRAFT);
        $draftContract2->setContractNumber('COUNT-DRAFT-2-' . uniqid());

        self::getEntityManager()->persist($activeContract);
        self::getEntityManager()->persist($draftContract1);
        self::getEntityManager()->persist($draftContract2);
        self::getEntityManager()->flush();

        $counts = $this->repository->countByStatus();
        $this->assertArrayHasKey('active', $counts);
        $this->assertArrayHasKey('draft', $counts);
        $this->assertGreaterThanOrEqual(1, $counts['active']);
        $this->assertGreaterThanOrEqual(2, $counts['draft']);
    }

    public function testGetContractStatistics(): void
    {
        $activeContract = $this->createNewEntity();
        $activeContract->setStatus(ContractStatus::ACTIVE);
        $activeContract->setAmount(100000.00);
        $activeContract->setContractNumber('STATS-ACTIVE-' . uniqid());

        $completedContract = $this->createNewEntity();
        $completedContract->setStatus(ContractStatus::COMPLETED);
        $completedContract->setAmount(200000.00);
        $completedContract->setContractNumber('STATS-COMPLETED-' . uniqid());

        self::getEntityManager()->persist($activeContract);
        self::getEntityManager()->persist($completedContract);
        self::getEntityManager()->flush();

        $stats = $this->repository->getContractStatistics();

        $this->assertArrayHasKey('totalCount', $stats);
        $this->assertArrayHasKey('totalAmount', $stats);
        $this->assertArrayHasKey('averageAmount', $stats);
        $this->assertArrayHasKey('countByStatus', $stats);
        $this->assertGreaterThanOrEqual(2, $stats['totalCount']);
        $this->assertGreaterThanOrEqual(300000.00, $stats['totalAmount']);
        $this->assertIsArray($stats['countByStatus']);
        $this->assertGreaterThan(0, $stats['averageAmount']);
    }

    public function testSearchContracts(): void
    {
        $contract = $this->createNewEntity();
        $contract->setTitle('软件开发合同');
        $contract->setContractNumber('DEV-2024-001');

        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        $results = $this->repository->searchContracts('软件开发');
        $this->assertCount(1, $results);
        $this->assertEquals('软件开发合同', $results[0]->getTitle());

        $resultsByNumber = $this->repository->searchContracts('DEV-2024');
        $this->assertCount(1, $resultsByNumber);
    }

    public function testFindByType(): void
    {
        // 创建测试合同
        $testContracts = [
            ['type' => 'service', 'title' => '服务合同', 'prefix' => 'SERVICE'],
            ['type' => 'supply', 'title' => '供货合同', 'prefix' => 'SUPPLY'],
        ];

        foreach ($testContracts as $contractData) {
            $contract = $this->createNewEntity();
            $contract->setContractType($contractData['type']);
            $contract->setTitle($contractData['title']);
            $contract->setContractNumber($contractData['prefix'] . '-' . uniqid());
            self::getEntityManager()->persist($contract);
        }
        self::getEntityManager()->flush();

        // 验证每种类型的合同
        foreach ($testContracts as $contractData) {
            $contracts = $this->repository->findByType($contractData['type']);
            $this->assertGreaterThanOrEqual(1, count($contracts));

            $found = $this->findTestContract($contracts, $contractData['prefix']);
            $this->assertNotNull($found, "应该找到我们创建的{$contractData['title']}");
            $this->assertEquals($contractData['title'], $found->getTitle());
        }

        // 验证不存在的类型
        $leaseContracts = $this->repository->findByType('lease');
        $foundLease = $this->findTestContract($leaseContracts, 'LEASE');
        $this->assertNull($foundLease, '不应该找到租赁类型的测试合同');
    }

    /**
     * @param array<Contract> $contracts
     */
    private function findTestContract(array $contracts, string $prefix): ?Contract
    {
        foreach ($contracts as $contract) {
            if (str_contains($contract->getContractNumber(), $prefix . '-')) {
                return $contract;
            }
        }

        return null;
    }

    public function testSaveAndRemove(): void
    {
        $contract = $this->createNewEntity();

        $this->repository->save($contract, true);
        $this->assertNotNull($contract->getId());

        $found = $this->repository->find($contract->getId());
        $this->assertInstanceOf(Contract::class, $found);

        $savedId = $contract->getId();
        $this->repository->remove($contract, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testCountBySupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Test Count Supplier');
        $supplier->setLegalName('Test Count Legal Name');
        $supplier->setLegalAddress('Test Count Address');
        $supplier->setRegistrationNumber('COUNT' . uniqid());
        $supplier->setTaxNumber('COUNT-TAX' . uniqid());
        self::getEntityManager()->persist($supplier);

        $contract1 = $this->createNewEntity();
        $contract1->setSupplier($supplier);
        $contract1->setContractNumber('COUNT-1-' . uniqid());

        $contract2 = $this->createNewEntity();
        $contract2->setSupplier($supplier);
        $contract2->setContractNumber('COUNT-2-' . uniqid());

        $otherContract = $this->createNewEntity();
        $otherContract->setContractNumber('OTHER-' . uniqid());

        self::getEntityManager()->persist($contract1);
        self::getEntityManager()->persist($contract2);
        self::getEntityManager()->persist($otherContract);
        self::getEntityManager()->flush();

        $count = $this->repository->countBySupplier($supplier);
        $this->assertEquals(2, $count);
    }

    public function testFindContractsByAmountThreshold(): void
    {
        $smallContract = $this->createNewEntity();
        $smallContract->setAmount(50000.00);
        $smallContract->setContractNumber('SMALL-' . uniqid());

        $mediumContract = $this->createNewEntity();
        $mediumContract->setAmount(100000.00);
        $mediumContract->setContractNumber('MEDIUM-' . uniqid());

        $largeContract = $this->createNewEntity();
        $largeContract->setAmount(200000.00);
        $largeContract->setContractNumber('LARGE-' . uniqid());

        self::getEntityManager()->persist($smallContract);
        self::getEntityManager()->persist($mediumContract);
        self::getEntityManager()->persist($largeContract);
        self::getEntityManager()->flush();

        $contractsGTE100k = $this->repository->findContractsByAmountThreshold(100000.00, '>=');
        $this->assertGreaterThanOrEqual(2, count($contractsGTE100k));

        $foundMedium = false;
        $foundLarge = false;
        foreach ($contractsGTE100k as $contract) {
            if (str_contains($contract->getContractNumber(), 'MEDIUM-')) {
                $foundMedium = true;
            }
            if (str_contains($contract->getContractNumber(), 'LARGE-')) {
                $foundLarge = true;
            }
        }
        $this->assertTrue($foundMedium && $foundLarge, '应该找到金额大于等于10万的合同');

        $contractsLE100k = $this->repository->findContractsByAmountThreshold(100000.00, '<=');
        $this->assertGreaterThanOrEqual(1, count($contractsLE100k));

        $foundSmallOrMedium = false;
        foreach ($contractsLE100k as $contract) {
            if (str_contains($contract->getContractNumber(), 'SMALL-') || str_contains($contract->getContractNumber(), 'MEDIUM-')) {
                $foundSmallOrMedium = true;
                break;
            }
        }
        $this->assertTrue($foundSmallOrMedium, '应该找到金额小于等于10万的合同');
    }

    public function testFindContractsBySupplierAndStatus(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Supplier Status Test');
        $supplier->setLegalName('Supplier Status Legal');
        $supplier->setLegalAddress('Supplier Status Address');
        $supplier->setRegistrationNumber('STATUS' . uniqid());
        $supplier->setTaxNumber('STATUS-TAX' . uniqid());
        self::getEntityManager()->persist($supplier);

        $activeContract = $this->createNewEntity();
        $activeContract->setSupplier($supplier);
        $activeContract->setStatus(ContractStatus::ACTIVE);
        $activeContract->setContractNumber('SUPPLIER-ACTIVE-' . uniqid());

        $draftContract = $this->createNewEntity();
        $draftContract->setSupplier($supplier);
        $draftContract->setStatus(ContractStatus::DRAFT);
        $draftContract->setContractNumber('SUPPLIER-DRAFT-' . uniqid());

        $otherSupplierContract = $this->createNewEntity();
        $otherSupplierContract->setStatus(ContractStatus::ACTIVE);
        $otherSupplierContract->setContractNumber('OTHER-SUPPLIER-' . uniqid());

        self::getEntityManager()->persist($activeContract);
        self::getEntityManager()->persist($draftContract);
        self::getEntityManager()->persist($otherSupplierContract);
        self::getEntityManager()->flush();

        $activeContractsForSupplier = $this->repository->findContractsBySupplierAndStatus($supplier, ContractStatus::ACTIVE->value);
        $this->assertCount(1, $activeContractsForSupplier);
        $this->assertStringContainsString('SUPPLIER-ACTIVE-', $activeContractsForSupplier[0]->getContractNumber());

        $draftContractsForSupplier = $this->repository->findContractsBySupplierAndStatus($supplier, ContractStatus::DRAFT->value);
        $this->assertCount(1, $draftContractsForSupplier);
        $this->assertStringContainsString('SUPPLIER-DRAFT-', $draftContractsForSupplier[0]->getContractNumber());
    }

    public function testFindCurrentActiveBySupplier(): void
    {
        $supplier = new Supplier();
        $supplier->setName('Current Active Test Supplier');
        $supplier->setLegalName('Current Active Legal');
        $supplier->setLegalAddress('Current Active Address');
        $supplier->setRegistrationNumber('CURRENT' . uniqid());
        $supplier->setTaxNumber('CURRENT-TAX' . uniqid());
        self::getEntityManager()->persist($supplier);

        $now = new \DateTimeImmutable();

        $currentActiveContract = $this->createNewEntity();
        $currentActiveContract->setSupplier($supplier);
        $currentActiveContract->setStatus(ContractStatus::ACTIVE);
        $currentActiveContract->setStartDate($now->modify('-1 month'));
        $currentActiveContract->setEndDate($now->modify('+1 month'));
        $currentActiveContract->setContractNumber('CURRENT-ACTIVE-' . uniqid());

        $futureContract = $this->createNewEntity();
        $futureContract->setSupplier($supplier);
        $futureContract->setStatus(ContractStatus::ACTIVE);
        $futureContract->setStartDate($now->modify('+1 month'));
        $futureContract->setEndDate($now->modify('+2 months'));
        $futureContract->setContractNumber('FUTURE-' . uniqid());

        $expiredContract = $this->createNewEntity();
        $expiredContract->setSupplier($supplier);
        $expiredContract->setStatus(ContractStatus::ACTIVE);
        $expiredContract->setStartDate($now->modify('-2 months'));
        $expiredContract->setEndDate($now->modify('-1 day'));
        $expiredContract->setContractNumber('EXPIRED-' . uniqid());

        self::getEntityManager()->persist($currentActiveContract);
        self::getEntityManager()->persist($futureContract);
        self::getEntityManager()->persist($expiredContract);
        self::getEntityManager()->flush();

        $currentActive = $this->repository->findCurrentActiveBySupplier($supplier);
        $this->assertNotNull($currentActive);
        $this->assertStringContainsString('CURRENT-ACTIVE-', $currentActive->getContractNumber());
        $this->assertEquals(ContractStatus::ACTIVE, $currentActive->getStatus());
    }

    public function testFindDraftContractsOlderThan(): void
    {
        $oldDraft = $this->createNewEntity();
        $oldDraft->setStatus(ContractStatus::DRAFT);
        $oldDraft->setContractNumber('OLD-DRAFT-' . uniqid());

        $newDraft = $this->createNewEntity();
        $newDraft->setStatus(ContractStatus::DRAFT);
        $newDraft->setContractNumber('NEW-DRAFT-' . uniqid());

        $activeContract = $this->createNewEntity();
        $activeContract->setStatus(ContractStatus::ACTIVE);
        $activeContract->setContractNumber('ACTIVE-OLD-' . uniqid());

        self::getEntityManager()->persist($oldDraft);
        self::getEntityManager()->persist($newDraft);
        self::getEntityManager()->persist($activeContract);
        self::getEntityManager()->flush();

        // 使用SQL直接更新create_time来模拟老合同
        self::getEntityManager()->getConnection()->executeStatement(
            'UPDATE supplier_contract SET create_time = ? WHERE id = ?',
            [
                (new \DateTimeImmutable('-40 days'))->format('Y-m-d H:i:s'),
                $oldDraft->getId(),
            ]
        );

        $oldDrafts = $this->repository->findDraftContractsOlderThan(30);
        $this->assertGreaterThanOrEqual(1, count($oldDrafts));

        $foundOldDraft = false;
        $foundNewDraft = false;
        foreach ($oldDrafts as $contract) {
            if (str_contains($contract->getContractNumber(), 'OLD-DRAFT-')) {
                $foundOldDraft = true;
            }
            if (str_contains($contract->getContractNumber(), 'NEW-DRAFT-')) {
                $foundNewDraft = true;
            }
        }
        $this->assertTrue($foundOldDraft, '应该找到超过30天的旧草稿合同');
        $this->assertFalse($foundNewDraft, '不应该找到新创建的草稿合同');
    }

    public function testFindExpiringWithinDays(): void
    {
        $soonExpiringContract = $this->createNewEntity();
        $soonExpiringContract->setStatus(ContractStatus::ACTIVE);
        $soonExpiringContract->setEndDate(new \DateTimeImmutable('+15 days'));
        $soonExpiringContract->setContractNumber('SOON-EXPIRING-' . uniqid());

        $laterExpiringContract = $this->createNewEntity();
        $laterExpiringContract->setStatus(ContractStatus::ACTIVE);
        $laterExpiringContract->setEndDate(new \DateTimeImmutable('+45 days'));
        $laterExpiringContract->setContractNumber('LATER-EXPIRING-' . uniqid());

        $expiredContract = $this->createNewEntity();
        $expiredContract->setStatus(ContractStatus::ACTIVE);
        $expiredContract->setEndDate(new \DateTimeImmutable('-1 day'));
        $expiredContract->setContractNumber('ALREADY-EXPIRED-' . uniqid());

        self::getEntityManager()->persist($soonExpiringContract);
        self::getEntityManager()->persist($laterExpiringContract);
        self::getEntityManager()->persist($expiredContract);
        self::getEntityManager()->flush();

        $expiringIn30Days = $this->repository->findExpiringWithinDays(30);
        $this->assertGreaterThanOrEqual(1, count($expiringIn30Days));

        $foundSoonExpiring = false;
        $foundLaterExpiring = false;
        $foundExpired = false;
        foreach ($expiringIn30Days as $contract) {
            if (str_contains($contract->getContractNumber(), 'SOON-EXPIRING-')) {
                $foundSoonExpiring = true;
            }
            if (str_contains($contract->getContractNumber(), 'LATER-EXPIRING-')) {
                $foundLaterExpiring = true;
            }
            if (str_contains($contract->getContractNumber(), 'ALREADY-EXPIRED-')) {
                $foundExpired = true;
            }
        }
        $this->assertTrue($foundSoonExpiring, '应该找到15天后到期的合同');
        $this->assertFalse($foundLaterExpiring, '不应该找到45天后到期的合同');
        $this->assertFalse($foundExpired, '不应该找到已过期的合同');
    }

    public function testFindRenewableContracts(): void
    {
        $renewableContract = $this->createNewEntity();
        $renewableContract->setStatus(ContractStatus::ACTIVE);
        $renewableContract->setEndDate(new \DateTimeImmutable('+45 days'));
        $renewableContract->setContractNumber('RENEWABLE-' . uniqid());

        $tooEarlyContract = $this->createNewEntity();
        $tooEarlyContract->setStatus(ContractStatus::ACTIVE);
        $tooEarlyContract->setEndDate(new \DateTimeImmutable('+90 days'));
        $tooEarlyContract->setContractNumber('TOO-EARLY-' . uniqid());

        $draftContract = $this->createNewEntity();
        $draftContract->setStatus(ContractStatus::DRAFT);
        $draftContract->setEndDate(new \DateTimeImmutable('+30 days'));
        $draftContract->setContractNumber('DRAFT-NOT-RENEWABLE-' . uniqid());

        self::getEntityManager()->persist($renewableContract);
        self::getEntityManager()->persist($tooEarlyContract);
        self::getEntityManager()->persist($draftContract);
        self::getEntityManager()->flush();

        $renewableContracts = $this->repository->findRenewableContracts(60);
        $this->assertGreaterThanOrEqual(1, count($renewableContracts));

        $foundRenewable = false;
        $foundTooEarly = false;
        $foundDraft = false;
        foreach ($renewableContracts as $contract) {
            if (str_contains($contract->getContractNumber(), 'RENEWABLE-')) {
                $foundRenewable = true;
            }
            if (str_contains($contract->getContractNumber(), 'TOO-EARLY-')) {
                $foundTooEarly = true;
            }
            if (str_contains($contract->getContractNumber(), 'DRAFT-NOT-RENEWABLE-')) {
                $foundDraft = true;
            }
        }
        $this->assertTrue($foundRenewable, '应该找到可续约的合同');
        $this->assertFalse($foundTooEarly, '不应该找到太早续约的合同');
        $this->assertFalse($foundDraft, '不应该找到草稿状态的合同');
    }

    public function testRemove(): void
    {
        $contract = $this->createNewEntity();
        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        $contractId = $contract->getId();
        $this->assertNotNull($contractId);

        $foundBefore = $this->repository->find($contractId);
        $this->assertNotNull($foundBefore);

        $this->repository->remove($contract, true);

        $foundAfter = $this->repository->find($contractId);
        $this->assertNull($foundAfter);
    }
}
