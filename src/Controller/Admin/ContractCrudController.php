<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\SupplierManageBundle\Controller\Admin\Traits\SafeAdminContextTrait;
use Tourze\SupplierManageBundle\Entity\Contract;
use Tourze\SupplierManageBundle\Enum\ContractStatus;

/**
 * 供应商合同管理控制器
 *
 * @extends AbstractCrudController<Contract>
 */
#[AdminCrud(routePath: '/supplier/contract', routeName: 'supplier_contract')]
final class ContractCrudController extends AbstractCrudController
{
    use SafeAdminContextTrait;

    public static function getEntityFqcn(): string
    {
        return Contract::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('供应商合同')
            ->setEntityLabelInPlural('供应商合同')
            ->setPageTitle('index', '供应商合同管理')
            ->setPageTitle('detail', '合同详情')
            ->setPageTitle('new', '新增合同')
            ->setPageTitle('edit', '编辑合同')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['contractNumber', 'title', 'supplier.name'])
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('基本信息')->setIcon('fa fa-info-circle');

        yield TextField::new('contractNumber', '合同编号')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('唯一的合同编号，用于识别合同')
            ->setFormTypeOption('attr', ['placeholder' => '例如：CT202501001'])
        ;

        yield AssociationField::new('supplier', '供应商')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('选择与此合同关联的供应商')
            ->autocomplete()
        ;

        yield TextField::new('title', '合同标题')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('简洁明确的合同标题')
            ->setFormTypeOption('attr', ['placeholder' => '例如：设备采购合同'])
        ;

        yield ChoiceField::new('contractType', '合同类型')
            ->setColumns(3)
            ->setRequired(true)
            ->setChoices([
                '供应合同' => 'supply',
                '服务合同' => 'service',
                '采购合同' => 'purchase',
                '租赁合同' => 'lease',
                '其他合同' => 'other',
            ])
            ->setHelp('选择合同的业务类型')
        ;

        $statusField = EnumField::new('status', '合同状态');
        $statusField->setEnumCases(ContractStatus::cases());
        yield $statusField
            ->setColumns(3)
            ->setHelp('当前合同的处理状态')
            ->hideOnForm()
        ;

        yield FormField::addPanel('金额信息')->setIcon('fa fa-money-bill');

        yield MoneyField::new('amount', '合同金额')
            ->setColumns(4)
            ->setRequired(true)
            ->setCurrency('CNY')
            ->setStoredAsCents(false)
            ->setHelp('合同的总金额')
        ;

        yield ChoiceField::new('currency', '币种')
            ->setColumns(2)
            ->setRequired(true)
            ->setChoices([
                '人民币' => 'CNY',
                '美元' => 'USD',
                '欧元' => 'EUR',
                '日元' => 'JPY',
                '港币' => 'HKD',
            ])
            ->setHelp('合同使用的货币类型')
        ;

        yield FormField::addPanel('合同期限')->setIcon('fa fa-calendar');

