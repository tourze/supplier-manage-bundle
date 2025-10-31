<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;

/**
 * @internal
 */
#[CoversClass(SupplierStatus::class)]
final class SupplierStatusTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(SupplierStatus::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = SupplierStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertContainsOnlyInstancesOf(SupplierStatus::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'suspended',
            'terminated',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('草稿', SupplierStatus::DRAFT->getLabel());
        $this->assertEquals('待审核', SupplierStatus::PENDING_REVIEW->getLabel());
        $this->assertEquals('已批准', SupplierStatus::APPROVED->getLabel());
        $this->assertEquals('已拒绝', SupplierStatus::REJECTED->getLabel());
        $this->assertEquals('已暂停', SupplierStatus::SUSPENDED->getLabel());
        $this->assertEquals('已终止', SupplierStatus::TERMINATED->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = SupplierStatus::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(6, $choices);
        $this->assertEquals('draft', $choices['草稿']);
        $this->assertEquals('pending_review', $choices['待审核']);
        $this->assertEquals('approved', $choices['已批准']);
        $this->assertEquals('rejected', $choices['已拒绝']);
        $this->assertEquals('suspended', $choices['已暂停']);
        $this->assertEquals('terminated', $choices['已终止']);
    }

    public function testIsActive(): void
    {
        $this->assertTrue(SupplierStatus::APPROVED->isActive());
        $this->assertFalse(SupplierStatus::DRAFT->isActive());
        $this->assertFalse(SupplierStatus::PENDING_REVIEW->isActive());
        $this->assertFalse(SupplierStatus::REJECTED->isActive());
        $this->assertFalse(SupplierStatus::SUSPENDED->isActive());
        $this->assertFalse(SupplierStatus::TERMINATED->isActive());
    }

    public function testToArray(): void
    {
        $array = SupplierStatus::APPROVED->toArray();
        $this->assertEquals(['value' => 'approved', 'label' => '已批准'], $array);

        $array = SupplierStatus::SUSPENDED->toArray();
        $this->assertEquals(['value' => 'suspended', 'label' => '已暂停'], $array);
    }
}
