<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;
use Tourze\SupplierManageBundle\Repository\EvaluationItemRepository;

#[ORM\Entity(repositoryClass: EvaluationItemRepository::class)]
#[ORM\Table(name: 'supplier_evaluation_item', options: ['comment' => '供应商评估项表'])]
class EvaluationItem implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: PerformanceEvaluation::class, inversedBy: 'evaluationItems')]
    #[ORM\JoinColumn(name: 'evaluation_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private PerformanceEvaluation $evaluation;

    #[ORM\Column(length: 255, options: ['comment' => '指标名称'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $itemName;

    #[ORM\Column(enumType: EvaluationItemType::class, options: ['comment' => '指标类型'])]
    #[Assert\NotNull]
    #[Assert\Choice(callback: [EvaluationItemType::class, 'cases'])]
    #[IndexColumn]
    private EvaluationItemType $itemType = EvaluationItemType::QUANTITATIVE;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, options: ['comment' => '权重(%)'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0, max: 100)]
    #[Assert\Length(max: 8)]
    private string $weight;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, options: ['comment' => '得分'])]
    #[Assert\NotBlank]
    #[Assert\Range(min: 0)]
    #[Assert\Length(max: 11)]
    private string $score;

    #[ORM\Column(type: Types::DECIMAL, precision: 8, scale: 2, options: ['comment' => '最大分值'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    #[Assert\Length(max: 11)]
    private string $maxScore;

    #[ORM\Column(length: 20, nullable: true, options: ['comment' => '单位'])]
    #[Assert\Length(max: 20)]
    private ?string $unit = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    #[Assert\Length(max: 1000)]
    private ?string $description = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEvaluation(): PerformanceEvaluation
    {
        return $this->evaluation;
    }

    public function setEvaluation(PerformanceEvaluation $evaluation): void
    {
        $this->evaluation = $evaluation;
    }

    public function getItemName(): string
    {
        return $this->itemName;
    }

    public function setItemName(string $itemName): void
    {
        $this->itemName = $itemName;
    }

    public function getItemType(): EvaluationItemType
    {
        return $this->itemType;
    }

    public function setItemType(EvaluationItemType $itemType): void
    {
        $this->itemType = $itemType;
    }

    public function getWeight(): float
    {
        return (float) $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = (string) $weight;
    }

    public function getScore(): float
    {
        return (float) $this->score;
    }

    public function setScore(float $score): void
    {
        $this->score = (string) $score;
    }

    public function getMaxScore(): float
    {
        return (float) $this->maxScore;
    }

    public function setMaxScore(float $maxScore): void
    {
        $this->maxScore = (string) $maxScore;
    }

    public function getUnit(): ?string
    {
        return $this->unit;
    }

    public function setUnit(?string $unit): void
    {
        $this->unit = $unit;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * 获取加权得分
     */
    public function getWeightedScore(): float
    {
        $maxScore = $this->getMaxScore();
        if (0.0 === $maxScore) {
            return 0.0;
        }

        $scorePercentage = $this->getScore() / $maxScore;

        return $scorePercentage * $this->getWeight();
    }

    /**
     * 获取得分百分比
     */
    public function getScorePercentage(): float
    {
        $maxScore = $this->getMaxScore();
        if (0.0 === $maxScore) {
            return 0.0;
        }

        return ($this->getScore() / $maxScore) * 100.0;
    }

    /**
     * 检查是否为定量指标
     */
    public function isQuantitative(): bool
    {
        return $this->itemType->isQuantitative();
    }

    /**
     * 检查是否为定性指标
     */
    public function isQualitative(): bool
    {
        return $this->itemType->isQualitative();
    }

    public function __toString(): string
    {
        return $this->itemName . ' (' . $this->weight . '%)';
    }
}
