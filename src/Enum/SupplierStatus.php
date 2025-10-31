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
 * 供应商状态枚举
 */
enum SupplierStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case SUSPENDED = 'suspended';
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
            '已拒绝' => self::REJECTED->value,
            '已暂停' => self::SUSPENDED->value,
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
            self::REJECTED => '已拒绝',
            self::SUSPENDED => '已暂停',
            self::TERMINATED => '已终止',
        };
    }

    /**
     * 判断是否为有效状态
     */
    public function isActive(): bool
    {
        return in_array($this, [self::APPROVED], true);
    }

    /**
     * 获取状态徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => self::SECONDARY,
            self::PENDING_REVIEW => self::WARNING,
            self::APPROVED => self::SUCCESS,
            self::REJECTED => self::DANGER,
            self::SUSPENDED => self::WARNING,
            self::TERMINATED => self::DANGER,
        };
    }
}
