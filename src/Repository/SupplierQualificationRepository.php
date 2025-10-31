<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;

/**
 * @extends ServiceEntityRepository<SupplierQualification>
 */
#[AsRepository(entityClass: SupplierQualification::class)]
class SupplierQualificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierQualification::class);
    }

    public function save(SupplierQualification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(SupplierQualification $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * 根据供应商查找所有资质
     *
     * @return SupplierQualification[]
     */
    public function findBySupplier(Supplier $supplier): array
    {        /** @var array<SupplierQualification> */
        return $this->createQueryBuilder('sq')
            ->where('sq.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('sq.expiryDate', 'ASC')
            ->addOrderBy('sq.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找供应商的有效资质
     *
     * @return SupplierQualification[]
     */
    public function findActiveBySupplier(Supplier $supplier): array
    {        /** @var array<SupplierQualification> */
        return $this->createQueryBuilder('sq')
            ->where('sq.supplier = :supplier')
            ->andWhere('sq.isActive = :isActive')
            ->andWhere('sq.expiryDate > :today')
            ->setParameter('supplier', $supplier)
            ->setParameter('isActive', true)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('sq.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找即将过期的资质（指定天数内）
     *
     * @return SupplierQualification[]
     */
    public function findExpiringWithinDays(int $days): array
    {
        $expiryDate = new \DateTimeImmutable('+' . $days . ' days');        /** @var array<SupplierQualification> */

        return $this->createQueryBuilder('sq')
            ->where('sq.isActive = :isActive')
            ->andWhere('sq.expiryDate <= :expiryDate')
            ->andWhere('sq.expiryDate > :today')
            ->setParameter('isActive', true)
            ->setParameter('expiryDate', $expiryDate)
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('sq.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据资质类型查找
     *
     * @return SupplierQualification[]
     */
    public function findByType(string $type): array
    {        /** @var array<SupplierQualification> */
        return $this->createQueryBuilder('sq')
            ->where('sq.type = :type')
            ->andWhere('sq.isActive = :isActive')
            ->setParameter('type', $type)
            ->setParameter('isActive', true)
            ->orderBy('sq.expiryDate', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 搜索资质
     *
     * @param array<string, mixed> $criteria
     * @return SupplierQualification[]
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('sq')
            ->leftJoin('sq.supplier', 's')
        ;

        $this->applyQualificationTextCriteria($qb, $criteria);
        $this->applyQualificationBooleanCriteria($qb, $criteria);
        $this->applyQualificationExactCriteria($qb, $criteria);        /** @var array<SupplierQualification> */

        return $qb->orderBy('s.name', 'ASC')
            ->addOrderBy('sq.expiryDate', 'ASC')
            ->addOrderBy('sq.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 应用文本搜索条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyQualificationTextCriteria(QueryBuilder $qb, array $criteria): void
    {
        $textFilters = [
            'name' => 'sq.name',
            'supplier_name' => 's.name',
            'certificate_number' => 'sq.certificateNumber',
            'issuing_authority' => 'sq.issuingAuthority',
        ];

        foreach ($textFilters as $key => $field) {
            if (isset($criteria[$key]) && '' !== $criteria[$key]) {
                assert(is_string($criteria[$key]));
                $qb->andWhere($field . ' LIKE :' . $key)
                    ->setParameter($key, '%' . $criteria[$key] . '%')
                ;
            }
        }
    }

    /**
     * 应用布尔值条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyQualificationBooleanCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['is_active'])) {
            $qb->andWhere('sq.isActive = :is_active')
                ->setParameter('is_active', (bool) $criteria['is_active'])
            ;
        }
    }

    /**
     * 应用精确匹配条件
     *
     * @param array<string, mixed> $criteria
     */
    private function applyQualificationExactCriteria(QueryBuilder $qb, array $criteria): void
    {
        if (isset($criteria['type']) && '' !== $criteria['type']) {
            $qb->andWhere('sq.type = :type')
                ->setParameter('type', $criteria['type'])
            ;
        }
    }

    /**
     * 统计供应商资质数量
     */
    public function countBySupplier(Supplier $supplier): int
    {
        return (int) $this->createQueryBuilder('sq')
            ->select('COUNT(sq.id)')
            ->where('sq.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
