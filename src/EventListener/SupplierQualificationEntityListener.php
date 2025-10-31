<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: SupplierQualification::class)]
class SupplierQualificationEntityListener
{
    public function preUpdate(SupplierQualification $qualification, PreUpdateEventArgs $event): void
    {
        $qualification->setUpdateTime(new \DateTimeImmutable());
    }
}
