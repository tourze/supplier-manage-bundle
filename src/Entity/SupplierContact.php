<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SupplierManageBundle\Repository\SupplierContactRepository;

#[ORM\Entity(repositoryClass: SupplierContactRepository::class)]
#[ORM\Table(name: 'supplier_contact', options: ['comment' => '供应商联系人表'])]
class SupplierContact implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'contacts')]
    #[ORM\JoinColumn(name: 'supplier_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Supplier $supplier;

    #[ORM\Column(length: 100, options: ['comment' => '联系人姓名'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $name;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '职位'])]
    #[Assert\Length(max: 100)]
    private ?string $position = null;

    #[ORM\Column(length: 255, options: ['comment' => '邮箱'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Assert\Email]
    #[IndexColumn]
    private string $email;

    #[ORM\Column(length: 50, options: ['comment' => '电话'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Regex(pattern: '/^[\d\s\-\+\(\)]+$/', message: '电话号码格式不正确')]
    private string $phone;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否主要联系人'])]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $isPrimary = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSupplier(): Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(Supplier $supplier): void
    {
        $this->supplier = $supplier;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getPosition(): ?string
    {
        return $this->position;
    }

    public function setPosition(?string $position): void
    {
        $this->position = $position;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): void
    {
        $this->email = $email;
    }

    public function getPhone(): string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): void
    {
        $this->phone = $phone;
    }

    public function getIsPrimary(): bool
    {
        return $this->isPrimary;
    }

    public function setIsPrimary(bool $isPrimary): void
    {
        $this->isPrimary = $isPrimary;
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->email . ')';
    }
}
