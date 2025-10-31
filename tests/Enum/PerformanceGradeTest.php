<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;

/**
 * @internal
 */
#[CoversClass(PerformanceGrade::class)]
final class PerformanceGradeTest extends AbstractEnumTestCase
{
    public function testEnumImplementsRequiredInterfaces(): void
    {
        $reflection = new \ReflectionEnum(PerformanceGrade::class);

        $this->assertTrue($reflection->implementsInterface(Itemable::class));
        $this->assertTrue($reflection->implementsInterface(Labelable::class));
        $this->assertTrue($reflection->implementsInterface(Selectable::class));
    }

    public function testEnumCases(): void
    {
        $cases = PerformanceGrade::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(PerformanceGrade::class, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);
        $expectedValues = [
            'A',
            'B',
            'C',
            'D',
            'E',
        ];
        $this->assertEquals($expectedValues, $values);
    }

    public function testGetLabel(): void
    {
        $this->assertEquals('优秀', PerformanceGrade::A->getLabel());
        $this->assertEquals('良好', PerformanceGrade::B->getLabel());
        $this->assertEquals('一般', PerformanceGrade::C->getLabel());
        $this->assertEquals('较差', PerformanceGrade::D->getLabel());
        $this->assertEquals('极差', PerformanceGrade::E->getLabel());
    }

    public function testGetChoices(): void
    {
        $choices = PerformanceGrade::getChoices();

        $this->assertIsArray($choices);
        $this->assertCount(5, $choices);
        $this->assertEquals('A', $choices['优秀']);
        $this->assertEquals('B', $choices['良好']);
        $this->assertEquals('C', $choices['一般']);
        $this->assertEquals('D', $choices['较差']);
        $this->assertEquals('E', $choices['极差']);
    }

    #[DataProvider('scoreProvider')]
    public function testFromScore(float $score, PerformanceGrade $expectedGrade): void
    {
        $this->assertSame($expectedGrade, PerformanceGrade::fromScore($score));
    }

    /**
     * @return array<int, array{float, PerformanceGrade}>
     */
    public static function scoreProvider(): array
    {
        return [
            [100.0, PerformanceGrade::A],
            [95.0, PerformanceGrade::A],
            [90.0, PerformanceGrade::A],
            [89.0, PerformanceGrade::B],
            [85.0, PerformanceGrade::B],
            [80.0, PerformanceGrade::B],
            [79.0, PerformanceGrade::C],
            [75.0, PerformanceGrade::C],
            [70.0, PerformanceGrade::C],
            [69.0, PerformanceGrade::D],
            [65.0, PerformanceGrade::D],
            [60.0, PerformanceGrade::D],
            [59.0, PerformanceGrade::E],
            [50.0, PerformanceGrade::E],
            [0.0, PerformanceGrade::E],
        ];
    }

    public function testToArray(): void
    {
        $array = PerformanceGrade::A->toArray();
        $this->assertEquals(['value' => 'A', 'label' => '优秀'], $array);

        $array = PerformanceGrade::E->toArray();
        $this->assertEquals(['value' => 'E', 'label' => '极差'], $array);
    }
}
