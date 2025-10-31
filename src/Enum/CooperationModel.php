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
 * 合作模式枚举
 */
enum CooperationModel: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DISTRIBUTION = 'distribution';
    case CONSIGNMENT = 'consignment';
    case JOINT_VENTURE = 'jointventure';

    /**
     * 获取所有合作模式选项
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            '分销' => self::DISTRIBUTION->value,
            '代销' => self::CONSIGNMENT->value,
            '合资' => self::JOINT_VENTURE->value,
        ];
    }

    /**
     * 获取合作模式标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DISTRIBUTION => '分销',
            self::CONSIGNMENT => '代销',
            self::JOINT_VENTURE => '合资',
        };
    }

    /**
     * 获取合作模式徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DISTRIBUTION => self::PRIMARY,
            self::CONSIGNMENT => self::INFO,
            self::JOINT_VENTURE => self::SUCCESS,
        };
    }
}
