<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SupplierManageBundle\Enum\ContractStatus;
use Tourze\SupplierManageBundle\Exception\InvalidContractAmountException;
use Tourze\SupplierManageBundle\Exception\InvalidContractDateRangeException;
use Tourze\SupplierManageBundle\Repository\ContractRepository;

#[ORM\Entity(repositoryClass: ContractRepository::class)]
#[ORM\Table(name: 'supplier_contract', options: ['comment' => '供应商合同表'])]
#[ORM\Index(name: 'supplier_contract_idx_supplier_contract_dates', columns: ['start_date', 'end_date'])]
class Contract implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'contracts')]
    #[ORM\JoinColumn(name: 'supplier_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Supplier $supplier;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => '合同编号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[IndexColumn]
    private string $contractNumber;

    #[ORM\Column(length: 255, options: ['comment' => '合同标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(length: 50, options: ['comment' => '合同类型'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    #[Assert\Choice(choices: ['supply', 'service', 'purchase', 'lease', 'other'])]
    private string $contractType;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '合同开始日期'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $startDate;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '合同结束日期'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $endDate;

    #[ORM\Column(type: Types::DECIMAL, precision: 15, scale: 2, options: ['comment' => '合同金额'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private float $amount;

    #[ORM\Column(length: 3, options: ['comment' => '币种'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 3)]
    #[Assert\Currency]
    private string $currency = 'CNY';

    #[ORM\Column(enumType: ContractStatus::class, options: ['comment' => '合同状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [ContractStatus::class, 'cases'])]
    #[IndexColumn]
    private ContractStatus $status = ContractStatus::DRAFT;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '合同描述'])]
    #[Assert\Length(max: 5000)]
    private ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '合同条款'])]
    #[Assert\Length(max: 10000)]
    private ?string $terms = null;

    /**
     * @var array<int, array{old_amount: float, new_amount: float, reason: string, changed_at: \DateTimeImmutable}>
     */
    #[ORM\Column(type: Types::JSON, options: ['comment' => '金额变更历史'])]
    #[Assert\Type(type: 'array')]
    private array $amountChangeHistory = [];

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

    public function getContractNumber(): string
    {
        return $this->contractNumber;
    }

    public function setContractNumber(string $contractNumber): void
    {
        $this->contractNumber = $contractNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getContractType(): string
    {
        return $this->contractType;
    }

    public function setContractType(string $contractType): void
    {
        $this->contractType = $contractType;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): void
    {
        $this->startDate = $startDate;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): void
    {
        $this->endDate = $endDate;
    }

    public function getAmount(): float
    {
        return $this->amount;
    }

    public function setAmount(float $amount): void
    {
        if ($amount < 0) {
            throw new InvalidContractAmountException();
        }

        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getStatus(): ContractStatus
    {
        return $this->status;
    }

    public function setStatus(ContractStatus $status): void
    {
        $this->status = $status;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getTerms(): ?string
    {
        return $this->terms;
    }

    public function setTerms(?string $terms): void
    {
        $this->terms = $terms;
    }

    /**
     * @return array<int, array{old_amount: float, new_amount: float, reason: string, changed_at: \DateTimeImmutable}>
     */
    public function getAmountChangeHistory(): array
    {
        return $this->amountChangeHistory;
    }

    public function recordAmountChange(float $newAmount, string $reason): void
    {
        $this->amountChangeHistory[] = [
            'old_amount' => $this->amount,
            'new_amount' => $newAmount,
            'reason' => $reason,
            'changed_at' => new \DateTimeImmutable(),
        ];

        $this->amount = $newAmount;
    }

    public function isDateRangeValid(): bool
    {
        return $this->startDate <= $this->endDate;
    }

    public function validateDateRange(): void
    {
        if (!$this->isDateRangeValid()) {
            throw new InvalidContractDateRangeException();
        }
    }

    public function getDurationInDays(): int
    {
        return (int) $this->startDate->diff($this->endDate)->days;
    }

    public function isExpired(): bool
    {
        return $this->endDate <= new \DateTimeImmutable('today');
    }

    public function isCurrentlyActive(): bool
    {
        $now = new \DateTimeImmutable();

        return $this->startDate <= $now && $this->endDate >= $now;
    }

    public function getDaysUntilExpiry(): int
    {
        $now = new \DateTimeImmutable();
        $interval = $now->diff($this->endDate);

        return 1 === $interval->invert ? -(int) $interval->days : (int) $interval->days;
    }

    public function __toString(): string
    {
        return $this->contractNumber . ' - ' . $this->title;
    }
}
