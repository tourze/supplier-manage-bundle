<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\CooperationModel;

/**
 * @internal
 */
#[CoversClass(CooperationModel::class)]
final class CooperationModelTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(CooperationModel::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = CooperationModel::cases();

        $this->assertCount(3, $cases);
        $this->assertContainsOnlyInstancesOf(CooperationModel::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'distribution',
            'consignment',
            'jointventure',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('分销', CooperationModel::DISTRIBUTION->getLabel());
        $this->assertEquals('代销', CooperationModel::CONSIGNMENT->getLabel());
        $this->assertEquals('合资', CooperationModel::JOINT_VENTURE->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = CooperationModel::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(3, $choices);
        $this->assertEquals('distribution', $choices['分销']);
        $this->assertEquals('consignment', $choices['代销']);
        $this->assertEquals('jointventure', $choices['合资']);
    }

    public function testToArray(): void
    {
        $array = CooperationModel::DISTRIBUTION->toArray();
        $this->assertEquals(['value' => 'distribution', 'label' => '分销'], $array);

        $array = CooperationModel::CONSIGNMENT->toArray();
        $this->assertEquals(['value' => 'consignment', 'label' => '代销'], $array);
    }
}
