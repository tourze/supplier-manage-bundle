<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;

/**
 * @internal
 */
#[CoversClass(EvaluationItemType::class)]
final class EvaluationItemTypeTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(EvaluationItemType::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = EvaluationItemType::cases();

        $this->assertCount(2, $cases);
        $this->assertContainsOnlyInstancesOf(EvaluationItemType::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'quantitative',
            'qualitative',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('定量评估', EvaluationItemType::QUANTITATIVE->getLabel());
        $this->assertEquals('定性评估', EvaluationItemType::QUALITATIVE->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = EvaluationItemType::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(2, $choices);
        $this->assertEquals('quantitative', $choices['定量评估']);
        $this->assertEquals('qualitative', $choices['定性评估']);
    }

    public function testIsQuantitative(): void
    {
        $this->assertTrue(EvaluationItemType::QUANTITATIVE->isQuantitative());
        $this->assertFalse(EvaluationItemType::QUALITATIVE->isQuantitative());
    }

    public function testIsQualitative(): void
    {
        $this->assertTrue(EvaluationItemType::QUALITATIVE->isQualitative());
        $this->assertFalse(EvaluationItemType::QUANTITATIVE->isQualitative());
    }

    public function testToArray(): void
    {
        $array = EvaluationItemType::QUANTITATIVE->toArray();
        $this->assertEquals(['value' => 'quantitative', 'label' => '定量评估'], $array);

        $array = EvaluationItemType::QUALITATIVE->toArray();
        $this->assertEquals(['value' => 'qualitative', 'label' => '定性评估'], $array);
    }
}
