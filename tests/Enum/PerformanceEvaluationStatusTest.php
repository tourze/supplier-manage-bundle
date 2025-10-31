<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;

/**
 * @internal
 */
#[CoversClass(PerformanceEvaluationStatus::class)]
final class PerformanceEvaluationStatusTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(PerformanceEvaluationStatus::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = PerformanceEvaluationStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContainsOnlyInstancesOf(PerformanceEvaluationStatus::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'draft',
            'pending_review',
            'confirmed',
            'rejected',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('草稿', PerformanceEvaluationStatus::DRAFT->getLabel());
        $this->assertEquals('待审核', PerformanceEvaluationStatus::PENDING_REVIEW->getLabel());
        $this->assertEquals('已确认', PerformanceEvaluationStatus::CONFIRMED->getLabel());
        $this->assertEquals('已拒绝', PerformanceEvaluationStatus::REJECTED->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = PerformanceEvaluationStatus::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(4, $choices);
        $this->assertEquals('draft', $choices['草稿']);
        $this->assertEquals('pending_review', $choices['待审核']);
        $this->assertEquals('confirmed', $choices['已确认']);
        $this->assertEquals('rejected', $choices['已拒绝']);
    }

    public function testIsEditable(): void
    {
        $this->assertTrue(PerformanceEvaluationStatus::DRAFT->isEditable());
        $this->assertTrue(PerformanceEvaluationStatus::REJECTED->isEditable());
        $this->assertFalse(PerformanceEvaluationStatus::PENDING_REVIEW->isEditable());
        $this->assertFalse(PerformanceEvaluationStatus::CONFIRMED->isEditable());
    }

    public function testIsCompleted(): void
    {
        $this->assertTrue(PerformanceEvaluationStatus::CONFIRMED->isCompleted());
        $this->assertFalse(PerformanceEvaluationStatus::DRAFT->isCompleted());
        $this->assertFalse(PerformanceEvaluationStatus::PENDING_REVIEW->isCompleted());
        $this->assertFalse(PerformanceEvaluationStatus::REJECTED->isCompleted());
    }

    public function testToArray(): void
    {
        $array = PerformanceEvaluationStatus::DRAFT->toArray();
        $this->assertEquals(['value' => 'draft', 'label' => '草稿'], $array);

        $array = PerformanceEvaluationStatus::CONFIRMED->toArray();
        $this->assertEquals(['value' => 'confirmed', 'label' => '已确认'], $array);
    }
}
