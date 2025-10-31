<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;

class EvaluationItemFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // 使用引用而不是查询数据库
        $evaluation1 = $this->getReference(PerformanceEvaluationFixtures::EVALUATION_1_REFERENCE, PerformanceEvaluation::class);
        $evaluation2 = $this->getReference(PerformanceEvaluationFixtures::EVALUATION_2_REFERENCE, PerformanceEvaluation::class);
        $evaluation3 = $this->getReference(PerformanceEvaluationFixtures::EVALUATION_3_REFERENCE, PerformanceEvaluation::class);

        $evaluations = [$evaluation1, $evaluation2, $evaluation3];

        $itemTemplates = [
            [
                'itemName' => '产品质量',
                'itemType' => EvaluationItemType::QUANTITATIVE,
                'weight' => 30.0,
                'score' => 88.5,
                'maxScore' => 100.0,
                'unit' => '分',
                'description' => '产品质量合格率和客户满意度',
            ],
            [
                'itemName' => '交付准时率',
                'itemType' => EvaluationItemType::QUANTITATIVE,
                'weight' => 25.0,
                'score' => 92.0,
                'maxScore' => 100.0,
                'unit' => '%',
                'description' => '按时交付订单的比例',
            ],
            [
                'itemName' => '服务响应',
                'itemType' => EvaluationItemType::QUALITATIVE,
                'weight' => 20.0,
                'score' => 8.5,
                'maxScore' => 10.0,
                'unit' => '分',
                'description' => '客户服务响应速度和态度',
            ],
            [
                'itemName' => '成本控制',
                'itemType' => EvaluationItemType::QUANTITATIVE,
                'weight' => 15.0,
                'score' => 75.0,
                'maxScore' => 100.0,
                'unit' => '分',
                'description' => '成本控制能力和价格竞争力',
            ],
            [
                'itemName' => '创新能力',
                'itemType' => EvaluationItemType::QUALITATIVE,
                'weight' => 10.0,
                'score' => 7.0,
                'maxScore' => 10.0,
                'unit' => '分',
                'description' => '技术创新和改进建议能力',
            ],
        ];

        foreach ($evaluations as $evaluation) {
            foreach ($itemTemplates as $template) {
                $item = new EvaluationItem();
                $item->setEvaluation($evaluation);
                $item->setItemName($template['itemName']);
                $item->setItemType($template['itemType']);
                $item->setWeight($template['weight']);
                $item->setScore($template['score'] + rand(-10, 10));
                $item->setMaxScore($template['maxScore']);
                $item->setUnit($template['unit']);
                $item->setDescription($template['description']);

                $manager->persist($item);
            }
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            PerformanceEvaluationFixtures::class,
        ];
    }
}
