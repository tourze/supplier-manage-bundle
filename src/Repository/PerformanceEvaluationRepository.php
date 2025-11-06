<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;

/**
 * @extends ServiceEntityRepository<PerformanceEvaluation>
 */
#[AsRepository(entityClass: PerformanceEvaluation::class)]
class PerformanceEvaluationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PerformanceEvaluation::class);
    }

    public function save(PerformanceEvaluation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(PerformanceEvaluation $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findBySupplier(Supplier $supplier): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByStatus(string $status): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.status = :status')
            ->setParameter('status', $status)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByPeriod(string $period): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.evaluationPeriod = :period')
            ->setParameter('period', $period)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByGrade(string $grade): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.grade = :grade')
            ->setParameter('grade', $grade)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByEvaluator(string $evaluator): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.evaluator = :evaluator')
            ->setParameter('evaluator', $evaluator)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByDateRange(\DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.evaluationDate BETWEEN :startDate AND :endDate')
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findByScoreRange(float $minScore, float $maxScore): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.overallScore BETWEEN :minScore AND :maxScore')
            ->setParameter('minScore', (string) $minScore)
            ->setParameter('maxScore', (string) $maxScore)
            ->orderBy('pe.overallScore', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findByEvaluationNumber(string $evaluationNumber): ?PerformanceEvaluation
    {        /** @var PerformanceEvaluation|null */
        return $this->createQueryBuilder('pe')
            ->where('pe.evaluationNumber = :evaluationNumber')
            ->setParameter('evaluationNumber', $evaluationNumber)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function countBySupplier(Supplier $supplier): int
    {
        return (int) $this->createQueryBuilder('pe')
            ->select('COUNT(pe.id)')
            ->where('pe.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function getLatestBySupplier(Supplier $supplier): ?PerformanceEvaluation
    {        /** @var PerformanceEvaluation|null */
        return $this->createQueryBuilder('pe')
            ->where('pe.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->orderBy('pe.evaluationDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function search(string $keyword, int $limit = 10): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->leftJoin('pe.supplier', 's')
            ->where('pe.title LIKE :keyword')
            ->orWhere('pe.evaluationNumber LIKE :keyword')
            ->orWhere('pe.evaluator LIKE :keyword')
            ->orWhere('s.name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('pe.evaluationDate', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findHighPerformers(float $minScore = 90.0): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.overallScore >= :minScore')
            ->setParameter('minScore', (string) $minScore)
            ->orderBy('pe.overallScore', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return PerformanceEvaluation[]
     */
    public function findLowPerformers(float $maxScore = 60.0): array
    {        /** @var array<PerformanceEvaluation> */
        return $this->createQueryBuilder('pe')
            ->where('pe.overallScore <= :maxScore')
            ->setParameter('maxScore', (string) $maxScore)
            ->orderBy('pe.overallScore', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getAverageScoreBySupplier(Supplier $supplier): float
    {
        $result = $this->createQueryBuilder('pe')
            ->select('AVG(pe.overallScore)')
            ->where('pe.supplier = :supplier')
            ->setParameter('supplier', $supplier)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0.0);
    }

    /**
     * @return array<string, int>
     */
    public function getGradeDistribution(): array
    {
        $entityManager = $this->getEntityManager();
        $conn = $entityManager->getConnection();
        $sql = 'SELECT grade, COUNT(id) as count FROM supplier_performance_evaluation GROUP BY grade';
        $stmt = $conn->executeQuery($sql);
        /** @var array<array<string, mixed>> */
        $results = $stmt->fetchAllAssociative();

        $distribution = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['grade'], $result['count']));
            assert(is_string($result['grade']) || is_int($result['grade']));
            assert(is_int($result['count']) || is_string($result['count']));
            $distribution[(string) $result['grade']] = (int) $result['count'];
        }

        return $distribution;
    }
}
