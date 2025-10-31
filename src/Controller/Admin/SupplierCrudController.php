<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;

/**
 * 供应商管理控制器
 * @extends AbstractCrudController<Supplier>
 */
#[AdminCrud(routePath: '/supplier/supplier', routeName: 'supplier_supplier')]
final class SupplierCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Supplier::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('供应商')
            ->setEntityLabelInPlural('供应商管理')
            ->setPageTitle('index', '供应商列表')
            ->setPageTitle('new', '创建供应商')
            ->setPageTitle('edit', '编辑供应商')
            ->setPageTitle('detail', '供应商详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['name', 'legalName', 'registrationNumber', 'taxNumber', 'businessCategory'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewContactsAction = Action::new('viewContacts', '管理联系人', 'fa fa-users')
            ->linkToCrudAction('viewContacts')
            ->addCssClass('btn btn-info')
        ;

        $viewQualificationsAction = Action::new('viewQualifications', '管理资质', 'fa fa-certificate')
            ->linkToCrudAction('viewQualifications')
            ->addCssClass('btn btn-success')
        ;

        $viewContractsAction = Action::new('viewContracts', '管理合同', 'fa fa-file-contract')
            ->linkToCrudAction('viewContracts')
            ->addCssClass('btn btn-warning')
        ;

        $viewEvaluationsAction = Action::new('viewEvaluations', '绩效评估', 'fa fa-chart-line')
            ->linkToCrudAction('viewEvaluations')
            ->addCssClass('btn btn-primary')
        ;

        return $actions
            ->add(Crud::PAGE_DETAIL, $viewContactsAction)
            ->add(Crud::PAGE_DETAIL, $viewQualificationsAction)
            ->add(Crud::PAGE_DETAIL, $viewContractsAction)
            ->add(Crud::PAGE_DETAIL, $viewEvaluationsAction)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('supplierType', '供应商类型')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->getLabel(), SupplierType::cases()),
                    SupplierType::cases()
                )))
            ->add(ChoiceFilter::new('cooperationModel', '合作模式')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->getLabel(), CooperationModel::cases()),
                    CooperationModel::cases()
                )))
            ->add(ChoiceFilter::new('status', '状态')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->getLabel(), SupplierStatus::cases()),
                    SupplierStatus::cases()
                )))
            ->add(BooleanFilter::new('isWarehouse', '是否仓储'))
            ->add(TextFilter::new('industry', '行业'))
            ->add(TextFilter::new('businessCategory', '业务类别'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 列表页显示字段
        if (Crud::PAGE_INDEX === $pageName) {
            // Debug: Let's try yielding instead of returning
            yield IntegerField::new('id', 'ID');
            yield TextField::new('name', '供应商名称')
                ->setHelp('供应商的显示名称')
            ;
            yield TextField::new('legalName', '法人名称')
                ->setHelp('法人或公司全称')
            ;
            $supplierTypeField = EnumField::new('supplierType', '类型');
            $supplierTypeField->setEnumCases(SupplierType::cases());
            yield $supplierTypeField;
            $statusField = EnumField::new('status', '状态');
            $statusField->setEnumCases(SupplierStatus::cases());
            yield $statusField;
            yield TextField::new('businessCategory', '业务类别')
                ->setHelp('主要业务分类')
            ;
            yield BooleanField::new('isWarehouse', '仓储服务')
                ->setHelp('是否提供仓储服务')
            ;
            yield DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss')
            ;

            return; // Stop execution after yielding index fields
        }

        // 表单页面字段配置
        yield FormField::addTab('基本信息');

        yield TextField::new('name', '供应商名称')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('供应商的显示名称，用于日常标识')
        ;

        yield TextField::new('legalName', '法人名称')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('法人或公司的完整注册名称')
        ;

        $supplierTypeFormField = EnumField::new('supplierType', '供应商类型');
        $supplierTypeFormField->setEnumCases(SupplierType::cases());
        yield $supplierTypeFormField
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('选择供应商的业务类型')
        ;

        $cooperationModelField = EnumField::new('cooperationModel', '合作模式');
        $cooperationModelField->setEnumCases(CooperationModel::cases());
        yield $cooperationModelField
            ->setColumns(4)
            ->setHelp('与供应商的合作方式')
        ;

        $statusFormField = EnumField::new('status', '状态');
        $statusFormField->setEnumCases(SupplierStatus::cases());
        yield $statusFormField
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('供应商的当前状态')
        ;

        yield FormField::addTab('注册信息');

        yield TextareaField::new('legalAddress', '法人地址')
            ->setColumns(12)
            ->setRequired(true)
            ->setNumOfRows(2)
            ->setHelp('法人的注册地址')
        ;

        yield TextField::new('registrationNumber', '注册号')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('工商注册号或统一社会信用代码')
        ;

        yield TextField::new('taxNumber', '税号')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('税务登记号')
        ;

        yield TextField::new('industry', '所属行业')
            ->setColumns(6)
            ->setHelp('供应商所属的行业类别')
        ;

        yield TextField::new('businessCategory', '业务类别')
            ->setColumns(6)
            ->setHelp('主要业务分类或经营范围')
        ;

        yield UrlField::new('website', '公司官网')
            ->setColumns(6)
            ->setHelp('供应商的官方网站地址')
        ;

        yield BooleanField::new('isWarehouse', '提供仓储服务')
            ->setColumns(6)
            ->setHelp('该供应商是否提供仓储服务')
        ;

        yield FormField::addTab('公司介绍');

        yield TextareaField::new('introduction', '公司介绍')
            ->setColumns(12)
            ->setNumOfRows(5)
            ->setHelp('供应商的详细介绍，最多2000个字符')
        ;

        yield FormField::addTab('系统信息')->hideOnForm();

        yield IntegerField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield IntegerField::new('version', '版本号')
            ->hideOnForm()
            ->setHelp('数据版本号，用于乐观锁控制')
        ;

        yield DateTimeField::new('deleteTime', '删除时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('软删除时间，为空表示未删除')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield FormField::addTab('关联信息')->hideOnForm();

        yield AssociationField::new('contacts', '联系人')
            ->hideOnForm()
            ->setHelp('与该供应商相关的联系人列表')
        ;

        yield AssociationField::new('qualifications', '资质证书')
            ->hideOnForm()
            ->setHelp('供应商的资质证书和认证信息')
        ;

        yield AssociationField::new('contracts', '合同')
            ->hideOnForm()
            ->setHelp('与该供应商签署的合同信息')
        ;

        yield AssociationField::new('performanceEvaluations', '绩效评估')
            ->hideOnForm()
            ->setHelp('供应商的绩效评估记录')
        ;
    }

    /**
     * 管理供应商联系人
     */
    #[AdminAction(routeName: 'supplier_view_contacts', routePath: '{entityId}/view-contacts')]
    public function viewContacts(): Response
    {
        $this->addFlash('info', '联系人管理功能开发中，请通过相关CRUD页面管理');

        return $this->redirectToRoute('admin');
    }

    /**
     * 管理供应商资质
     */
    #[AdminAction(routeName: 'supplier_view_qualifications', routePath: '{entityId}/view-qualifications')]
    public function viewQualifications(): Response
    {
        $this->addFlash('info', '资质管理功能开发中，请通过相关CRUD页面管理');

        return $this->redirectToRoute('admin');
    }

    /**
     * 管理供应商合同
     */
    #[AdminAction(routeName: 'supplier_view_contracts', routePath: '{entityId}/view-contracts')]
    public function viewContracts(): Response
    {
        $this->addFlash('info', '合同管理功能开发中，请通过相关CRUD页面管理');

        return $this->redirectToRoute('admin');
    }

    /**
     * 管理供应商绩效评估
     */
    #[AdminAction(routeName: 'supplier_view_evaluations', routePath: '{entityId}/view-evaluations')]
    public function viewEvaluations(): Response
    {
        $this->addFlash('info', '绩效评估管理功能开发中，请通过相关CRUD页面管理');

        return $this->redirectToRoute('admin');
    }
}
