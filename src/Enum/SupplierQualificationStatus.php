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
 * 供应商资质状态枚举
 */
enum SupplierQualificationStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';

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
            '已过期' => self::EXPIRED->value,
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
            self::EXPIRED => '已过期',
        };
    }

    /**
     * 判断是否为有效状态
     */
    public function isValid(): bool
    {
        return in_array($this, [self::APPROVED], true);
    }

    /**
     * 获取资质状态徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => self::SECONDARY,
            self::PENDING_REVIEW => self::WARNING,
            self::APPROVED => self::SUCCESS,
            self::REJECTED => self::DANGER,
            self::EXPIRED => self::DANGER,
        };
    }
}
