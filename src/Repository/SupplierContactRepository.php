<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Entity\SupplierContact;

/**
 * @extends ServiceEntityRepository<SupplierContact>
 */
#[AsRepository(entityClass: SupplierContact::class)]
class SupplierContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupplierContact::class);
    }

    public function save(SupplierContact $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(SupplierContact $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * 根据供应商查找所有联系人
     *
     * @return SupplierContact[]
     */
    public function findBySupplier(Supplier $supplier): array
    {        /** @var array<SupplierContact> */
        return $this->createQueryBuilder('sc')
            ->where('sc.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('sc.isPrimary', 'DESC')
            ->addOrderBy('sc.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 查找供应商的主要联系人
     */
    public function findPrimaryContactBySupplier(Supplier $supplier): ?SupplierContact
    {        /** @var SupplierContact|null */
        return $this->createQueryBuilder('sc')
            ->where('sc.supplier = :supplier')
            ->andWhere('sc.isPrimary = :isPrimary')
            ->setParameter('supplier', $supplier)
            ->setParameter('isPrimary', true)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 根据邮箱查找联系人
     */
    public function findByEmail(string $email): ?SupplierContact
    {        /** @var SupplierContact|null */
        return $this->createQueryBuilder('sc')
            ->where('sc.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * 查找某个供应商的所有主要联系人（用于检查数据一致性）
     *
     * @return SupplierContact[]
     */
    public function findPrimaryContactsBySupplier(Supplier $supplier): array
    {        /** @var array<SupplierContact> */
        return $this->createQueryBuilder('sc')
            ->where('sc.supplier = :supplier')
            ->andWhere('sc.isPrimary = :isPrimary')
            ->setParameter('supplier', $supplier)
            ->setParameter('isPrimary', true)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 搜索联系人
     *
     * @param array<string, mixed> $criteria
     * @return SupplierContact[]
     */
    public function search(array $criteria): array
    {
        $qb = $this->createQueryBuilder('sc')
            ->leftJoin('sc.supplier', 's')
        ;

        if (isset($criteria['name']) && '' !== $criteria['name']) {
            assert(is_string($criteria['name']));
            $qb->andWhere('sc.name LIKE :name')
                ->setParameter('name', '%' . $criteria['name'] . '%')
            ;
        }

        if (isset($criteria['email']) && '' !== $criteria['email']) {
            assert(is_string($criteria['email']));
            $qb->andWhere('sc.email LIKE :email')
                ->setParameter('email', '%' . $criteria['email'] . '%')
            ;
        }

        if (isset($criteria['supplier_name']) && '' !== $criteria['supplier_name']) {
            assert(is_string($criteria['supplier_name']));
            $qb->andWhere('s.name LIKE :supplier_name')
                ->setParameter('supplier_name', '%' . $criteria['supplier_name'] . '%')
            ;
        }

        if (isset($criteria['is_primary'])) {
            $qb->andWhere('sc.isPrimary = :is_primary')
                ->setParameter('is_primary', (bool) $criteria['is_primary'])
            ;
        }        /** @var array<SupplierContact> */

        return $qb->orderBy('s.name', 'ASC')
            ->addOrderBy('sc.isPrimary', 'DESC')
            ->addOrderBy('sc.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 统计供应商联系人数量
     */
    public function countBySupplier(Supplier $supplier): int
    {
        return (int) $this->createQueryBuilder('sc')
            ->select('COUNT(sc.id)')
            ->where('sc.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }
}
