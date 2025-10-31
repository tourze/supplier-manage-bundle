<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: EvaluationItem::class)]
class EvaluationItemEntityListener
{
    public function preUpdate(EvaluationItem $item, PreUpdateEventArgs $event): void
    {
        $item->setUpdateTime(new \DateTimeImmutable());
    }
}
