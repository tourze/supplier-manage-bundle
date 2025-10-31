<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\SupplierType;

/**
 * @internal
 */
#[CoversClass(SupplierType::class)]
final class SupplierTypeTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(SupplierType::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = SupplierType::cases();

        $this->assertCount(2, $cases);
        $this->assertContainsOnlyInstancesOf(SupplierType::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'supplier',
            'merchant',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('供应商', SupplierType::SUPPLIER->getLabel());
        $this->assertEquals('商户', SupplierType::MERCHANT->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = SupplierType::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(2, $choices);
        $this->assertEquals('supplier', $choices['供应商']);
        $this->assertEquals('merchant', $choices['商户']);
    }

    public function testToArray(): void
    {
        $array = SupplierType::SUPPLIER->toArray();
        $this->assertEquals(['value' => 'supplier', 'label' => '供应商'], $array);

        $array = SupplierType::MERCHANT->toArray();
        $this->assertEquals(['value' => 'merchant', 'label' => '商户'], $array);
    }
}
