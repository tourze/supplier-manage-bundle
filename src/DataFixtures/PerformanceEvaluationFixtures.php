<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;

class PerformanceEvaluationFixtures extends Fixture implements DependentFixtureInterface
{
    public const EVALUATION_1_REFERENCE = 'evaluation-1';
    public const EVALUATION_2_REFERENCE = 'evaluation-2';
    public const EVALUATION_3_REFERENCE = 'evaluation-3';

    public function load(ObjectManager $manager): void
    {
        // 使用引用而不是查询数据库
        $supplier1 = $this->getReference(SupplierFixtures::SUPPLIER_A_REFERENCE, Supplier::class);
        $supplier2 = $this->getReference(SupplierFixtures::SUPPLIER_B_REFERENCE, Supplier::class);
        $supplier3 = $this->getReference(SupplierFixtures::SUPPLIER_C_REFERENCE, Supplier::class);

        $suppliers = [$supplier1, $supplier2, $supplier3];

        $evaluationData = [
            [
                'evaluationNumber' => 'EVAL-2024-001',
                'title' => '2024年第一季度绩效评估',
                'evaluationPeriod' => '2024-Q1',
                'evaluationDate' => new \DateTimeImmutable('2024-04-01'),
                'evaluator' => '张三',
                'overallScore' => 92.5,
                'grade' => PerformanceGrade::A,
                'summary' => '表现优秀，质量控制到位，交付及时。',
                'improvementSuggestions' => '建议进一步优化生产流程，提升效率。',
                'status' => PerformanceEvaluationStatus::CONFIRMED,
            ],
            [
                'evaluationNumber' => 'EVAL-2024-002',
                'title' => '2024年第二季度绩效评估',
                'evaluationPeriod' => '2024-Q2',
                'evaluationDate' => new \DateTimeImmutable('2024-07-01'),
                'evaluator' => '李四',
                'overallScore' => 78.0,
                'grade' => PerformanceGrade::C,
                'summary' => '整体表现一般，有几次延期交付。',
                'improvementSuggestions' => '需要改善项目管理和时间规划能力。',
                'status' => PerformanceEvaluationStatus::CONFIRMED,
            ],
            [
                'evaluationNumber' => 'EVAL-2024-003',
                'title' => '2024年第三季度绩效评估',
                'evaluationPeriod' => '2024-Q3',
                'evaluationDate' => new \DateTimeImmutable('2024-10-01'),
                'evaluator' => '王五',
                'overallScore' => 85.5,
                'grade' => PerformanceGrade::B,
                'summary' => '表现良好，质量有所提升。',
                'improvementSuggestions' => '继续保持当前水平，进一步提升服务质量。',
                'status' => PerformanceEvaluationStatus::PENDING_REVIEW,
            ],
            [
                'evaluationNumber' => 'EVAL-2024-004',
                'title' => '2024年度综合评估',
                'evaluationPeriod' => '2024年度',
                'evaluationDate' => new \DateTimeImmutable('2024-12-31'),
                'evaluator' => '赵六',
                'overallScore' => 95.0,
                'grade' => PerformanceGrade::A,
                'summary' => '年度表现卓越，是优秀的合作伙伴。',
                'improvementSuggestions' => '继续保持优秀水平，期待更深入的合作。',
                'status' => PerformanceEvaluationStatus::DRAFT,
            ],
            [
                'evaluationNumber' => 'EVAL-2024-005',
                'title' => '2024年服务质量评估',
                'evaluationPeriod' => '2024-H2',
                'evaluationDate' => new \DateTimeImmutable('2024-12-15'),
                'evaluator' => '孙七',
                'overallScore' => 68.5,
                'grade' => PerformanceGrade::D,
                'summary' => '服务质量需要改进，响应速度较慢。',
                'improvementSuggestions' => '建议加强客服培训，提升响应效率。',
                'status' => PerformanceEvaluationStatus::REJECTED,
            ],
        ];

        $supplierIndex = 0;
        foreach ($evaluationData as $data) {
            $evaluation = new PerformanceEvaluation();
            $evaluation->setSupplier($suppliers[$supplierIndex % count($suppliers)]);
            $evaluation->setEvaluationNumber($data['evaluationNumber']);
            $evaluation->setTitle($data['title']);
            $evaluation->setEvaluationPeriod($data['evaluationPeriod']);
            $evaluation->setEvaluationDate($data['evaluationDate']);
            $evaluation->setEvaluator($data['evaluator']);
            $evaluation->setOverallScore($data['overallScore']);
            $evaluation->setGrade($data['grade']);
            $evaluation->setSummary($data['summary']);
            $evaluation->setImprovementSuggestions($data['improvementSuggestions']);
            $evaluation->setStatus($data['status']);

            $manager->persist($evaluation);

            // 添加引用
            if (1 === $supplierIndex) {
                $this->addReference(self::EVALUATION_1_REFERENCE, $evaluation);
            } elseif (2 === $supplierIndex) {
                $this->addReference(self::EVALUATION_2_REFERENCE, $evaluation);
            } elseif (3 === $supplierIndex) {
                $this->addReference(self::EVALUATION_3_REFERENCE, $evaluation);
            }

            ++$supplierIndex;
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SupplierFixtures::class,
        ];
    }
}
