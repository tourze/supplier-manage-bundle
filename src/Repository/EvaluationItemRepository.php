<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;

/**
 * @extends ServiceEntityRepository<EvaluationItem>
 */
#[AsRepository(entityClass: EvaluationItem::class)]
class EvaluationItemRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EvaluationItem::class);
    }

    public function save(EvaluationItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    public function remove(EvaluationItem $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $entityManager = $this->getEntityManager();
            $entityManager->flush();
        }
    }

    /**
     * @return EvaluationItem[]
     */
    public function findByEvaluation(PerformanceEvaluation $evaluation): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->where('ei.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->orderBy('ei.weight', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function findByItemType(string $itemType): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->where('ei.itemType = :itemType')
            ->setParameter('itemType', $itemType)
            ->orderBy('ei.weight', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function findQuantitativeItems(): array
    {
        return $this->findByItemType(EvaluationItemType::QUANTITATIVE->value);
    }

    /**
     * @return EvaluationItem[]
     */
    public function findQualitativeItems(): array
    {
        return $this->findByItemType(EvaluationItemType::QUALITATIVE->value);
    }

    /**
     * @return EvaluationItem[]
     */
    public function findByWeightRange(float $minWeight, float $maxWeight): array
    {
        /** @var array<EvaluationItem> */
        return $this->createQueryBuilder('ei')
            ->where('ei.weight BETWEEN :minWeight AND :maxWeight')
            ->setParameter('minWeight', (string) $minWeight)
            ->setParameter('maxWeight', (string) $maxWeight)
            ->orderBy('ei.weight', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function findByScoreRange(float $minScore, float $maxScore): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->where('ei.score BETWEEN :minScore AND :maxScore')
            ->setParameter('minScore', (string) $minScore)
            ->setParameter('maxScore', (string) $maxScore)
            ->orderBy('ei.score', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function findHighWeightItems(float $minWeight = 20.0): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->where('ei.weight >= :minWeight')
            ->setParameter('minWeight', (string) $minWeight)
            ->orderBy('ei.weight', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function findLowScoreItems(float $maxScore = 60.0): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->where('ei.score <= :maxScore')
            ->setParameter('maxScore', (string) $maxScore)
            ->orderBy('ei.score', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalWeightByEvaluation(PerformanceEvaluation $evaluation): float
    {
        $result = $this->createQueryBuilder('ei')
            ->select('SUM(ei.weight)')
            ->where('ei.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0.0);
    }

    public function getAverageScoreByEvaluation(PerformanceEvaluation $evaluation): float
    {
        $result = $this->createQueryBuilder('ei')
            ->select('AVG(ei.score)')
            ->where('ei.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (float) ($result ?? 0.0);
    }

    public function countByEvaluation(PerformanceEvaluation $evaluation): int
    {
        return (int) $this->createQueryBuilder('ei')
            ->select('COUNT(ei.id)')
            ->where('ei.evaluation = :evaluation')
            ->setParameter('evaluation', $evaluation)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    public function countByItemType(string $itemType): int
    {
        return (int) $this->createQueryBuilder('ei')
            ->select('COUNT(ei.id)')
            ->where('ei.itemType = :itemType')
            ->setParameter('itemType', $itemType)
            ->getQuery()
            ->getSingleScalarResult()
        ;
    }

    /**
     * @return EvaluationItem[]
     */
    public function search(string $keyword, int $limit = 10): array
    {
        /** @var array<EvaluationItem> */

        return $this->createQueryBuilder('ei')
            ->leftJoin('ei.evaluation', 'pe')
            ->leftJoin('pe.supplier', 's')
            ->where('ei.itemName LIKE :keyword')
            ->orWhere('ei.description LIKE :keyword')
            ->orWhere('s.name LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('ei.weight', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return array<string, int>
     */
    public function getItemTypeDistribution(): array
    {
        /** @var array<array{itemType: mixed, count: mixed}> */
        $results = $this->createQueryBuilder('ei')
            ->select('ei.itemType, COUNT(ei.id) as count')
            ->groupBy('ei.itemType')
            ->getQuery()
            ->getResult()
        ;

        $distribution = [];
        foreach ($results as $result) {
            assert(is_array($result) && isset($result['itemType']) && isset($result['count']));
            $itemType = $result['itemType'];
            if ($itemType instanceof EvaluationItemType) {
                $itemTypeKey = $itemType->value;
            } else {
                assert(is_string($itemType));
                $itemTypeKey = $itemType;
            }
            assert(is_int($result['count']) || is_string($result['count']) || is_float($result['count']));
            $distribution[$itemTypeKey] = (int) $result['count'];
        }

        return $distribution;
    }

    public function getWeightedScoreByEvaluation(PerformanceEvaluation $evaluation): float
    {
        $items = $this->findByEvaluation($evaluation);
        $totalScore = 0.0;

        foreach ($items as $item) {
            $score = (float) $item->getScore();
            $maxScore = (float) $item->getMaxScore();
            $weight = (float) $item->getWeight();

            if ($maxScore > 0) {
                $totalScore += ($score / $maxScore) * $weight;
            }
        }

        return $totalScore;
    }
}