        yield DateField::new('startDate', '合同开始日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('合同生效的起始日期')
            ->setFormTypeOption('attr', ['min' => date('Y-m-d')])
        ;

        yield DateField::new('endDate', '合同结束日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('合同到期的结束日期')
            ->setFormTypeOption('attr', ['min' => date('Y-m-d')])
        ;

        // 计算字段：合同期限（仅在详情页显示）
        yield TextField::new('durationInDaysDisplay', '合同期限（天）')
            ->onlyOnDetail()
            ->setColumns(2)
            ->formatValue(function ($value, Contract $entity) {
                return $entity->getDurationInDays() . ' 天';
            })
            ->setHelp('合同的总执行天数')
        ;

        yield TextField::new('currentStatusDisplay', '当前状态')
            ->onlyOnDetail()
            ->setColumns(2)
            ->formatValue(function ($value, Contract $entity) {
                if ($entity->isExpired()) {
                    return '<span class="badge badge-danger">已过期</span>';
                }
                if ($entity->isCurrentlyActive()) {
                    return '<span class="badge badge-success">生效中</span>';
                }

                return '<span class="badge badge-warning">未生效</span>';
            })
            ->setHelp('基于当前日期的合同状态')
        ;

        yield FormField::addPanel('合同详情')->setIcon('fa fa-file-text');

        yield TextareaField::new('description', '合同描述')
            ->setColumns(6)
            ->setNumOfRows(4)
            ->setMaxLength(5000)
            ->setHelp('合同的详细描述和主要内容概述')
            ->hideOnIndex()
        ;

        yield TextareaField::new('terms', '合同条款')
            ->setColumns(6)
            ->setNumOfRows(6)
            ->setMaxLength(10000)
            ->setHelp('重要的合同条款和约定事项')
            ->hideOnIndex()
        ;

        // 金额变更历史（仅在详情页显示）
        if (Crud::PAGE_DETAIL === $pageName) {
            yield CodeEditorField::new('amountChangeHistory', '金额变更历史')
                ->setLanguage('javascript')
                ->setColumns(12)
                ->setHelp('合同金额的变更记录（JSON格式）')
                ->onlyOnDetail()
                ->formatValue(function ($value, Contract $entity) {
                    $history = $entity->getAmountChangeHistory();
                    if ([] === $history) {
                        return '无变更记录';
                    }

                    return json_encode($history, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                })
            ;
        }

        yield FormField::addPanel('时间信息')->setIcon('fa fa-clock')->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->setColumns(3)
            ->hideOnForm()
            ->setHelp('合同记录的创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setColumns(3)
            ->hideOnForm()
            ->setHelp('合同记录的最后更新时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('contractNumber', '合同编号'))
            ->add(TextFilter::new('title', '合同标题'))
            ->add(EntityFilter::new('supplier', '供应商'))
            ->add(ChoiceFilter::new('contractType', '合同类型')->setChoices([
                '供应合同' => 'supply',
                '服务合同' => 'service',
                '采购合同' => 'purchase',
                '租赁合同' => 'lease',
                '其他合同' => 'other',
            ]))
            ->add(ChoiceFilter::new('status', '合同状态')->setChoices(array_reduce(
                ContractStatus::cases(),
                static fn (array $choices, ContractStatus $status): array => $choices + [$status->getLabel() => $status->value],
                []
            )))
            ->add(NumericFilter::new('amount', '合同金额'))
            ->add(ChoiceFilter::new('currency', '币种')->setChoices([
                '人民币' => 'CNY',
                '美元' => 'USD',
                '欧元' => 'EUR',
                '日元' => 'JPY',
                '港币' => 'HKD',
            ]))
            ->add(DateTimeFilter::new('startDate', '开始日期'))
            ->add(DateTimeFilter::new('endDate', '结束日期'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * 重写 detail 以安全处理 AdminContext
     */
    #[AdminAction(routePath: '{entityId}/detail', routeName: 'detail')]
    public function detail(AdminContext $context)
    {
        if (null !== $response = $this->guardEntityRequiredAction($context, Action::DETAIL)) {
            return $response;
        }

        return parent::detail($context);
    }

    /**
     * 重写 edit 以安全处理 AdminContext
     */
    public function edit(AdminContext $context)
    {
        if (null !== $response = $this->guardEntityRequiredAction($context, Action::EDIT)) {
            return $response;
        }

        return parent::edit($context);
    }

    /**
     * 重写 delete 以安全处理 AdminContext
     */
    public function delete(AdminContext $context)
    {
        if (null !== $response = $this->guardEntityRequiredAction($context, Action::DELETE)) {
            return $response;
        }

        return parent::delete($context);
    }

    /**
     * 重写index方法以安全处理AdminContext
     */
    public function index(AdminContext $context): Response|KeyValueStore
    {
        return $this->safeIndex($context);
    }
}
