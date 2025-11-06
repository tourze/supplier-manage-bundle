<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;

/**
 * @extends ServiceEntityRepository<Supplier>
 */
#[AsRepository(entityClass: Supplier::class)]
class SupplierRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Supplier::class);
    }

    public function save(Supplier $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(Supplier $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * 根据状态查找供应商
     * @return Supplier[]
     */
    public function findByStatus(string $status): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', $status)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据供应商类型查找
     * @return Supplier[]
     */
    public function findBySupplierType(string $supplierType): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.supplierType = :supplierType')
            ->setParameter('supplierType', $supplierType)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找活跃的供应商（已批准且未终止）
     * @return Supplier[]
     */
    public function findActiveSuppliers(): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.status = :status')
            ->setParameter('status', SupplierStatus::APPROVED->value)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计各状态的供应商数量
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        /** @var array<array{status: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('s')
            ->select('s.status, COUNT(s.id) as count')
            ->groupBy('s.status')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['status'], $result['count']));
            // 处理枚举类型：如果是枚举对象，获取其值
            $status = $result['status'];
            if ($status instanceof SupplierStatus) {
                $statusKey = $status->value;
            } else {
                assert(is_string($status) || is_int($status));
                $statusKey = (string) $status;
            }
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $counts[$statusKey] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 搜索供应商
     * @return Supplier[]
     */
    public function search(string $query, ?string $status = null, ?string $supplierType = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.name LIKE :query OR s.legalName LIKE :query')
            ->setParameter('query', '%' . $query . '%')
        ;

        if (null !== $status) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $status)
            ;
        }

        if (null !== $supplierType) {
            $qb->andWhere('s.supplierType = :supplierType')
                ->setParameter('supplierType', $supplierType)
            ;
        }

        /** @var array<Supplier> */
        return $qb->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据多个条件筛选供应商
     * @param array<string, mixed> $filters
     * @return Supplier[]
     */
    public function findByMultipleFilters(array $filters): array
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        if (isset($filters['supplierType'])) {
            $qb->andWhere('s.supplierType = :supplierType')
                ->setParameter('supplierType', $filters['supplierType'])
            ;
        }

        if (isset($filters['name'])) {
            assert(is_string($filters['name']));
            $qb->andWhere('s.name LIKE :name OR s.legalName LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%')
            ;
        }

        if (isset($filters['industry'])) {
            $qb->andWhere('s.industry = :industry')
                ->setParameter('industry', $filters['industry'])
            ;
        }

        if (isset($filters['cooperationModel'])) {
            $qb->andWhere('s.cooperationModel = :cooperationModel')
                ->setParameter('cooperationModel', $filters['cooperationModel'])
            ;
        }

        /** @var array<Supplier> */
        return $qb->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 分页查询供应商
     * @param int $page
     * @param int $limit
     * @param array<string, mixed> $filters
     * @return array{data: Supplier[], total: int, page: int, limit: int, totalPages: int}
     */
    public function findWithPagination(int $page, int $limit, array $filters = []): array
    {
        $qb = $this->createQueryBuilder('s');
        $this->applyFilters($qb, $filters);

        // 获取总数
        $countQb = clone $qb;
        $total = (int) $countQb->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // 计算分页
        $offset = ($page - 1) * $limit;
        $totalPages = (int) ceil($total / $limit);

        // 获取分页数据
        /** @var array<Supplier> */
        $data = $qb->select('s')
            ->orderBy('s.name', 'ASC')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'totalPages' => $totalPages,
        ];
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        if (isset($filters['supplierType'])) {
            $qb->andWhere('s.supplierType = :supplierType')
                ->setParameter('supplierType', $filters['supplierType'])
            ;
        }

        if (isset($filters['name'])) {
            assert(is_string($filters['name']));
            $qb->andWhere('s.name LIKE :name OR s.legalName LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%')
            ;
        }

        if (isset($filters['industry'])) {
            $qb->andWhere('s.industry = :industry')
                ->setParameter('industry', $filters['industry'])
            ;
        }
    }

    /**
     * 按供应商类型统计数量
     * @return array<string, int>
     */
    public function countBySupplierType(): array
    {
        /** @var array<array{supplierType: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('s')
            ->select('s.supplierType, COUNT(s.id) as count')
            ->groupBy('s.supplierType')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['supplierType'], $result['count']));
            // 处理枚举类型：如果是枚举对象，获取其值
            $supplierType = $result['supplierType'];
            if ($supplierType instanceof SupplierType) {
                $supplierTypeKey = $supplierType->value;
            } else {
                assert(is_string($supplierType) || is_int($supplierType));
                $supplierTypeKey = (string) $supplierType;
            }
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $counts[$supplierTypeKey] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 按名称模式搜索供应商
     * @return Supplier[]
     */
    public function findByNamePattern(string $pattern): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('LOWER(s.name) LIKE LOWER(:pattern) OR LOWER(s.legalName) LIKE LOWER(:pattern)')
            ->setParameter('pattern', '%' . $pattern . '%')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据注册号查找供应商
     */
    public function findOneByRegistrationNumber(string $registrationNumber): ?Supplier
    {
        /** @var Supplier|null */
        return $this->createQueryBuilder('s')
            ->where('s.registrationNumber = :registrationNumber')
            ->setParameter('registrationNumber', $registrationNumber)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找最近创建的供应商
     * @param int $days 天数
     * @return Supplier[]
     */
    public function findRecentlyCreated(int $days): array
    {
        $date = new \DateTimeImmutable("-{$days} days");

        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.createTime >= :date')
            ->setParameter('date', $date)
            ->orderBy('s.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取统计摘要
     * @return array{totalCount: int, countByStatus: array<string, int>, countBySupplierType: array<string, int>, recentlyCreated: int}
     */
    public function getStatisticsSummary(): array
    {
        // 获取总数
        $totalCount = (int) $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->getQuery()
            ->getSingleScalarResult()
        ;

        // 按状态统计
        $countByStatus = $this->countByStatus();

        // 按供应商类型统计
        $countBySupplierType = $this->countBySupplierType();

        // 最近7天创建的供应商数量
        $recentlyCreatedCount = count($this->findRecentlyCreated(7));

        return [
            'totalCount' => $totalCount,
            'countByStatus' => $countByStatus,
            'countBySupplierType' => $countBySupplierType,
            'recentlyCreated' => $recentlyCreatedCount,
        ];
    }

    /**
     * 按行业查找供应商
     * @return Supplier[]
     */
    public function findByIndustry(string $industry): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.industry = :industry')
            ->setParameter('industry', $industry)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按地区查找供应商
     * @return Supplier[]
     */
    public function findByRegion(string $region): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.legalAddress LIKE :region')
            ->setParameter('region', '%' . $region . '%')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按合作模式查找供应商
     * @return Supplier[]
     */
    public function findByCooperationModel(string $cooperationModel): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->where('s.cooperationModel = :cooperationModel')
            ->setParameter('cooperationModel', $cooperationModel)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据税号查找供应商
     */
    public function findByTaxNumber(string $taxNumber): ?Supplier
    {
        /** @var Supplier|null */
        return $this->createQueryBuilder('s')
            ->where('s.taxNumber = :taxNumber')
            ->setParameter('taxNumber', $taxNumber)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 按行业统计供应商数量
     * @return array<string, int>
     */
    public function countByIndustry(): array
    {
        /** @var array<array{industry: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('s')
            ->select('s.industry, COUNT(s.id) as count')
            ->groupBy('s.industry')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['industry'], $result['count']));
            assert(is_string($result['industry']) || is_int($result['industry']));
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $counts[(string) $result['industry']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 按地区统计供应商数量
     * @return array<string, int>
     */
    public function countByRegion(): array
    {
        /** @var array<array{region: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('s')
            ->select('SUBSTRING(s.legalAddress, 1, 10) as region, COUNT(s.id) as count')
            ->groupBy('region')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['region'], $result['count']));
            assert(is_string($result['region']) || is_int($result['region']));
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $counts[(string) $result['region']] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 高级搜索，支持多个条件
     * @param array<string, mixed> $filters
     * @param int $limit
     * @return Supplier[]
     */
    public function searchWithAdvancedFilters(array $filters, int $limit = 50): array
    {
        $qb = $this->createQueryBuilder('s');

        if (isset($filters['keyword'])) {
            assert(is_string($filters['keyword']));
            $qb->andWhere('s.name LIKE :keyword OR s.legalName LIKE :keyword OR s.registrationNumber LIKE :keyword')
                ->setParameter('keyword', '%' . $filters['keyword'] . '%')
            ;
        }

        if (isset($filters['status'])) {
            $qb->andWhere('s.status = :status')
                ->setParameter('status', $filters['status'])
            ;
        }

        if (isset($filters['supplierType'])) {
            $qb->andWhere('s.supplierType = :supplierType')
                ->setParameter('supplierType', $filters['supplierType'])
            ;
        }

        if (isset($filters['industry'])) {
            $qb->andWhere('s.industry = :industry')
                ->setParameter('industry', $filters['industry'])
            ;
        }

        if (isset($filters['cooperationModel'])) {
            $qb->andWhere('s.cooperationModel = :cooperationModel')
                ->setParameter('cooperationModel', $filters['cooperationModel'])
            ;
        }

        if (isset($filters['region'])) {
            assert(is_string($filters['region']));
            $qb->andWhere('s.legalAddress LIKE :region')
                ->setParameter('region', '%' . $filters['region'] . '%')
            ;
        }

        if (isset($filters['createdAfter'])) {
            $qb->andWhere('s.createTime >= :createdAfter')
                ->setParameter('createdAfter', $filters['createdAfter'])
            ;
        }

        if (isset($filters['createdBefore'])) {
            $qb->andWhere('s.createTime <= :createdBefore')
                ->setParameter('createdBefore', $filters['createdBefore'])
            ;
        }

        /** @var array<Supplier> */
        return $qb->orderBy('s.name', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找有合同的供应商
     * @return Supplier[]
     */
    public function findSuppliersWithContracts(): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->innerJoin('s.contracts', 'c')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找有资质证书的供应商
     * @return Supplier[]
     */
    public function findSuppliersWithQualifications(): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->innerJoin('s.qualifications', 'q')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找有绩效评估的供应商
     * @return Supplier[]
     */
    public function findSuppliersWithPerformanceEvaluations(): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->innerJoin('s.performanceEvaluations', 'pe')
            ->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据联系信息查找供应商
     * @param array<string, mixed> $contactInfo
     * @return Supplier[]
     */
    public function findSuppliersByContactInfo(array $contactInfo): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.contacts', 'sc')
        ;

        if (isset($contactInfo['email'])) {
            assert(is_string($contactInfo['email']));
            $qb->andWhere('sc.email LIKE :email')
                ->setParameter('email', '%' . $contactInfo['email'] . '%')
            ;
        }

        if (isset($contactInfo['phone'])) {
            assert(is_string($contactInfo['phone']));
            $qb->andWhere('sc.phone LIKE :phone')
                ->setParameter('phone', '%' . $contactInfo['phone'] . '%')
            ;
        }

        if (isset($contactInfo['name'])) {
            assert(is_string($contactInfo['name']));
            $qb->andWhere('sc.name LIKE :contactName')
                ->setParameter('contactName', '%' . $contactInfo['name'] . '%')
            ;
        }

        /** @var array<Supplier> */
        return $qb->groupBy('s.id')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找表现优秀的供应商（基于绩效评估）
     * @param float $minScore 最低评分
     * @return Supplier[]
     */
    public function findTopPerformingSuppliers(float $minScore = 85.0): array
    {
        /** @var array<Supplier> */
        return $this->createQueryBuilder('s')
            ->innerJoin('s.performanceEvaluations', 'pe')
            ->where('pe.overallScore >= :minScore')
            ->andWhere('pe.status = :status')
            ->setParameter('minScore', (string) $minScore)
            ->setParameter('status', 'confirmed')
            ->groupBy('s.id')
            ->orderBy('AVG(pe.overallScore)', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找需要关注的供应商（低评分或问题供应商）
     * @param float $maxScore 最高评分阈值
     * @return Supplier[]
     */
    public function findSuppliersNeedingAttention(float $maxScore = 70.0): array
    {
        $qb = $this->createQueryBuilder('s');
        $qb->leftJoin('s.performanceEvaluations', 'pe')
            ->where(
                $qb->expr()->orX(
                    's.status = :suspended',
                    's.status = :terminated',
                    $qb->expr()->andX(
                        'pe.overallScore <= :maxScore',
                        'pe.status = :confirmed'
                    )
                )
            )
            ->setParameter('suspended', SupplierStatus::SUSPENDED->value)
            ->setParameter('terminated', SupplierStatus::TERMINATED->value)
            ->setParameter('maxScore', (string) $maxScore)
            ->setParameter('confirmed', 'confirmed')
            ->orderBy('s.name', 'ASC')
            ->distinct()
        ;

        $query = $qb->getQuery();

        /** @var array<Supplier> */
        return $query->getResult();
    }

    /**
     * 获取供应商平均注册年龄（天数）
     */
    public function getAverageRegistrationAgeInDays(): float
    {
        /** @var array<array{createTime: mixed}> */
        $suppliers = $this->createQueryBuilder('s')
            ->select('s.createTime')
            ->getQuery()
            ->getResult()
        ;

        if ([] === $suppliers) {
            return 0.0;
        }

        $now = new \DateTimeImmutable();
        $totalDays = 0;
        $count = 0;

        foreach ($suppliers as $supplier) {
            $createTime = $supplier['createTime'];
            if ($createTime instanceof \DateTimeImmutable) {
                $diff = $now->diff($createTime);
                if (false !== $diff->days) {
                    $totalDays += $diff->days;
                    ++$count;
                }
            }
        }

        return $count > 0 ? $totalDays / $count : 0.0;
    }
}
