<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\Supplier;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Supplier::class)]
class SupplierEntityListener
{
    public function preUpdate(Supplier $supplier, PreUpdateEventArgs $event): void
    {
        $supplier->setUpdateTime(new \DateTimeImmutable());
    }
}
