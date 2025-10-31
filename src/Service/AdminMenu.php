<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Service;

use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Attribute\MenuProvider;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

#[MenuProvider]
class AdminMenu implements MenuProviderInterface
{
    public function __invoke(ItemInterface $item): void
    {
        $supplierMenu = $item->addChild('供应商管理', [
            'icon' => 'fas fa-handshake',
        ]);

        // 基础供应商管理
        $supplierMenu->addChild('供应商列表', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\SupplierCrudController'],
            'icon' => 'fas fa-building',
        ]);

        $supplierMenu->addChild('联系人管理', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\SupplierContactCrudController'],
            'icon' => 'fas fa-address-book',
        ]);

        $supplierMenu->addChild('资质管理', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\SupplierQualificationCrudController'],
            'icon' => 'fas fa-certificate',
        ]);

        // 合同管理
        $contractMenu = $item->addChild('合同管理', [
            'icon' => 'fas fa-file-contract',
        ]);

        $contractMenu->addChild('合同列表', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\ContractCrudController'],
            'icon' => 'fas fa-file-signature',
        ]);

        // 绩效评估
        $evaluationMenu = $item->addChild('绩效评估', [
            'icon' => 'fas fa-chart-bar',
        ]);

        $evaluationMenu->addChild('评估管理', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\PerformanceEvaluationCrudController'],
            'icon' => 'fas fa-poll',
        ]);

        $evaluationMenu->addChild('评估项目', [
            'route' => 'admin',
            'routeParameters' => ['crudAction' => 'index', 'crudControllerFqcn' => 'Tourze\SupplierManageBundle\Controller\Admin\EvaluationItemCrudController'],
            'icon' => 'fas fa-list-check',
        ]);
    }
}
