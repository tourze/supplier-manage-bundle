<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: PerformanceEvaluation::class)]
class PerformanceEvaluationEntityListener
{
    public function preUpdate(PerformanceEvaluation $evaluation, PreUpdateEventArgs $event): void
    {
        $evaluation->setUpdateTime(new \DateTimeImmutable());
    }
}
