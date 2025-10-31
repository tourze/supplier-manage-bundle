<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * 供应商管理Bundle的EasyAdmin控制器测试基类
 * 解决biz_user表依赖问题，强制使用内存用户而非数据库用户
 *
 * @internal
 */
#[CoversClass(AbstractSupplierEasyAdminControllerTestCase::class)]
#[RunTestsInSeparateProcesses]
abstract class AbstractSupplierEasyAdminControllerTestCase extends AbstractEasyAdminControllerTestCase
{
    /**
     * 重写createAdminUser方法，避免数据库操作
     *
     * 注意：忽略传入的用户名和密码，始终使用与 security.yaml when@test 配置中定义的凭据
     * 这确保了无论父类传入什么参数，我们都使用正确的测试用户
     */
    protected function createAdminUser(string $username = 'admin', string $password = 'password'): UserInterface
    {
        // 强制使用 security.yaml when@test 配置中定义的用户名和密码
        // 忽略传入的参数，因为 AbstractEasyAdminControllerTestCase 可能传入不同的值
        return new InMemoryUser('admin', 'password', ['ROLE_ADMIN']);
    }

    /**
     * 以管理员身份登录 - 重写以使用配置的测试用户
     *
     * 注意：忽略传入的用户名和密码，始终使用与 security.yaml when@test 配置中定义的凭据
     * 这确保了无论父类传入什么参数，我们都使用正确的测试用户
     */
    protected function loginAsAdmin(KernelBrowser $client, string $username = 'admin', string $password = 'password'): UserInterface
    {
        // 强制使用 security.yaml when@test 配置中定义的用户名和密码
        // 忽略传入的参数，确保与配置文件中的 users_in_memory 提供者一致
        $user = new InMemoryUser('admin', 'password', ['ROLE_ADMIN']);
        $client->loginUser($user);

        return $user;
    }

    /**
     * 创建认证客户端的辅助方法 - 直接提供给子类使用
     */
    protected function createSimpleAuthenticatedClient(): KernelBrowser
    {
        // 创建客户端
        $client = self::createClient();

        // 创建并登录内存用户
        $user = new InMemoryUser('admin', null, ['ROLE_ADMIN']);
        $client->loginUser($user);

        return $client;
    }

    /**
     * 以普通用户身份登录 - 重写以强制使用内存用户
     */
    protected function loginAsUser(KernelBrowser $client, string $username = 'user', string $password = 'password'): UserInterface
    {
        // 强制使用内存用户，避免biz_user表依赖
        $user = new InMemoryUser($username, null, ['ROLE_USER']);
        $client->loginUser($user);

        return $user;
    }
}
