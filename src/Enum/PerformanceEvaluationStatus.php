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
 * 绩效评估状态枚举
 */
enum PerformanceEvaluationStatus: string implements Itemable, Labelable, Selectable, BadgeInterface
{
    use ItemTrait;
    use SelectTrait;
    case DRAFT = 'draft';
    case PENDING_REVIEW = 'pending_review';
    case CONFIRMED = 'confirmed';
    case REJECTED = 'rejected';

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
            '已确认' => self::CONFIRMED->value,
            '已拒绝' => self::REJECTED->value,
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
            self::CONFIRMED => '已确认',
            self::REJECTED => '已拒绝',
        };
    }

    /**
     * 判断是否可以编辑
     */
    public function isEditable(): bool
    {
        return in_array($this, [self::DRAFT, self::REJECTED], true);
    }

    /**
     * 判断是否已完成
     */
    public function isCompleted(): bool
    {
        return in_array($this, [self::CONFIRMED], true);
    }

    /**
     * 获取状态徽章样式
     */
    public function getBadge(): string
    {
        return match ($this) {
            self::DRAFT => self::SECONDARY,
            self::PENDING_REVIEW => self::WARNING,
            self::CONFIRMED => self::SUCCESS,
            self::REJECTED => self::DANGER,
        };
    }
}
