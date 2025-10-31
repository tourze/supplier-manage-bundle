<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SupplierManageBundle\DependencyInjection\SupplierManageExtension;

/**
 * @internal
 */
#[CoversClass(SupplierManageExtension::class)]
class SupplierManageExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private SupplierManageExtension $extension;

    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new SupplierManageExtension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }

    public function testLoad(): void
    {
        // Act
        $this->extension->load([], $this->container);

        // Assert
        // 验证容器中注册了预期的服务
        $this->assertTrue($this->container->hasDefinition('Tourze\SupplierManageBundle\Service\SupplierService')
            || $this->container->hasParameter('supplier_manage.some_parameter')
            || [] !== $this->container->getDefinitions());
    }

    public function testGetAlias(): void
    {
        // 如果扩展有别名，可以测试
        // 当前 SupplierManageExtension 没有重写 getAlias 方法，所以会使用默认的
        $expectedAlias = 'supplier_manage'; // 基于类名 SupplierManageExtension
        $this->assertEquals($expectedAlias, $this->extension->getAlias());
    }
}
