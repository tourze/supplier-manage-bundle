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
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;
use Tourze\SupplierManageBundle\Repository\PerformanceEvaluationRepository;

#[ORM\Entity(repositoryClass: PerformanceEvaluationRepository::class)]
#[ORM\Table(name: 'supplier_performance_evaluation', options: ['comment' => '供应商绩效评估表'])]
class PerformanceEvaluation implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'performanceEvaluations')]
    #[ORM\JoinColumn(name: 'supplier_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private Supplier $supplier;

    #[ORM\Column(length: 100, unique: true, options: ['comment' => '评估编号'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[IndexColumn]
    private string $evaluationNumber;

    #[ORM\Column(length: 255, options: ['comment' => '评估标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $title;

    #[ORM\Column(length: 50, options: ['comment' => '评估周期'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $evaluationPeriod;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, options: ['comment' => '评估日期'])]
    #[Assert\NotBlank]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    #[IndexColumn]
    private \DateTimeImmutable $evaluationDate;

    #[ORM\Column(length: 100, options: ['comment' => '评估人'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $evaluator;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '综合得分'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 100)]
    #[Assert\Length(max: 6)]
    private string $overallScore;

    #[ORM\Column(enumType: PerformanceGrade::class, options: ['comment' => '等级'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PerformanceGrade::class, 'cases'])]
    private PerformanceGrade $grade;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '评估总结'])]
    #[Assert\Length(max: 2000)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '改进建议'])]
    #[Assert\Length(max: 2000)]
    private ?string $improvementSuggestions = null;

    #[ORM\Column(enumType: PerformanceEvaluationStatus::class, options: ['comment' => '状态'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [PerformanceEvaluationStatus::class, 'cases'])]
    #[IndexColumn]
    private PerformanceEvaluationStatus $status = PerformanceEvaluationStatus::DRAFT;

    /**
     * @var Collection<int, EvaluationItem>
     */
    #[ORM\OneToMany(mappedBy: 'evaluation', targetEntity: EvaluationItem::class, cascade: ['persist', 'remove'])]
    private Collection $evaluationItems;

    public function __construct()
    {
        $this->evaluationItems = new ArrayCollection();
    }

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

    public function getEvaluationNumber(): string
    {
        return $this->evaluationNumber;
    }

    public function setEvaluationNumber(string $evaluationNumber): void
    {
        $this->evaluationNumber = $evaluationNumber;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getEvaluationPeriod(): string
    {
        return $this->evaluationPeriod;
    }

    public function setEvaluationPeriod(string $evaluationPeriod): void
    {
        $this->evaluationPeriod = $evaluationPeriod;
    }

    public function getEvaluationDate(): \DateTimeImmutable
    {
        return $this->evaluationDate;
    }

    public function setEvaluationDate(\DateTimeImmutable $evaluationDate): void
    {
        $this->evaluationDate = $evaluationDate;
    }

    public function getEvaluator(): string
    {
        return $this->evaluator;
    }

    public function setEvaluator(string $evaluator): void
    {
        $this->evaluator = $evaluator;
    }

    public function getOverallScore(): float
    {
        return (float) $this->overallScore;
    }

    public function setOverallScore(float $overallScore): void
    {
        $this->overallScore = (string) $overallScore;
    }

    public function getGrade(): PerformanceGrade
    {
        return $this->grade;
    }

    public function setGrade(PerformanceGrade $grade): void
    {
        $this->grade = $grade;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getImprovementSuggestions(): ?string
    {
        return $this->improvementSuggestions;
    }

    public function setImprovementSuggestions(?string $improvementSuggestions): void
    {
        $this->improvementSuggestions = $improvementSuggestions;
    }

    public function getStatus(): PerformanceEvaluationStatus
    {
        return $this->status;
    }

    public function setStatus(PerformanceEvaluationStatus $status): void
    {
        $this->status = $status;
    }

    /**
     * 根据得分计算等级
     */
    public function calculateGrade(): PerformanceGrade
    {
        $score = $this->getOverallScore();

        return PerformanceGrade::fromScore($score);
    }

    /**
     * 检查评估是否已完成
     */
    public function isCompleted(): bool
    {
        return $this->status->isCompleted();
    }

    /**
     * 检查评估是否已批准
     */
    public function isApproved(): bool
    {
        return PerformanceEvaluationStatus::CONFIRMED === $this->status;
    }

    /**
     * 检查评估是否被拒绝
     */
    public function isRejected(): bool
    {
        return PerformanceEvaluationStatus::REJECTED === $this->status;
    }

    /**
     * @return Collection<int, EvaluationItem>
     */
    public function getEvaluationItems(): Collection
    {
        return $this->evaluationItems;
    }

    public function addEvaluationItem(EvaluationItem $item): void
    {
        if (!$this->evaluationItems->contains($item)) {
            $this->evaluationItems[] = $item;
            $item->setEvaluation($this);
        }
    }

    public function removeEvaluationItem(EvaluationItem $item): void
    {
        if ($this->evaluationItems->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getEvaluation() === $this) {
                // Note: EvaluationItem::setEvaluation() method signature needs to allow null
                // This will be handled when we implement EvaluationItem entity
            }
        }
    }

    /**
     * 计算实际综合得分（基于评估项）
     */
    public function calculateActualScore(): float
    {
        if ($this->evaluationItems->isEmpty()) {
            return 0.0;
        }

        $totalWeightedScore = 0.0;
        foreach ($this->evaluationItems as $item) {
            $totalWeightedScore += $item->getWeightedScore();
        }

        return $totalWeightedScore;
    }

    /**
     * 验证评估项权重总和是否为100%
     */
    public function validateWeightsTotal(): bool
    {
        if ($this->evaluationItems->isEmpty()) {
            return false;
        }

        $totalWeight = 0.0;
        foreach ($this->evaluationItems as $item) {
            $totalWeight += $item->getWeight();
        }

        // 允许有小的误差
        return abs($totalWeight - 100.0) < 0.01;
    }

    /**
     * 获取权重总和
     */
    public function getTotalWeight(): float
    {
        $totalWeight = 0.0;
        foreach ($this->evaluationItems as $item) {
            $totalWeight += $item->getWeight();
        }

        return $totalWeight;
    }

    /**
     * 获取定量评估项
     *
     * @return Collection<int, EvaluationItem>
     */
    public function getQuantitativeItems(): Collection
    {
        return $this->evaluationItems->filter(
            fn (EvaluationItem $item) => $item->isQuantitative()
        );
    }

    /**
     * 获取定性评估项
     *
     * @return Collection<int, EvaluationItem>
     */
    public function getQualitativeItems(): Collection
    {
        return $this->evaluationItems->filter(
            fn (EvaluationItem $item) => $item->isQualitative()
        );
    }

    public function __toString(): string
    {
        return $this->evaluationNumber . ' - ' . $this->title;
    }
}
