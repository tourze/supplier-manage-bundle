<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 绩效等级枚举
 */
enum PerformanceGrade: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case A = 'A';
    case B = 'B';
    case C = 'C';
    case D = 'D';
    case E = 'E';

    /**
     * 获取所有等级选项
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            '优秀' => self::A->value,
            '良好' => self::B->value,
            '一般' => self::C->value,
            '较差' => self::D->value,
            '极差' => self::E->value,
        ];
    }

    /**
     * 获取等级标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::A => '优秀',
            self::B => '良好',
            self::C => '一般',
            self::D => '较差',
            self::E => '极差',
        };
    }

    /**
     * 根据分数获取等级
     */
    public static function fromScore(float $score): self
    {
        return match (true) {
            $score >= 90 => self::A,
            $score >= 80 => self::B,
            $score >= 70 => self::C,
            $score >= 60 => self::D,
            default => self::E,
        };
    }

    /**
     * 获取等级徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::A => self::SUCCESS,
            self::B => self::SUCCESS,
            self::C => self::WARNING,
            self::D => self::WARNING,
            self::E => self::DANGER,
        };
    }
}
