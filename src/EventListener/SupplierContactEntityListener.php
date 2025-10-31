<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Tourze\SupplierManageBundle\Entity\SupplierContact;

#[AsEntityListener(event: Events::preUpdate, method: 'preUpdate', entity: SupplierContact::class)]
class SupplierContactEntityListener
{
    public function preUpdate(SupplierContact $contact, PreUpdateEventArgs $event): void
    {
        $contact->setUpdateTime(new \DateTimeImmutable());
    }
}
