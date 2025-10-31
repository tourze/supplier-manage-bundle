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
 * 合同状态枚举
 */
enum ContractStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_approval';
    case APPROVED = 'approved';
    case ACTIVE = 'active';
    case COMPLETED = 'completed';
    case TERMINATED = 'terminated';

    /**
     * 获取所有状态选项
     *
     * @return array<string, string>
     */
    public static function getChoices(): array
    {
        return [
            '草稿' => self::DRAFT->value,
            '待审核' => self::PENDING_REVIEW->value,
            '已批准' => self::APPROVED->value,
            '生效中' => self::ACTIVE->value,
            '已完成' => self::COMPLETED->value,
            '已终止' => self::TERMINATED->value,
        ];
    }

    /**
     * 获取状态标签
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::DRAFT => '草稿',
            self::PENDING_REVIEW => '待审核',
            self::APPROVED => '已批准',
            self::ACTIVE => '生效中',
            self::COMPLETED => '已完成',
            self::TERMINATED => '已终止',
        };
    }

    /**
     * 获取状态徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => self::SECONDARY,
            self::PENDING_REVIEW => self::WARNING,
            self::APPROVED => self::INFO,
            self::ACTIVE => self::SUCCESS,
            self::COMPLETED => self::SUCCESS,
            self::TERMINATED => self::DANGER,
        };
    }
}
