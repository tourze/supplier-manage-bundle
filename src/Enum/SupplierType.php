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
 * 供应商类型枚举
 */
enum SupplierType: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case SUPPLIER = 'supplier';
    case MERCHANT = 'merchant';

    /**
     * 获取所有类型选项
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            '供应商' => self::SUPPLIER->value,
            '商户' => self::MERCHANT->value,
        ];
    }

    /**
     * 获取类型标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::SUPPLIER => '供应商',
            self::MERCHANT => '商户',
        };
    }

    /**
     * 获取类型徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::SUPPLIER => self::PRIMARY,
            self::MERCHANT => self::INFO,
        };
    }
}
