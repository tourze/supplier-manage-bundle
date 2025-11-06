<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\ContractStatus;

/**
 * @extends ServiceEntityRepository<Contract>
 */
#[AsRepository(entityClass: Contract::class)]
class ContractRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contract::class);
    }

    public function save(Contract $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(Contract $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * 根据供应商查找所有合同
     *
     * @return array<Contract>
     */
    public function findBySupplier(Supplier $supplier): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('c.startDate', 'DESC')
            ->addOrderBy('c.contractNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找有效的合同（未过期且状态为活跃）
     *
     * @return array<Contract>
     */
    public function findActiveContracts(): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.endDate > :today')
            ->setParameter('statuses', ['approved', 'active'])
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将过期的合同（指定天数内）
     *
     * @return array<Contract>
     */
    public function findExpiringWithinDays(int $days): array
    {
        $expiryDate = new \DateTimeImmutable('+' . $days . ' days');

        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.endDate <= :expiryDate')
            ->andWhere('c.endDate > :today')
            ->setParameter('statuses', ['approved', 'active'])
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据合同类型查找
     *
     * @return array<Contract>
     */
    public function findByType(string $type): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.contractType = :type')
            ->setParameter('type', $type)
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据状态查找合同
     *
     * @return array<Contract>
     */
    public function findByStatus(string $status): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->setParameter('status', $status)
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 搜索合同
     *
     * @param array<string, mixed> $criteria
     * @return array<Contract>
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.supplier', 's')
        ;

        $this->applyTextCriteria($qb, $criteria);
        $this->applyTypeCriteria($qb, $criteria);
        $this->applyAmountCriteria($qb, $criteria);
        $this->applyDateCriteria($qb, $criteria);

        /** @var array<Contract> */
        return $qb->orderBy('c.startDate', 'DESC')
            ->addOrderBy('c.contractNumber', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 应用文本搜索条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyTextCriteria(QueryBuilder $qb, array $criteria): void
    {
        $textFilters = [
            'contract_number' => ['field' => 'c.contractNumber', 'operator' => 'LIKE'],
            'title' => ['field' => 'c.title', 'operator' => 'LIKE'],
            'supplier_name' => ['field' => 's.name', 'operator' => 'LIKE'],
        ];

        foreach ($textFilters as $key => $config) {
            if (isset($criteria[$key]) && '' !== $criteria[$key]) {
                assert(is_string($criteria[$key]));
                $qb->andWhere($config['field'] . ' ' . $config['operator'] . ' :' . $key)
                    ->setParameter($key, '%' . $criteria[$key] . '%')
                ;
            }
        }
    }

    /**
     * 应用类型和状态条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyTypeCriteria(QueryBuilder $qb, array $criteria): void
    {
        $exactFilters = [
            'type' => 'c.contractType',
            'status' => 'c.status',
        ];

        foreach ($exactFilters as $key => $field) {
            if (isset($criteria[$key]) && '' !== $criteria[$key]) {
                $qb->andWhere($field . ' = :' . $key)
                    ->setParameter($key, $criteria[$key])
                ;
            }
        }
    }

    /**
     * 应用金额范围条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyAmountCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['amount_min'])) {
            $qb->andWhere('c.amount >= :amount_min')
                ->setParameter('amount_min', $criteria['amount_min'])
            ;
        }

        if (isset($criteria['amount_max'])) {
            $qb->andWhere('c.amount <= :amount_max')
                ->setParameter('amount_max', $criteria['amount_max'])
            ;
        }
    }

    /**
     * 应用日期范围条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyDateCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['start_date_from'])) {
            $qb->andWhere('c.startDate >= :start_date_from')
                ->setParameter('start_date_from', $criteria['start_date_from'])
            ;
        }

        if (isset($criteria['start_date_to'])) {
            $qb->andWhere('c.startDate <= :start_date_to')
                ->setParameter('start_date_to', $criteria['start_date_to'])
            ;
        }
    }

    /**
     * 统计供应商合同数量
     */
    public function countBySupplier(Supplier $supplier): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * 根据合同编号查找
     */
    public function findByContractNumber(string $contractNumber): ?Contract
    {
        /** @var Contract|null */
        return $this->createQueryBuilder('c')
            ->where('c.contractNumber = :contractNumber')
            ->setParameter('contractNumber', $contractNumber)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找供应商的当前活跃合同
     */
    public function findCurrentActiveBySupplier(Supplier $supplier): ?Contract
    {
        $now = new \DateTimeImmutable();

        /** @var Contract|null */
        return $this->createQueryBuilder('c')
            ->where('c.supplier = :supplier')
            ->andWhere('c.status = :status')
            ->andWhere('c.startDate <= :now')
            ->andWhere('c.endDate >= :now')
            ->setParameter('supplier', $supplier)
            ->setParameter('status', 'active')
            ->setParameter('now', $now)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找即将到期的合同
     *
     * @return array<Contract>
     */
    public function findExpiringContracts(int $days): array
    {
        $expiryDate = new \DateTimeImmutable('+' . $days . ' days');

        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.endDate <= :expiryDate')
            ->andWhere('c.endDate > :today')
            ->setParameter('statuses', ['approved', 'active'])
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按日期范围查找合同
     *
     * @return array<Contract>
     */
    public function findByDateRange(\DateTimeImmutable $startDate, \DateTimeImmutable $endDate): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.startDate <= :endDate')
            ->andWhere('c.endDate >= :startDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('c.startDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按金额范围查找合同
     *
     * @return array<Contract>
     */
    public function findByAmountRange(float $minAmount, float $maxAmount): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.amount >= :minAmount')
            ->andWhere('c.amount <= :maxAmount')
            ->setParameter('minAmount', $minAmount)
            ->setParameter('maxAmount', $maxAmount)
            ->orderBy('c.amount', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按状态统计合同数量
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        /** @var array<array{status: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('c')
            ->select('c.status, COUNT(c.id) as count')
            ->groupBy('c.status')
            ->getQuery()
            ->getResult()
        ;

        $counts = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['status'], $result['count']));
            $status = $result['status'];
            if ($status instanceof ContractStatus) {
                $status = $status->value;
            }
            assert(is_string($status) || is_int($status));
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $counts[(string) $status] = (int) $result['count'];
        }

        return $counts;
    }

    /**
     * 获取合同统计信息
     *
     * @return array{totalCount: int, totalAmount: float, averageAmount: float, countByStatus: array<string, int>}
     */
    public function getContractStatistics(): array
    {
        // 获取总数和总金额
        /** @var array{totalCount: mixed, totalAmount: mixed} */
        $totalResult = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as totalCount, SUM(c.amount) as totalAmount')
            ->getQuery()
            ->getSingleResult()
        ;

        assert(is_array($totalResult) && isset($totalResult['totalCount']));
        assert(is_int($totalResult['totalCount']) || is_string($totalResult['totalCount']) || is_float($totalResult['totalCount']));
        $totalCount = (int) $totalResult['totalCount'];

        $totalAmountRaw = $totalResult['totalAmount'] ?? null;
        assert(null === $totalAmountRaw || is_float($totalAmountRaw) || is_string($totalAmountRaw) || is_int($totalAmountRaw));
        $totalAmount = (float) ($totalAmountRaw ?? 0.0);
        $averageAmount = $totalCount > 0 ? $totalAmount / $totalCount : 0.0;

        // 按状态统计
        $countByStatus = $this->countByStatus();

        return [
            'totalCount' => $totalCount,
            'totalAmount' => $totalAmount,
            'averageAmount' => $averageAmount,
            'countByStatus' => $countByStatus,
        ];
    }

    /**
     * 获取到期预警信息
     *
     * @return array{count: int, contracts: Contract[]}
     */
    public function getExpiringContractsAlert(int $days): array
    {
        $contracts = $this->findExpiringContracts($days);

        return [
            'count' => count($contracts),
            'contracts' => $contracts,
        ];
    }

    /**
     * 搜索合同（简化版本）
     *
     * @return array<Contract>
     */
    public function searchContracts(string $keyword): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.title LIKE :keyword OR c.contractNumber LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找特定供应商和状态的合同
     *
     * @return array<Contract>
     */
    public function findContractsBySupplierAndStatus(Supplier $supplier, string $status): array
    {
        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.supplier = :supplier')
            ->andWhere('c.status = :status')
            ->setParameter('supplier', $supplier)
            ->setParameter('status', $status)
            ->orderBy('c.startDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取供应商合同绩效统计
     *
     * @return array{totalContracts: int, completedContracts: int, totalValue: float, averageValue: float}
     */
    public function getContractPerformanceStats(Supplier $supplier): array
    {
        /** @var array{totalContracts: mixed, completedContracts: mixed, totalValue: mixed} */
        $results = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as totalContracts, SUM(c.amount) as totalValue')
            ->addSelect('COUNT(CASE WHEN c.status = :completedStatus THEN 1 END) as completedContracts')
            ->where('c.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->setParameter('completedStatus', 'completed')
            ->getQuery()
            ->getSingleResult()
        ;

        assert(is_array($results));
        assert(isset($results['totalContracts'], $results['completedContracts']));
        assert(is_int($results['totalContracts']) || is_string($results['totalContracts']) || is_float($results['totalContracts']));
        assert(is_int($results['completedContracts']) || is_string($results['completedContracts']) || is_float($results['completedContracts']));

        $totalContracts = (int) $results['totalContracts'];
        $completedContracts = (int) $results['completedContracts'];

        $totalValueRaw = $results['totalValue'] ?? null;
        assert(null === $totalValueRaw || is_float($totalValueRaw) || is_string($totalValueRaw) || is_int($totalValueRaw));
        $totalValue = (float) ($totalValueRaw ?? 0.0);
        $averageValue = $totalContracts > 0 ? $totalValue / $totalContracts : 0.0;

        return [
            'totalContracts' => $totalContracts,
            'completedContracts' => $completedContracts,
            'totalValue' => $totalValue,
            'averageValue' => $averageValue,
        ];
    }

    /**
     * 查找超过指定天数的草稿合同
     *
     * @return array<Contract>
     */
    public function findDraftContractsOlderThan(int $days): array
    {
        $cutoffDate = new \DateTimeImmutable('-' . $days . ' days');

        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status = :status')
            ->andWhere('c.createTime < :cutoffDate')
            ->setParameter('status', 'draft')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('c.createTime', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 按金额阈值查找合同
     *
     * @return array<Contract>
     */
    public function findContractsByAmountThreshold(float $threshold, string $operator = '>='): array
    {
        $qb = $this->createQueryBuilder('c');

        switch ($operator) {
            case '>=':
                $qb->where('c.amount >= :threshold');
                break;
            case '<=':
                $qb->where('c.amount <= :threshold');
                break;
            case '>':
                $qb->where('c.amount > :threshold');
                break;
            case '<':
                $qb->where('c.amount < :threshold');
                break;
            case '=':
                $qb->where('c.amount = :threshold');
                break;
            default:
                $qb->where('c.amount >= :threshold');
                break;
        }

        /** @var array<Contract> */
        return $qb->setParameter('threshold', $threshold)
            ->orderBy('c.amount', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取合同完成率
     */
    public function getContractCompletionRate(): float
    {
        /** @var array{totalContracts: mixed, completedContracts: mixed} */
        $totalResult = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) as totalContracts')
            ->addSelect('COUNT(CASE WHEN c.status = :completedStatus THEN 1 END) as completedContracts')
            ->setParameter('completedStatus', 'completed')
            ->getQuery()
            ->getSingleResult()
        ;

        assert(is_array($totalResult) && isset($totalResult['totalContracts'], $totalResult['completedContracts']));
        assert(is_int($totalResult['totalContracts']) || is_string($totalResult['totalContracts']) || is_float($totalResult['totalContracts']));
        assert(is_int($totalResult['completedContracts']) || is_string($totalResult['completedContracts']) || is_float($totalResult['completedContracts']));

        $totalContracts = (int) $totalResult['totalContracts'];
        $completedContracts = (int) $totalResult['completedContracts'];

        return $totalContracts > 0 ? ($completedContracts / $totalContracts) * 100 : 0.0;
    }

    /**
     * 查找可续约的合同
     *
     * @return array<Contract>
     */
    public function findRenewableContracts(int $days = 60): array
    {
        $renewalDate = new \DateTimeImmutable('+' . $days . ' days');

        /** @var array<Contract> */
        return $this->createQueryBuilder('c')
            ->where('c.status IN (:statuses)')
            ->andWhere('c.endDate <= :renewalDate')
            ->andWhere('c.endDate > :today')
            ->setParameter('statuses', ['active', 'approved'])
            ->setParameter('renewalDate', $renewalDate)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('c.endDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取供应商合同摘要
     *
     * @return array{activeCount: int, totalValue: float, avgContractValue: float, latestContract: ?Contract}
     */
    public function getSupplierContractSummary(Supplier $supplier): array
    {
        /** @var array{activeCount: mixed, totalValue: mixed, avgValue: mixed} */
        $stats = $this->createQueryBuilder('c')
            ->select('COUNT(CASE WHEN c.status = :activeStatus THEN 1 END) as activeCount')
            ->addSelect('SUM(c.amount) as totalValue')
            ->addSelect('AVG(c.amount) as avgValue')
            ->where('c.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->setParameter('activeStatus', 'active')
            ->getQuery()
            ->getSingleResult()
        ;

        assert(is_array($stats) && isset($stats['activeCount']));
        assert(is_int($stats['activeCount']) || is_string($stats['activeCount']) || is_float($stats['activeCount']));

        $totalValueRaw = $stats['totalValue'] ?? null;
        assert(null === $totalValueRaw || is_float($totalValueRaw) || is_string($totalValueRaw) || is_int($totalValueRaw));

        $avgValueRaw = $stats['avgValue'] ?? null;
        assert(null === $avgValueRaw || is_float($avgValueRaw) || is_string($avgValueRaw) || is_int($avgValueRaw));

        /** @var Contract|null */
        $latestContract = $this->createQueryBuilder('c')
            ->where('c.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('c.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;

        return [
            'activeCount' => (int) $stats['activeCount'],
            'totalValue' => (float) ($totalValueRaw ?? 0.0),
            'avgContractValue' => (float) ($avgValueRaw ?? 0.0),
            'latestContract' => $latestContract,
        ];
    }
}
