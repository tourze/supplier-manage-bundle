<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\SupplierManageBundle\Service\AdminMenu;

/**
 * AdminMenu 单元测试
 *
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private ItemInterface $item;

    public function testInvokeMethod(): void
    {
        // 测试 AdminMenu 的 __invoke 方法正常工作
        $this->expectNotToPerformAssertions();

        try {
            $adminMenu = self::getService(AdminMenu::class);
            ($adminMenu)($this->item);
        } catch (\Throwable $e) {
            self::fail('AdminMenu __invoke method should not throw exception: ' . $e->getMessage());
        }
    }

    protected function onSetUp(): void
    {
        // 使用PHPUnit Mock功能创建ItemInterface，避免代码重复
        $this->item = $this->createMock(ItemInterface::class);

        // 配置mock以满足AdminMenu的需求
        $this->item->method('getChild')
            ->willReturnCallback(function (mixed $name): ?ItemInterface {
                return in_array($name, ['供应商管理', '合同管理', '绩效评估'], true) ? $this->item : null;
            })
        ;
    }
}
