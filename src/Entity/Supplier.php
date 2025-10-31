<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SupplierManageBundle\Enum\ContractStatus;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Repository\SupplierRepository;

#[ORM\Entity(repositoryClass: SupplierRepository::class)]
#[ORM\Table(name: 'supplier', options: ['comment' => '供应商表'])]
class Supplier implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\Column(length: 255, options: ['comment' => '供应商名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[IndexColumn]
    private string $name;

    #[ORM\Column(length: 255, options: ['comment' => '法人名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $legalName;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '法人地址'])]
    #[Assert\NotBlank]
    #[Assert\Length]
    private string $legalAddress;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => '注册号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $registrationNumber;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => '税号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $taxNumber;

    #[ORM\Column(length: 50, nullable: true, options: ['comment' => '所属行业'])]
    #[Assert\Length(max: 50)]
    private ?string $industry = null;

    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '公司官网'])]
    #[Assert\Length(max: 255)]
    #[Assert\Url]
    private ?string $website = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '公司介绍'])]
    #[Assert\Length(max: 2000)]
    private ?string $introduction = null;

    #[ORM\Column(enumType: SupplierType::class, options: ['comment' => '供应商类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [SupplierType::class, 'cases'])]
    #[IndexColumn]
    private SupplierType $supplierType = SupplierType::SUPPLIER;

    #[ORM\Column(enumType: CooperationModel::class, nullable: true, options: ['comment' => '合作模式'])]
    #[Assert\Choice(callback: [CooperationModel::class, 'cases'])]
    private ?CooperationModel $cooperationModel = null;

    #[ORM\Column(length: 100, nullable: true, options: ['comment' => '业务类别'])]
    #[Assert\Length(max: 100)]
    private ?string $businessCategory = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否仓储'])]
    #[Assert\Type(type: 'bool')]
    private bool $isWarehouse = false;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '删除时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $deleteTime = null;

    #[ORM\Column(enumType: SupplierStatus::class, options: ['comment' => '状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [SupplierStatus::class, 'cases'])]
    #[IndexColumn]
    private SupplierStatus $status = SupplierStatus::DRAFT;

    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '版本号'])]
    private int $version = 1;

    /**
     * @var Collection<int, SupplierContact>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierContact::class, cascade: ['persist', 'remove'])]
    private Collection $contacts;

    /**
     * @var Collection<int, SupplierQualification>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: SupplierQualification::class, cascade: ['persist', 'remove'])]
    private Collection $qualifications;

    /**
     * @var Collection<int, Contract>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: Contract::class, cascade: ['persist', 'remove'])]
    private Collection $contracts;

    /**
     * @var Collection<int, PerformanceEvaluation>
     */
    #[ORM\OneToMany(mappedBy: 'supplier', targetEntity: PerformanceEvaluation::class, cascade: ['persist', 'remove'])]
    private Collection $performanceEvaluations;

    public function __construct()
    {
        $this->contacts = new ArrayCollection();
        $this->qualifications = new ArrayCollection();
        $this->contracts = new ArrayCollection();
        $this->performanceEvaluations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getLegalName(): string
    {
        return $this->legalName;
    }

    public function setLegalName(string $legalName): void
    {
        $this->legalName = $legalName;
    }

    public function getLegalAddress(): string
    {
        return $this->legalAddress;
    }

    public function setLegalAddress(string $legalAddress): void
    {
        $this->legalAddress = $legalAddress;
    }

    public function getRegistrationNumber(): string
    {
        return $this->registrationNumber;
    }

    public function setRegistrationNumber(string $registrationNumber): void
    {
        $this->registrationNumber = $registrationNumber;
    }

    public function getTaxNumber(): string
    {
        return $this->taxNumber;
    }

    public function setTaxNumber(string $taxNumber): void
    {
        $this->taxNumber = $taxNumber;
    }

    public function getSupplierType(): SupplierType
    {
        return $this->supplierType;
    }

    public function setSupplierType(SupplierType $supplierType): void
    {
        $this->supplierType = $supplierType;
    }

    public function getCooperationModel(): ?CooperationModel
    {
        return $this->cooperationModel;
    }

    public function setCooperationModel(?CooperationModel $cooperationModel): void
    {
        $this->cooperationModel = $cooperationModel;
    }

    public function getBusinessCategory(): ?string
    {
        return $this->businessCategory;
    }

    public function setBusinessCategory(?string $businessCategory): void
    {
        $this->businessCategory = $businessCategory;
    }

    public function getIsWarehouse(): bool
    {
        return $this->isWarehouse;
    }

    public function setIsWarehouse(bool $isWarehouse): void
    {
        $this->isWarehouse = $isWarehouse;
    }

    public function getStatus(): SupplierStatus
    {
        return $this->status;
    }

    public function setStatus(SupplierStatus $status): void
    {
        $this->status = $status;
    }

    public function getVersion(): int
    {
        return $this->version;
    }

    public function setVersion(int $version): void
    {
        $this->version = $version;
    }

    public function getIndustry(): ?string
    {
        return $this->industry;
    }

    public function setIndustry(?string $industry): void
    {
        $this->industry = $industry;
    }

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): void
    {
        $this->website = $website;
    }

    public function getIntroduction(): ?string
    {
        return $this->introduction;
    }

    public function setIntroduction(?string $introduction): void
    {
        $this->introduction = $introduction;
    }

    public function getDeleteTime(): ?\DateTimeImmutable
    {
        return $this->deleteTime;
    }

    public function setDeleteTime(?\DateTimeImmutable $deleteTime): void
    {
        $this->deleteTime = $deleteTime;
    }

    public function isDeleted(): bool
    {
        return null !== $this->deleteTime;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, SupplierContact>
     */
    public function getContacts(): Collection
    {
        return $this->contacts;
    }

    public function addContact(SupplierContact $contact): void
    {
        if (!$this->contacts->contains($contact)) {
            if ($contact->getIsPrimary()) {
                $this->ensureSinglePrimaryContact();
            }

            $this->contacts[] = $contact;
            $contact->setSupplier($this);
        }
    }

    private function ensureSinglePrimaryContact(): void
    {
        foreach ($this->contacts as $existingContact) {
            if ($existingContact->getIsPrimary()) {
                $existingContact->setIsPrimary(false);
            }
        }
    }

    public function removeContact(SupplierContact $contact): void
    {
        if ($this->contacts->removeElement($contact)) {
            // 由于 SupplierContact 必须始终有 supplier，这里不设置为 null
            // 在实际应用中，删除联系人应该通过 EntityManager 进行
        }
    }

    /**
     * 获取主要联系人
     */
    public function getPrimaryContact(): ?SupplierContact
    {
        foreach ($this->contacts as $contact) {
            if ($contact->getIsPrimary()) {
                return $contact;
            }
        }

        return null;
    }

    /**
     * @return Collection<int, SupplierQualification>
     */
    public function getQualifications(): Collection
    {
        return $this->qualifications;
    }

    public function addQualification(SupplierQualification $qualification): void
    {
        if (!$this->qualifications->contains($qualification)) {
            $this->qualifications[] = $qualification;
            $qualification->setSupplier($this);
        }
    }

    public function removeQualification(SupplierQualification $qualification): void
    {
        if ($this->qualifications->removeElement($qualification)) {
            // 由于 SupplierQualification 必须始终有 supplier，这里不设置为 null
            // 在实际应用中，删除资质应该通过 EntityManager 进行
        }
    }

    /**
     * 获取有效资质（已批准且未过期）
     *
     * @return Collection<int, SupplierQualification>
     */
    public function getValidQualifications(): Collection
    {
        return $this->qualifications->filter(fn (SupplierQualification $q) => $q->isValid());
    }

    /**
     * 获取过期资质
     *
     * @return Collection<int, SupplierQualification>
     */
    public function getExpiredQualifications(): Collection
    {
        return $this->qualifications->filter(fn (SupplierQualification $q) => $q->isExpired());
    }

    /**
     * 获取待审核资质
     *
     * @return Collection<int, SupplierQualification>
     */
    public function getPendingReviewQualifications(): Collection
    {
        return $this->qualifications->filter(
            fn (SupplierQualification $q) => SupplierQualificationStatus::PENDING_REVIEW === $q->getStatus()
        );
    }

    /**
     * 检查是否有特定类型的资质
     */
    public function hasQualificationType(string $type): bool
    {
        foreach ($this->qualifications as $qualification) {
            if ($qualification->getQualificationType() === $type && $qualification->isValid()) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取即将过期的资质（30天内）
     *
     * @return Collection<int, SupplierQualification>
     */
    public function getQualificationsExpiringSoon(int $days = 30): Collection
    {
        return $this->qualifications->filter(function (SupplierQualification $q) use ($days) {
            if (!$q->isValid()) {
                return false;
            }

            $daysUntilExpiry = $q->getDaysUntilExpiry();

            return $daysUntilExpiry <= $days && $daysUntilExpiry >= 0;
        });
    }

    /**
     * @return Collection<int, Contract>
     */
    public function getContracts(): Collection
    {
        return $this->contracts;
    }

    public function addContract(Contract $contract): void
    {
        if (!$this->contracts->contains($contract)) {
            $this->contracts[] = $contract;
            $contract->setSupplier($this);
        }
    }

    public function removeContract(Contract $contract): void
    {
        if ($this->contracts->removeElement($contract)) {
            // 由于 Contract 必须始终有 supplier，这里不设置为 null
            // 在实际应用中，删除合同应该通过 EntityManager 进行
        }
    }

    /**
     * 获取有效合同（已批准且未过期）
     *
     * @return Collection<int, Contract>
     */
    public function getActiveContracts(): Collection
    {
        return $this->contracts->filter(fn (Contract $c) => ContractStatus::APPROVED === $c->getStatus() && !$c->isExpired()
        );
    }

    /**
     * 获取过期合同
     *
     * @return Collection<int, Contract>
     */
    public function getExpiredContracts(): Collection
    {
        return $this->contracts->filter(fn (Contract $c) => $c->isExpired());
    }

    /**
     * 获取待审核合同
     *
     * @return Collection<int, Contract>
     */
    public function getPendingReviewContracts(): Collection
    {
        return $this->contracts->filter(
            fn (Contract $c) => ContractStatus::PENDING_REVIEW === $c->getStatus()
        );
    }

    /**
     * 获取即将到期的合同（30天内）
     *
     * @return Collection<int, Contract>
     */
    public function getContractsExpiringSoon(int $days = 30): Collection
    {
        return $this->contracts->filter(function (Contract $c) use ($days) {
            if (ContractStatus::APPROVED !== $c->getStatus()) {
                return false;
            }

            $daysUntilExpiry = $c->getDaysUntilExpiry();

            return $daysUntilExpiry <= $days && $daysUntilExpiry >= 0;
        });
    }

    /**
     * 获取指定类型的合同
     *
     * @return Collection<int, Contract>
     */
    public function getContractsByType(string $contractType): Collection
    {
        return $this->contracts->filter(fn (Contract $c) => $c->getContractType() === $contractType);
    }

    /**
     * 检查是否有有效合同
     */
    public function hasActiveContracts(): bool
    {
        $activeContracts = $this->getActiveContracts();

        return !$activeContracts->isEmpty();
    }

    /**
     * @return Collection<int, PerformanceEvaluation>
     */
    public function getPerformanceEvaluations(): Collection
    {
        return $this->performanceEvaluations;
    }

    public function addPerformanceEvaluation(PerformanceEvaluation $evaluation): void
    {
        if (!$this->performanceEvaluations->contains($evaluation)) {
            $this->performanceEvaluations[] = $evaluation;
            $evaluation->setSupplier($this);
        }
    }

    public function removePerformanceEvaluation(PerformanceEvaluation $evaluation): void
    {
        if ($this->performanceEvaluations->removeElement($evaluation)) {
            // 由于 PerformanceEvaluation 必须始终有 supplier，这里不设置为 null
            // 在实际应用中，删除绩效评估应该通过 EntityManager 进行
        }
    }

    /**
     * 获取已确认的绩效评估
     *
     * @return Collection<int, PerformanceEvaluation>
     */
    public function getConfirmedEvaluations(): Collection
    {
        return $this->performanceEvaluations->filter(
            fn (PerformanceEvaluation $e) => PerformanceEvaluationStatus::CONFIRMED === $e->getStatus()
        );
    }

    /**
     * 获取待审核的绩效评估
     *
     * @return Collection<int, PerformanceEvaluation>
     */
    public function getPendingReviewEvaluations(): Collection
    {
        return $this->performanceEvaluations->filter(
            fn (PerformanceEvaluation $e) => PerformanceEvaluationStatus::PENDING_REVIEW === $e->getStatus()
        );
    }

    /**
     * 获取指定周期的绩效评估
     *
     * @return Collection<int, PerformanceEvaluation>
     */
    public function getEvaluationsByPeriod(string $period): Collection
    {
        return $this->performanceEvaluations->filter(
            fn (PerformanceEvaluation $e) => $e->getEvaluationPeriod() === $period
        );
    }

    /**
     * 获取最新的绩效评估
     */
    public function getLatestEvaluation(): ?PerformanceEvaluation
    {
        $confirmedEvaluations = $this->getConfirmedEvaluations();
        if ($confirmedEvaluations->isEmpty()) {
            return null;
        }

        // 按评估日期降序排序
        $sorted = $confirmedEvaluations->toArray();
        usort($sorted, fn (PerformanceEvaluation $a, PerformanceEvaluation $b) => $b->getEvaluationDate() <=> $a->getEvaluationDate()
        );

        return $sorted[0];
    }

    /**
     * 计算平均得分
     */
    public function getAverageScore(): ?float
    {
        $confirmedEvaluations = $this->getConfirmedEvaluations();
        if ($confirmedEvaluations->isEmpty()) {
            return null;
        }

        $totalScore = 0;
        foreach ($confirmedEvaluations as $evaluation) {
            $totalScore += $evaluation->getOverallScore();
        }

        return $totalScore / $confirmedEvaluations->count();
    }

    /**
     * 获取最新的等级
     */
    public function getLatestGrade(): ?PerformanceGrade
    {
        $latestEvaluation = $this->getLatestEvaluation();

        return null !== $latestEvaluation ? $latestEvaluation->getGrade() : null;
    }

    /**
     * 检查是否有绩效评估
     */
    public function hasPerformanceEvaluations(): bool
    {
        return !$this->performanceEvaluations->isEmpty();
    }
}
