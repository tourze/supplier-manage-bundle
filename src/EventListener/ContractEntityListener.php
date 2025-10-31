<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\Contract;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: Contract::class)]
class ContractEntityListener
{
    public function preUpdate(Contract $contract, PreUpdateEventArgs $event): void
    {
        $contract->setUpdateTime(new \DateTimeImmutable());
    }
}
