<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;

/**
 * @internal
 */
#[CoversClass(SupplierQualificationStatus::class)]
final class SupplierQualificationStatusTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(SupplierQualificationStatus::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = SupplierQualificationStatus::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(SupplierQualificationStatus::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'draft',
            'pending_review',
            'approved',
            'rejected',
            'expired',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('草稿', SupplierQualificationStatus::DRAFT->getLabel());
        $this->assertEquals('待审核', SupplierQualificationStatus::PENDING_REVIEW->getLabel());
        $this->assertEquals('已批准', SupplierQualificationStatus::APPROVED->getLabel());
        $this->assertEquals('已拒绝', SupplierQualificationStatus::REJECTED->getLabel());
        $this->assertEquals('已过期', SupplierQualificationStatus::EXPIRED->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = SupplierQualificationStatus::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(5, $choices);
        $this->assertEquals('draft', $choices['草稿']);
        $this->assertEquals('pending_review', $choices['待审核']);
        $this->assertEquals('approved', $choices['已批准']);
        $this->assertEquals('rejected', $choices['已拒绝']);
        $this->assertEquals('expired', $choices['已过期']);
    }

    public function testIsValid(): void
    {
        $this->assertTrue(SupplierQualificationStatus::APPROVED->isValid());
        $this->assertFalse(SupplierQualificationStatus::DRAFT->isValid());
        $this->assertFalse(SupplierQualificationStatus::PENDING_REVIEW->isValid());
        $this->assertFalse(SupplierQualificationStatus::REJECTED->isValid());
        $this->assertFalse(SupplierQualificationStatus::EXPIRED->isValid());
    }

    public function testToArray(): void
    {
        $array = SupplierQualificationStatus::APPROVED->toArray();
        $this->assertEquals(['value' => 'approved', 'label' => '已批准'], $array);

        $array = SupplierQualificationStatus::EXPIRED->toArray();
        $this->assertEquals(['value' => 'expired', 'label' => '已过期'], $array);
    }
}
