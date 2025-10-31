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
 * 评估项类型枚举
 */
enum EvaluationItemType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case QUANTITATIVE = 'quantitative';
    case QUALITATIVE = 'qualitative';

    /**
     * 获取所有类型选项
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            '定量评估' => self::QUANTITATIVE->value,
            '定性评估' => self::QUALITATIVE->value,
        ];
    }

    /**
     * 获取类型标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::QUANTITATIVE => '定量评估',
            self::QUALITATIVE => '定性评估',
        };
    }

    /**
     * 判断是否为定量评估
     */
    public function isQuantitative(): bool
    {
        return self::QUANTITATIVE === $this;
    }

    /**
     * 判断是否为定性评估
     */
    public function isQualitative(): bool
    {
        return self::QUALITATIVE === $this;
    }

    /**
     * 获取类型徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::QUANTITATIVE => self::PRIMARY,
            self::QUALITATIVE => self::INFO,
        };
    }
}
