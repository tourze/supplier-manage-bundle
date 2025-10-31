<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;
use Tourze\SupplierManageBundle\Repository\SupplierQualificationRepository;

#[ORM\Entity(repositoryClass: SupplierQualificationRepository::class)]
#[ORM\Table(name: 'supplier_qualification', options: ['comment' => '供应商资质表'])]
class SupplierQualification implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'qualifications')]
    #[ORM\JoinColumn(name: 'supplier_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Supplier $supplier;

    #[ORM\Column(length: 255, options: ['comment' => '资质名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $name;

    #[ORM\Column(length: 50, options: ['comment' => '资质类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['quality', 'safety', 'environment', 'industry', 'other'])]
    #[IndexColumn]
    private string $type;

    #[ORM\Column(length: 255, options: ['comment' => '证书编号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $certificateNumber;

    #[ORM\Column(length: 255, options: ['comment' => '颁发机构'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $issuingAuthority;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '颁发日期'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $issuedDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '到期日期'])]
    #[Assert\NotNull]
    #[IndexColumn]
    private \DateTimeImmutable $expiryDate;

    #[ORM\Column(length: 500, nullable: true, options: ['comment' => '证书文件路径'])]
    #[Assert\Length(max: 500)]
    private ?string $filePath = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否有效'])]
    #[Assert\Type(type: 'bool')]
    #[IndexColumn]
    private bool $isActive = true;

    #[ORM\Column(enumType: SupplierQualificationStatus::class, options: ['comment' => '状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [SupplierQualificationStatus::class, 'cases'])]
    private SupplierQualificationStatus $status = SupplierQualificationStatus::DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '备注说明'])]
    #[Assert\Length(max: 2000)]
    private ?string $remarks = null;

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

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function getCertificateNumber(): string
    {
        return $this->certificateNumber;
    }

    public function setCertificateNumber(string $certificateNumber): void
    {
        $this->certificateNumber = $certificateNumber;
    }

    public function getIssuingAuthority(): string
    {
        return $this->issuingAuthority;
    }

    public function setIssuingAuthority(string $issuingAuthority): void
    {
        $this->issuingAuthority = $issuingAuthority;
    }

    public function getIssuedDate(): \DateTimeImmutable
    {
        return $this->issuedDate;
    }

    public function setIssuedDate(\DateTimeImmutable $issuedDate): void
    {
        $this->issuedDate = $issuedDate;
    }

    public function getExpiryDate(): \DateTimeImmutable
    {
        return $this->expiryDate;
    }

    public function setExpiryDate(\DateTimeImmutable $expiryDate): void
    {
        $this->expiryDate = $expiryDate;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(?string $filePath): void
    {
        $this->filePath = $filePath;
    }

    public function getIsActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): void
    {
        $this->isActive = $isActive;
    }

    public function getRemarks(): ?string
    {
        return $this->remarks;
    }

    public function setRemarks(?string $remarks): void
    {
        $this->remarks = $remarks;
    }

    public function getStatus(): SupplierQualificationStatus
    {
        return $this->status;
    }

    public function setStatus(SupplierQualificationStatus $status): void
    {
        $this->status = $status;
    }

    public function isExpired(): bool
    {
        return $this->expiryDate <= new \DateTimeImmutable('today');
    }

    public function getValidityDays(): int
    {
        return (int) $this->issuedDate->diff($this->expiryDate)->days;
    }

    public function isValid(): bool
    {
        return $this->isActive && !$this->isExpired() && SupplierQualificationStatus::APPROVED === $this->status;
    }

    public function getQualificationType(): string
    {
        return $this->type;
    }

    public function getDaysUntilExpiry(): int
    {
        $today = new \DateTimeImmutable('today');
        $days = $today->diff($this->expiryDate)->days;

        if (false === $days) {
            return 0;
        }

        return $this->expiryDate >= $today ? $days : -$days;
    }

    public function __toString(): string
    {
        return $this->name . ' (' . $this->certificateNumber . ')';
    }
}
