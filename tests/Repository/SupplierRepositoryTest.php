<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;
use Tourze\SupplierManageBundle\Enum\ContractStatus;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Repository\SupplierRepository;

/**
 * @internal
 */
#[CoversClass(SupplierRepository::class)]
#[RunTestsInSeparateProcesses]
class SupplierRepositoryTest extends AbstractRepositoryTestCase
{
    private SupplierRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SupplierRepository::class);
    }

    protected function createNewEntity(): Supplier
    {
        $supplier = new Supplier();
        $supplier->setName('Test Supplier');
        $supplier->setLegalName('Test Legal Name');
        $supplier->setLegalAddress('Test Address');
        $supplier->setRegistrationNumber('TEST' . uniqid());
        $supplier->setTaxNumber('TAX' . uniqid());

        return $supplier;
    }

    /**
     * @return ServiceEntityRepository<Supplier>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testRepositoryHasRequiredMethods(): void
    {
        $methods = [
            'findByStatus',
            'findBySupplierType',
            'findActiveSuppliers',
            'countByStatus',
            'search',
        ];

        /** @var string $method */
        foreach ($methods as $method) {
            $this->assertTrue(method_exists($this->repository, $method));
        }
    }

    public function testFindByStatus(): void
    {
        // 创建测试数据
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Approved Supplier ' . uniqid());
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Draft Supplier ' . uniqid());
        $supplier2->setStatus(SupplierStatus::DRAFT);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试查找
        $approved = $this->repository->findByStatus(SupplierStatus::APPROVED->value);

        // 过滤出本次测试创建的数据
        $testApproved = array_filter($approved, fn ($s) => str_starts_with($s->getName(), 'Approved Supplier'));

        $this->assertCount(1, $testApproved);
        $this->assertStringStartsWith('Approved Supplier', reset($testApproved)->getName());
    }

    public function testFindBySupplierType(): void
    {
        // 创建测试数据，使用唯一标识
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Type Supplier ' . $uniqueId);
        $supplier1->setSupplierType(SupplierType::SUPPLIER);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Type Merchant ' . $uniqueId);
        $supplier2->setSupplierType(SupplierType::MERCHANT);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试查找
        $suppliers = $this->repository->findBySupplierType(SupplierType::SUPPLIER->value);

        // 过滤出本次测试创建的数据
        $testSuppliers = array_filter($suppliers, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testSuppliers);
        $this->assertStringStartsWith('Type Supplier', reset($testSuppliers)->getName());
    }

    public function testFindActiveSuppliers(): void
    {
        // 创建测试数据，使用唯一标识
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Active Supplier ' . $uniqueId);
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Suspended Supplier ' . $uniqueId);
        $supplier2->setStatus(SupplierStatus::SUSPENDED);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试查找
        $active = $this->repository->findActiveSuppliers();

        // 过滤出本次测试创建的数据
        $testActive = array_filter($active, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testActive);
        $this->assertStringStartsWith('Active Supplier', reset($testActive)->getName());
    }

    public function testCountByStatus(): void
    {
        // 先获取现有的统计
        $initialCounts = $this->repository->countByStatus();
        $initialApproved = $initialCounts[SupplierStatus::APPROVED->value] ?? 0;
        $initialDraft = $initialCounts[SupplierStatus::DRAFT->value] ?? 0;

        // 创建测试数据
        $supplier1 = $this->createNewEntity();
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = $this->createNewEntity();
        $supplier2->setStatus(SupplierStatus::APPROVED);

        $supplier3 = $this->createNewEntity();
        $supplier3->setStatus(SupplierStatus::DRAFT);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->persist($supplier3);
        self::getEntityManager()->flush();

        // 测试统计
        $counts = $this->repository->countByStatus();
        $this->assertEquals($initialApproved + 2, $counts[SupplierStatus::APPROVED->value] ?? 0);
        $this->assertEquals($initialDraft + 1, $counts[SupplierStatus::DRAFT->value] ?? 0);
    }

    public function testSearch(): void
    {
        // 创建测试数据
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('ABC Supplier');
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('XYZ Company');
        $supplier2->setStatus(SupplierStatus::DRAFT);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试搜索
        $results = $this->repository->search('ABC');
        $this->assertCount(1, $results);
        $this->assertEquals('ABC Supplier', $results[0]->getName());

        // 测试带状态过滤的搜索
        $results = $this->repository->search('Supplier', SupplierStatus::APPROVED->value);
        $this->assertCount(1, $results);
        $this->assertEquals('ABC Supplier', $results[0]->getName());
    }

    public function testSaveAndRemove(): void
    {
        // 创建测试实体
        $supplier = $this->createNewEntity();

        // 测试保存
        $this->repository->save($supplier, true);
        $this->assertNotNull($supplier->getId());

        // 验证保存成功
        $found = $this->repository->find($supplier->getId());
        $this->assertInstanceOf(Supplier::class, $found);
        $this->assertEquals('Test Supplier', $found->getName());

        // 测试删除
        $savedId = $supplier->getId();
        $this->repository->remove($supplier, true);
        $removed = $this->repository->find($savedId);
        $this->assertNull($removed);
    }

    public function testFindByMultipleFilters(): void
    {
        // 创建测试数据，使用唯一标识
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Test Supplier 1 ' . $uniqueId);
        $supplier1->setSupplierType(SupplierType::SUPPLIER);
        $supplier1->setStatus(SupplierStatus::APPROVED);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Test Supplier 2 ' . $uniqueId);
        $supplier2->setSupplierType(SupplierType::MERCHANT);
        $supplier2->setStatus(SupplierStatus::PENDING_REVIEW);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试多条件筛选
        $filters = [
            'status' => SupplierStatus::APPROVED->value,
            'supplierType' => SupplierType::SUPPLIER->value,
        ];

        $results = $this->repository->findByMultipleFilters($filters);

        // 过滤出本次测试创建的数据
        $testResults = array_filter($results, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testResults);
        $this->assertEquals('Test Supplier 1 ' . $uniqueId, reset($testResults)->getName());
    }

    public function testFindWithPagination(): void
    {
        // 先获取现有的总数
        $initialTotal = $this->repository->count([]);

        // 创建 5 个测试供应商
        for ($i = 1; $i <= 5; ++$i) {
            $supplier = $this->createNewEntity();
            $supplier->setName("Test Supplier {$i}");
            $supplier->setStatus(SupplierStatus::APPROVED);
            self::getEntityManager()->persist($supplier);
        }
        self::getEntityManager()->flush();

        // 测试分页查询
        $page = 1;
        $limit = 2;
        $result = $this->repository->findWithPagination($page, $limit);

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('limit', $result);
        $this->assertArrayHasKey('totalPages', $result);

        $this->assertCount(2, $result['data']);
        $this->assertEquals($initialTotal + 5, $result['total']);
        $this->assertEquals(1, $result['page']);
        $this->assertEquals(2, $result['limit']);
        $this->assertEquals(ceil(($initialTotal + 5) / $limit), $result['totalPages']);
    }

    public function testFindWithPaginationAndFilters(): void
    {
        // 先获取现有的SUPPLIER类型数量
        $filters = ['supplierType' => SupplierType::SUPPLIER->value];
        $initialCount = $this->repository->count($filters);

        // 创建不同类型的供应商，使用唯一标识
        $uniqueId = uniqid();
        for ($i = 1; $i <= 3; ++$i) {
            $supplier = $this->createNewEntity();
            $supplier->setName("Manufacturer {$i} {$uniqueId}");
            $supplier->setSupplierType(SupplierType::SUPPLIER);
            $supplier->setStatus(SupplierStatus::APPROVED);
            self::getEntityManager()->persist($supplier);
        }

        for ($i = 1; $i <= 2; ++$i) {
            $supplier = $this->createNewEntity();
            $supplier->setName("Distributor {$i} {$uniqueId}");
            $supplier->setSupplierType(SupplierType::MERCHANT);
            $supplier->setStatus(SupplierStatus::APPROVED);
            self::getEntityManager()->persist($supplier);
        }
        self::getEntityManager()->flush();

        // 测试带筛选条件的分页查询
        $result = $this->repository->findWithPagination(1, 2, $filters);

        $this->assertCount(2, $result['data']);
        $this->assertEquals($initialCount + 3, $result['total']);
        $this->assertEquals(ceil(($initialCount + 3) / 2), $result['totalPages']);
    }

    public function testCountBySupplierType(): void
    {
        // 先获取现有的统计
        $initialCounts = $this->repository->countBySupplierType();
        $initialSupplier = $initialCounts[SupplierType::SUPPLIER->value] ?? 0;
        $initialMerchant = $initialCounts[SupplierType::MERCHANT->value] ?? 0;

        // 创建测试数据
        $manufacturer1 = $this->createNewEntity();
        $manufacturer1->setSupplierType(SupplierType::SUPPLIER);
        $manufacturer1->setStatus(SupplierStatus::APPROVED);

        $manufacturer2 = $this->createNewEntity();
        $manufacturer2->setSupplierType(SupplierType::SUPPLIER);
        $manufacturer2->setStatus(SupplierStatus::PENDING_REVIEW);

        $distributor = $this->createNewEntity();
        $distributor->setSupplierType(SupplierType::MERCHANT);
        $distributor->setStatus(SupplierStatus::APPROVED);

        self::getEntityManager()->persist($manufacturer1);
        self::getEntityManager()->persist($manufacturer2);
        self::getEntityManager()->persist($distributor);
        self::getEntityManager()->flush();

        // 测试按供应商类型统计
        $counts = $this->repository->countBySupplierType();

        $this->assertArrayHasKey(SupplierType::SUPPLIER->value, $counts);
        $this->assertArrayHasKey(SupplierType::MERCHANT->value, $counts);
        $this->assertEquals($initialSupplier + 2, $counts[SupplierType::SUPPLIER->value]);
        $this->assertEquals($initialMerchant + 1, $counts[SupplierType::MERCHANT->value]);
    }

    public function testFindByNamePattern(): void
    {
        // 创建测试数据
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('ABC Corporation');

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('XYZ Limited');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        // 测试部分名称搜索
        $results = $this->repository->findByNamePattern('ABC');
        $this->assertCount(1, $results);
        $this->assertEquals('ABC Corporation', $results[0]->getName());

        // 测试不区分大小写搜索
        $results = $this->repository->findByNamePattern('abc');
        $this->assertCount(1, $results);
        $this->assertEquals('ABC Corporation', $results[0]->getName());
    }

    public function testFindOneByRegistrationNumber(): void
    {
        $supplier = $this->createNewEntity();
        $supplier->setName('Test Supplier');
        $supplier->setRegistrationNumber('UNIQUE001');

        self::getEntityManager()->persist($supplier);
        self::getEntityManager()->flush();

        $result = $this->repository->findOneByRegistrationNumber('UNIQUE001');

        $this->assertNotNull($result);
        $this->assertEquals('Test Supplier', $result->getName());
        $this->assertEquals('UNIQUE001', $result->getRegistrationNumber());
    }

    public function testFindRecentlyCreated(): void
    {
        // 创建最近的供应商
        $recentSupplier = $this->createNewEntity();
        $recentSupplier->setName('Recent Supplier');

        self::getEntityManager()->persist($recentSupplier);
        self::getEntityManager()->flush();

        // 测试查找最近创建的供应商
        $results = $this->repository->findRecentlyCreated(7); // 7天内

        $this->assertGreaterThan(0, count($results));
        $foundRecent = false;
        /** @var \Tourze\SupplierManageBundle\Entity\Supplier $result */
        foreach ($results as $result) {
            if ('Recent Supplier' === $result->getName()) {
                $foundRecent = true;
                break;
            }
        }
        $this->assertTrue($foundRecent);
    }

    public function testGetStatisticsSummary(): void
    {
        // 创建多样化的测试数据，使用唯一标识
        $uniqueId = uniqid();
        $manufacturerApproved = $this->createNewEntity();
        $manufacturerApproved->setName('Manufacturer Approved ' . $uniqueId);
        $manufacturerApproved->setSupplierType(SupplierType::SUPPLIER);
        $manufacturerApproved->setStatus(SupplierStatus::APPROVED);

        $distributorPending = $this->createNewEntity();
        $distributorPending->setName('Distributor Pending ' . $uniqueId);
        $distributorPending->setSupplierType(SupplierType::MERCHANT);
        $distributorPending->setStatus(SupplierStatus::PENDING_REVIEW);

        self::getEntityManager()->persist($manufacturerApproved);
        self::getEntityManager()->persist($distributorPending);
        self::getEntityManager()->flush();

        $statistics = $this->repository->getStatisticsSummary();

        $this->assertArrayHasKey('totalCount', $statistics);
        $this->assertArrayHasKey('countByStatus', $statistics);
        $this->assertArrayHasKey('countBySupplierType', $statistics);
        $this->assertArrayHasKey('recentlyCreated', $statistics);

        // 验证统计数据包含我们的测试数据
        $this->assertGreaterThan(0, $statistics['totalCount']);
        $this->assertArrayHasKey(SupplierStatus::APPROVED->value, $statistics['countByStatus']);
        $this->assertArrayHasKey(SupplierStatus::PENDING_REVIEW->value, $statistics['countByStatus']);
        $this->assertArrayHasKey(SupplierType::SUPPLIER->value, $statistics['countBySupplierType']);
        $this->assertArrayHasKey(SupplierType::MERCHANT->value, $statistics['countBySupplierType']);
    }

    public function testFindByIndustry(): void
    {
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Tech Supplier ' . $uniqueId);
        $supplier1->setIndustry('technology');

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Manufacturing Supplier ' . $uniqueId);
        $supplier2->setIndustry('manufacturing');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $techSuppliers = $this->repository->findByIndustry('technology');
        $testTechSuppliers = array_filter($techSuppliers, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testTechSuppliers);
        $this->assertEquals('technology', reset($testTechSuppliers)->getIndustry());
    }

    public function testFindByRegion(): void
    {
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Beijing Supplier ' . $uniqueId);
        $supplier1->setLegalAddress('北京市朝阳区XX路XX号');

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Shanghai Supplier ' . $uniqueId);
        $supplier2->setLegalAddress('上海市浦东新区XX路XX号');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $beijingSuppliers = $this->repository->findByRegion('北京');
        $testBeijingSuppliers = array_filter($beijingSuppliers, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testBeijingSuppliers);
        $this->assertStringContainsString('北京', reset($testBeijingSuppliers)->getLegalAddress());
    }

    public function testFindByCooperationModel(): void
    {
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Distribution Partner ' . $uniqueId);
        $supplier1->setCooperationModel(CooperationModel::DISTRIBUTION);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Consignment Supplier ' . $uniqueId);
        $supplier2->setCooperationModel(CooperationModel::CONSIGNMENT);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $distributionPartners = $this->repository->findByCooperationModel(CooperationModel::DISTRIBUTION->value);
        $testDistributionPartners = array_filter($distributionPartners, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testDistributionPartners);
        $this->assertEquals(CooperationModel::DISTRIBUTION, reset($testDistributionPartners)->getCooperationModel());
    }

    public function testFindByTaxNumber(): void
    {
        $uniqueTaxNumber = 'TAX-' . uniqid();
        $supplier = $this->createNewEntity();
        $supplier->setName('Tax Test Supplier');
        $supplier->setTaxNumber($uniqueTaxNumber);

        self::getEntityManager()->persist($supplier);
        self::getEntityManager()->flush();

        $foundSupplier = $this->repository->findByTaxNumber($uniqueTaxNumber);
        $this->assertNotNull($foundSupplier);
        $this->assertEquals($uniqueTaxNumber, $foundSupplier->getTaxNumber());
        $this->assertEquals('Tax Test Supplier', $foundSupplier->getName());

        $notFoundSupplier = $this->repository->findByTaxNumber('NON-EXISTENT-TAX');
        $this->assertNull($notFoundSupplier);
    }

    public function testCountByIndustry(): void
    {
        $initialCounts = $this->repository->countByIndustry();
        $initialTechCount = $initialCounts['technology'] ?? 0;
        $initialMfgCount = $initialCounts['manufacturing'] ?? 0;

        $supplier1 = $this->createNewEntity();
        $supplier1->setIndustry('technology');

        $supplier2 = $this->createNewEntity();
        $supplier2->setIndustry('technology');

        $supplier3 = $this->createNewEntity();
        $supplier3->setIndustry('manufacturing');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->persist($supplier3);
        self::getEntityManager()->flush();

        $counts = $this->repository->countByIndustry();
        $this->assertEquals($initialTechCount + 2, $counts['technology']);
        $this->assertEquals($initialMfgCount + 1, $counts['manufacturing']);
    }

    public function testCountByRegion(): void
    {
        $initialCounts = $this->repository->countByRegion();

        $supplier1 = $this->createNewEntity();
        $supplier1->setLegalAddress('北京市朝阳区测试地址1');

        $supplier2 = $this->createNewEntity();
        $supplier2->setLegalAddress('北京市海淀区测试地址2');

        $supplier3 = $this->createNewEntity();
        $supplier3->setLegalAddress('上海市浦东区测试地址3');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->persist($supplier3);
        self::getEntityManager()->flush();

        $counts = $this->repository->countByRegion();
        $this->assertIsArray($counts);

        $beijingFound = false;
        $shanghaiFound = false;
        /** @var string $region */
        /** @var int $count */
        foreach ($counts as $region => $count) {
            if (str_contains($region, '北京')) {
                $beijingFound = true;
            }
            if (str_contains($region, '上海')) {
                $shanghaiFound = true;
            }
        }

        $this->assertTrue($beijingFound, '应该找到包含北京的地区');
        $this->assertTrue($shanghaiFound, '应该找到包含上海的地区');
    }

    public function testSearchWithAdvancedFilters(): void
    {
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Advanced Search Supplier ' . $uniqueId);
        $supplier1->setStatus(SupplierStatus::APPROVED);
        $supplier1->setSupplierType(SupplierType::SUPPLIER);
        $supplier1->setIndustry('technology');
        $supplier1->setLegalAddress('北京市朝阳区测试地址');

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Another Supplier ' . $uniqueId);
        $supplier2->setStatus(SupplierStatus::DRAFT);
        $supplier2->setSupplierType(SupplierType::MERCHANT);
        $supplier2->setIndustry('manufacturing');
        $supplier2->setLegalAddress('上海市浦东区测试地址');

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $filters = [
            'keyword' => 'Advanced Search',
            'status' => SupplierStatus::APPROVED->value,
            'supplierType' => SupplierType::SUPPLIER->value,
            'industry' => 'technology',
            'region' => '北京',
        ];

        $results = $this->repository->searchWithAdvancedFilters($filters);
        $testResults = array_filter($results, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testResults);
        $this->assertEquals('Advanced Search Supplier ' . $uniqueId, reset($testResults)->getName());
    }

    public function testGetAverageRegistrationAgeInDays(): void
    {
        $supplier1 = $this->createNewEntity();
        $supplier2 = $this->createNewEntity();

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $averageAge = $this->repository->getAverageRegistrationAgeInDays();
        $this->assertIsFloat($averageAge);
        $this->assertGreaterThanOrEqual(0, $averageAge);
    }

    public function testRemove(): void
    {
        $supplier = $this->createNewEntity();
        self::getEntityManager()->persist($supplier);
        self::getEntityManager()->flush();

        $supplierId = $supplier->getId();
        $this->assertNotNull($supplierId);

        $foundBefore = $this->repository->find($supplierId);
        $this->assertNotNull($foundBefore);
        $this->assertEquals('Test Supplier', $foundBefore->getName());

        $this->repository->remove($supplier, true);

        $foundAfter = $this->repository->find($supplierId);
        $this->assertNull($foundAfter);
    }

    public function testFindSuppliersByContactInfo(): void
    {
        $uniqueId = uniqid();
        $supplier1 = $this->createNewEntity();
        $supplier1->setName('Contact Test Supplier 1 ' . $uniqueId);

        $supplier2 = $this->createNewEntity();
        $supplier2->setName('Contact Test Supplier 2 ' . $uniqueId);

        self::getEntityManager()->persist($supplier1);
        self::getEntityManager()->persist($supplier2);
        self::getEntityManager()->flush();

        $contact1 = new SupplierContact();
        $contact1->setSupplier($supplier1);
        $contact1->setName('张三');
        $contact1->setEmail('zhangsan@test.com');
        $contact1->setPhone('13800138001');
        $contact1->setIsPrimary(true);

        $contact2 = new SupplierContact();
        $contact2->setSupplier($supplier2);
        $contact2->setName('李四');
        $contact2->setEmail('lisi@another.com');
        $contact2->setPhone('13900139002');
        $contact2->setIsPrimary(true);

        self::getEntityManager()->persist($contact1);
        self::getEntityManager()->persist($contact2);
        self::getEntityManager()->flush();

        // 测试按邮箱查找
        $resultsByEmail = $this->repository->findSuppliersByContactInfo(['email' => 'zhangsan@test.com']);
        $testResults = array_filter($resultsByEmail, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testResults);
        $this->assertEquals('Contact Test Supplier 1 ' . $uniqueId, reset($testResults)->getName());

        // 测试按电话查找
        $resultsByPhone = $this->repository->findSuppliersByContactInfo(['phone' => '13900139002']);
        $testResults = array_filter($resultsByPhone, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testResults);
        $this->assertEquals('Contact Test Supplier 2 ' . $uniqueId, reset($testResults)->getName());

        // 测试按联系人姓名查找
        $resultsByName = $this->repository->findSuppliersByContactInfo(['name' => '张三']);
        $testResults = array_filter($resultsByName, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testResults);
        $this->assertEquals('Contact Test Supplier 1 ' . $uniqueId, reset($testResults)->getName());
    }

    public function testFindSuppliersNeedingAttention(): void
    {
        $uniqueId = uniqid();

        // 创建一个被暂停的供应商
        $suspendedSupplier = $this->createNewEntity();
        $suspendedSupplier->setName('Suspended Supplier ' . $uniqueId);
        $suspendedSupplier->setStatus(SupplierStatus::SUSPENDED);

        // 创建一个被终止的供应商
        $terminatedSupplier = $this->createNewEntity();
        $terminatedSupplier->setName('Terminated Supplier ' . $uniqueId);
        $terminatedSupplier->setStatus(SupplierStatus::TERMINATED);

        // 创建一个低评分的供应商
        $lowScoreSupplier = $this->createNewEntity();
        $lowScoreSupplier->setName('Low Score Supplier ' . $uniqueId);
        $lowScoreSupplier->setStatus(SupplierStatus::APPROVED);

        self::getEntityManager()->persist($suspendedSupplier);
        self::getEntityManager()->persist($terminatedSupplier);
        self::getEntityManager()->persist($lowScoreSupplier);
        self::getEntityManager()->flush();

        // 创建低评分绩效评估
        $lowEvaluation = new PerformanceEvaluation();
        $lowEvaluation->setSupplier($lowScoreSupplier);
        $lowEvaluation->setEvaluationNumber('EVAL-LOW-' . $uniqueId);
        $lowEvaluation->setTitle('低评分评估');
        $lowEvaluation->setEvaluationPeriod('2024Q1');
        $lowEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $lowEvaluation->setEvaluator('测试评估员');
        $lowEvaluation->setOverallScore(60.0);
        $lowEvaluation->setGrade(PerformanceGrade::D);
        $lowEvaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        self::getEntityManager()->persist($lowEvaluation);
        self::getEntityManager()->flush();

        $attentionSuppliers = $this->repository->findSuppliersNeedingAttention(70.0);
        $testSuppliers = array_filter($attentionSuppliers, fn ($s) => str_contains($s->getName(), $uniqueId));

        // 应该找到至少2个供应商（被暂停的、被终止的，可能还有低评分的）
        $this->assertGreaterThanOrEqual(2, count($testSuppliers));

        $supplierNames = array_map(fn ($s) => $s->getName(), $testSuppliers);
        $this->assertContains('Suspended Supplier ' . $uniqueId, $supplierNames);
        $this->assertContains('Terminated Supplier ' . $uniqueId, $supplierNames);
    }

    public function testFindSuppliersWithContracts(): void
    {
        $uniqueId = uniqid();

        // 创建有合同的供应商
        $supplierWithContract = $this->createNewEntity();
        $supplierWithContract->setName('Contract Supplier ' . $uniqueId);

        // 创建没有合同的供应商
        $supplierWithoutContract = $this->createNewEntity();
        $supplierWithoutContract->setName('No Contract Supplier ' . $uniqueId);

        self::getEntityManager()->persist($supplierWithContract);
        self::getEntityManager()->persist($supplierWithoutContract);
        self::getEntityManager()->flush();

        // 创建合同
        $contract = new Contract();
        $contract->setSupplier($supplierWithContract);
        $contract->setContractNumber('CONTRACT-' . $uniqueId);
        $contract->setTitle('测试合同');
        $contract->setContractType('supply');
        $contract->setStartDate(new \DateTimeImmutable());
        $contract->setEndDate(new \DateTimeImmutable('+1 year'));
        $contract->setAmount(100000.00);
        $contract->setCurrency('CNY');
        $contract->setStatus(ContractStatus::ACTIVE);

        self::getEntityManager()->persist($contract);
        self::getEntityManager()->flush();

        $suppliersWithContracts = $this->repository->findSuppliersWithContracts();
        $testSuppliers = array_filter($suppliersWithContracts, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testSuppliers);
        $this->assertEquals('Contract Supplier ' . $uniqueId, reset($testSuppliers)->getName());
    }

    public function testFindSuppliersWithPerformanceEvaluations(): void
    {
        $uniqueId = uniqid();

        // 创建有绩效评估的供应商
        $supplierWithEvaluation = $this->createNewEntity();
        $supplierWithEvaluation->setName('Evaluated Supplier ' . $uniqueId);

        // 创建没有绩效评估的供应商
        $supplierWithoutEvaluation = $this->createNewEntity();
        $supplierWithoutEvaluation->setName('Not Evaluated Supplier ' . $uniqueId);

        self::getEntityManager()->persist($supplierWithEvaluation);
        self::getEntityManager()->persist($supplierWithoutEvaluation);
        self::getEntityManager()->flush();

        // 创建绩效评估
        $evaluation = new PerformanceEvaluation();
        $evaluation->setSupplier($supplierWithEvaluation);
        $evaluation->setEvaluationNumber('EVAL-' . $uniqueId);
        $evaluation->setTitle('季度评估');
        $evaluation->setEvaluationPeriod('2024Q1');
        $evaluation->setEvaluationDate(new \DateTimeImmutable());
        $evaluation->setEvaluator('测试评估员');
        $evaluation->setOverallScore(85.0);
        $evaluation->setGrade(PerformanceGrade::B);
        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        self::getEntityManager()->persist($evaluation);
        self::getEntityManager()->flush();

        $suppliersWithEvaluations = $this->repository->findSuppliersWithPerformanceEvaluations();
        $testSuppliers = array_filter($suppliersWithEvaluations, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testSuppliers);
        $this->assertEquals('Evaluated Supplier ' . $uniqueId, reset($testSuppliers)->getName());
    }

    public function testFindSuppliersWithQualifications(): void
    {
        $uniqueId = uniqid();

        // 创建有资质的供应商
        $supplierWithQualification = $this->createNewEntity();
        $supplierWithQualification->setName('Qualified Supplier ' . $uniqueId);

        // 创建没有资质的供应商
        $supplierWithoutQualification = $this->createNewEntity();
        $supplierWithoutQualification->setName('Unqualified Supplier ' . $uniqueId);

        self::getEntityManager()->persist($supplierWithQualification);
        self::getEntityManager()->persist($supplierWithoutQualification);
        self::getEntityManager()->flush();

        // 创建资质证书
        $qualification = new SupplierQualification();
        $qualification->setSupplier($supplierWithQualification);
        $qualification->setName('ISO 9001质量管理体系认证');
        $qualification->setType('quality');
        $qualification->setCertificateNumber('ISO9001-' . $uniqueId);
        $qualification->setIssuingAuthority('中国质量认证中心');
        $qualification->setIssuedDate(new \DateTimeImmutable('-1 year'));
        $qualification->setExpiryDate(new \DateTimeImmutable('+2 years'));
        $qualification->setIsActive(true);
        $qualification->setStatus(SupplierQualificationStatus::APPROVED);

        self::getEntityManager()->persist($qualification);
        self::getEntityManager()->flush();

        $suppliersWithQualifications = $this->repository->findSuppliersWithQualifications();
        $testSuppliers = array_filter($suppliersWithQualifications, fn ($s) => str_contains($s->getName(), $uniqueId));

        $this->assertCount(1, $testSuppliers);
        $this->assertEquals('Qualified Supplier ' . $uniqueId, reset($testSuppliers)->getName());
    }

    public function testFindTopPerformingSuppliers(): void
    {
        $uniqueId = uniqid();

        // 创建高评分供应商
        $highScoreSupplier = $this->createNewEntity();
        $highScoreSupplier->setName('High Score Supplier ' . $uniqueId);
        $highScoreSupplier->setStatus(SupplierStatus::APPROVED);

        // 创建中等评分供应商
        $mediumScoreSupplier = $this->createNewEntity();
        $mediumScoreSupplier->setName('Medium Score Supplier ' . $uniqueId);
        $mediumScoreSupplier->setStatus(SupplierStatus::APPROVED);

        // 创建低评分供应商
        $lowScoreSupplier = $this->createNewEntity();
        $lowScoreSupplier->setName('Low Score Supplier ' . $uniqueId);
        $lowScoreSupplier->setStatus(SupplierStatus::APPROVED);

        self::getEntityManager()->persist($highScoreSupplier);
        self::getEntityManager()->persist($mediumScoreSupplier);
        self::getEntityManager()->persist($lowScoreSupplier);
        self::getEntityManager()->flush();

        // 创建高评分绩效评估
        $highEvaluation = new PerformanceEvaluation();
        $highEvaluation->setSupplier($highScoreSupplier);
        $highEvaluation->setEvaluationNumber('EVAL-HIGH-' . $uniqueId);
        $highEvaluation->setTitle('高评分评估');
        $highEvaluation->setEvaluationPeriod('2024Q1');
        $highEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $highEvaluation->setEvaluator('测试评估员');
        $highEvaluation->setOverallScore(95.0);
        $highEvaluation->setGrade(PerformanceGrade::A);
        $highEvaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        // 创建中等评分绩效评估
        $mediumEvaluation = new PerformanceEvaluation();
        $mediumEvaluation->setSupplier($mediumScoreSupplier);
        $mediumEvaluation->setEvaluationNumber('EVAL-MED-' . $uniqueId);
        $mediumEvaluation->setTitle('中等评分评估');
        $mediumEvaluation->setEvaluationPeriod('2024Q1');
        $mediumEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $mediumEvaluation->setEvaluator('测试评估员');
        $mediumEvaluation->setOverallScore(80.0);
        $mediumEvaluation->setGrade(PerformanceGrade::B);
        $mediumEvaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        // 创建低评分绩效评估
        $lowEvaluation = new PerformanceEvaluation();
        $lowEvaluation->setSupplier($lowScoreSupplier);
        $lowEvaluation->setEvaluationNumber('EVAL-LOW-' . $uniqueId);
        $lowEvaluation->setTitle('低评分评估');
        $lowEvaluation->setEvaluationPeriod('2024Q1');
        $lowEvaluation->setEvaluationDate(new \DateTimeImmutable());
        $lowEvaluation->setEvaluator('测试评估员');
        $lowEvaluation->setOverallScore(70.0);
        $lowEvaluation->setGrade(PerformanceGrade::C);
        $lowEvaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);

        self::getEntityManager()->persist($highEvaluation);
        self::getEntityManager()->persist($mediumEvaluation);
        self::getEntityManager()->persist($lowEvaluation);
        self::getEntityManager()->flush();

        // 测试默认最低评分85.0的情况
        $topPerformers = $this->repository->findTopPerformingSuppliers();
        $testSuppliers = array_filter($topPerformers, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(1, $testSuppliers);
        $this->assertEquals('High Score Supplier ' . $uniqueId, reset($testSuppliers)->getName());

        // 测试自定义最低评分75.0的情况
        $topPerformers75 = $this->repository->findTopPerformingSuppliers(75.0);
        $testSuppliers75 = array_filter($topPerformers75, fn ($s) => str_contains($s->getName(), $uniqueId));
        $this->assertCount(2, $testSuppliers75);

        $supplierNames = array_map(fn ($s) => $s->getName(), $testSuppliers75);
        $this->assertContains('High Score Supplier ' . $uniqueId, $supplierNames);
        $this->assertContains('Medium Score Supplier ' . $uniqueId, $supplierNames);
    }
}
