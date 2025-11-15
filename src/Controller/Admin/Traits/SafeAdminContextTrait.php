<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Controller\Admin\Traits;

use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * 针对 EasyAdmin 在某些情况下 AdminContext::getEntity() 返回 null（但签名非空）的问题
 * 提供统一的守卫与降级处理，避免 500。
 */
trait SafeAdminContextTrait
{
    /**
     * 在进入需要实体的动作（detail/edit/delete）前做安全守卫。
     *
     * - 若能取到实体则返回 null，控制器可继续 parent::...()
     * - 若取不到实体：
     *   1) 优先使用 referer 保留完整 EasyAdmin 上下文
     *   2) 降级：若请求中带有 entityId，则重定向到后备的"丑 URL"（admin 路由）
     *   3) 最终降级：回退至当前 CRUD 的 index
     */
    protected function guardEntityRequiredAction(AdminContext $context, string $targetAction): ?RedirectResponse
    {
        $entityDto = $this->tryGetEntityDto($context);
        if (null !== $entityDto && null !== $entityDto->getInstance()) {
            return null; // 安全，继续执行父实现
        }

        $request = $context->getRequest();

        // 优先使用 referer 保留完整的 EasyAdmin 上下文
        $referer = $request->headers->get('referer');
        if ($referer) {
            return new RedirectResponse($referer);
        }

        // 降级方案：尝试从请求中获取 entityId
        $entityId = $request->attributes->get('entityId') ?? $request->query->get('entityId');

        $crudDto = $context->getCrud();
        if (null === $crudDto || null === $crudDto->getControllerFqcn()) {
            // 无法获取控制器信息，回退到原始方法
            return null;
        }

        if (null !== $entityId) {
            // 使用"丑 URL"参数，确保路由解析时能拿到 FQCN、action 和实体 ID
            return new RedirectResponse($this->generateUrl('admin', [
                'crudAction' => $targetAction,
                'crudControllerFqcn' => $crudDto->getControllerFqcn(),
                'entityId' => $entityId,
            ]));
        }

        // 无实体 ID，只能回到列表页，避免 500
        return new RedirectResponse($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => $crudDto->getControllerFqcn(),
        ]));
    }

    /**
     * 尝试从 AdminContext 中获取 EntityDto；若触发类型错误则返回 null。
     */
    protected function tryGetEntityDto(AdminContext $context): ?EntityDto
    {
        try {
            return $context->getEntity();
        } catch (\TypeError) {
            return null;
        }
    }

    /**
     * 安全的index方法实现，处理AdminContext::getEntity()返回null的情况
     */
    protected function safeIndex(AdminContext $context): Response|KeyValueStore
    {
        try {
            return parent::index($context);
        } catch (\TypeError $e) {
            // 如果是AdminContext::getEntity()相关的TypeError，重定向到安全页面
            if (str_contains($e->getMessage(), 'AdminContext::getEntity')) {
                // 优先使用 referer 保留完整的 EasyAdmin 上下文
                $referer = $context->getRequest()?->headers->get('referer');
                if ($referer) {
                    return new RedirectResponse($referer);
                }

                // 降级方案：构造标准的 admin 路由
                $crudDto = $context->getCrud();
                if (null !== $crudDto && null !== $crudDto->getControllerFqcn()) {
                    return new RedirectResponse($this->generateUrl('admin', [
                        'crudAction' => 'index',
                        'crudControllerFqcn' => $crudDto->getControllerFqcn(),
                    ]));
                }
            }
            throw $e;
        }
    }
}
