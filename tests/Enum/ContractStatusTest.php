<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\ContractStatus;

/**
 * @internal
 */
#[CoversClass(ContractStatus::class)]
final class ContractStatusTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(ContractStatus::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = ContractStatus::cases();

        $this->assertCount(6, $cases);
        $this->assertContainsOnlyInstancesOf(ContractStatus::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'draft',
            'pending_approval',
            'approved',
            'active',
            'completed',
            'terminated',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('草稿', ContractStatus::DRAFT->getLabel());
        $this->assertEquals('待审核', ContractStatus::PENDING_REVIEW->getLabel());
        $this->assertEquals('已批准', ContractStatus::APPROVED->getLabel());
        $this->assertEquals('生效中', ContractStatus::ACTIVE->getLabel());
        $this->assertEquals('已完成', ContractStatus::COMPLETED->getLabel());
        $this->assertEquals('已终止', ContractStatus::TERMINATED->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = ContractStatus::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(6, $choices);
        $this->assertEquals('draft', $choices['草稿']);
        $this->assertEquals('pending_approval', $choices['待审核']);
        $this->assertEquals('approved', $choices['已批准']);
        $this->assertEquals('active', $choices['生效中']);
        $this->assertEquals('completed', $choices['已完成']);
        $this->assertEquals('terminated', $choices['已终止']);
    }

    public function testToArray(): void
    {
        $array = ContractStatus::DRAFT->toArray();
        $this->assertEquals(['value' => 'draft', 'label' => '草稿'], $array);

        $array = ContractStatus::ACTIVE->toArray();
        $this->assertEquals(['value' => 'active', 'label' => '生效中'], $array);
    }
}
