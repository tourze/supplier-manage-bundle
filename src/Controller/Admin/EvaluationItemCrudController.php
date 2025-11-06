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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
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
use Tourze\SupplierManageBundle\Entity\EvaluationItem;
use Tourze\SupplierManageBundle\Enum\EvaluationItemType;

/**
 * 评估项管理控制器
 *
 * @extends AbstractCrudController<EvaluationItem>
 */
#[AdminCrud(routePath: '/supplier/evaluation-item', routeName: 'supplier_evaluation_item')]
final class EvaluationItemCrudController extends AbstractCrudController
{
    use SafeAdminContextTrait;

    public static function getEntityFqcn(): string
    {
        return EvaluationItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('评估项')
            ->setEntityLabelInPlural('评估项管理')
            ->setPageTitle('index', '评估项列表')
            ->setPageTitle('detail', '评估项详情')
            ->setPageTitle('new', '新增评估项')
            ->setPageTitle('edit', '编辑评估项')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['itemName', 'evaluation.title', 'evaluation.evaluationNumber', 'description'])
            ->showEntityActionsInlined()
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $calculateWeightedScoreAction = Action::new('calculateWeightedScore', '计算加权得分', 'fa fa-calculator')
            ->linkToCrudAction('calculateWeightedScore')
            ->addCssClass('btn btn-info')
            ->setHtmlAttributes(['title' => '重新计算该评估项的加权得分'])
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $calculateWeightedScoreAction)
            ->add(Crud::PAGE_DETAIL, $calculateWeightedScoreAction)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 列表页专用显示字段
        if (Crud::PAGE_INDEX === $pageName) {
            yield TextField::new('itemName', '指标名称');
            yield AssociationField::new('evaluation', '所属评估')
                ->formatValue(function ($value, EvaluationItem $item) {
                    $evaluation = $item->getEvaluation();

                    return $evaluation->getEvaluationNumber();
                })
            ;
            $itemTypeField = EnumField::new('itemType', '类型');
            $itemTypeField->setEnumCases(EvaluationItemType::cases());
            yield $itemTypeField;
            yield NumberField::new('weight', '权重(%)')
                ->setNumDecimals(2)
            ;
            yield NumberField::new('score', '得分')
                ->setNumDecimals(2)
            ;
            yield NumberField::new('maxScore', '满分')
                ->setNumDecimals(2)
            ;
            yield NumberField::new('weightedScore', '加权得分')
                ->setNumDecimals(2)
                ->formatValue(function ($value, EvaluationItem $item) {
                    return number_format($item->getWeightedScore(), 2);
                })
            ;
            yield TextField::new('unit', '单位');
            yield DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm')
            ;

            return;
        }

        yield FormField::addPanel('基本信息')->setIcon('fa fa-info-circle');

        yield AssociationField::new('evaluation', '绩效评估')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('选择该评估项所属的绩效评估')
            ->autocomplete()
            ->formatValue(function ($value, EvaluationItem $item) {
                $evaluation = $item->getEvaluation();

                return $evaluation->getEvaluationNumber() . ' - ' . $evaluation->getTitle();
            })
        ;

        yield TextField::new('itemName', '指标名称')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('评估指标的名称，如：产品质量、交付及时性等')
            ->setFormTypeOption('attr', ['placeholder' => '例如：产品质量评估'])
        ;

        $itemTypeFormField = EnumField::new('itemType', '指标类型');
        $itemTypeFormField->setEnumCases(EvaluationItemType::cases());
        yield $itemTypeFormField
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('定量评估：基于具体数值；定性评估：基于主观判断')
        ;

        yield TextField::new('unit', '单位')
            ->setColumns(3)
            ->setHelp('评估指标的单位，如：%、件、天等（可选）')
            ->setFormTypeOption('attr', ['placeholder' => '例如：%、件、天'])
        ;

        yield FormField::addPanel('评分设置')->setIcon('fa fa-star');

        yield NumberField::new('weight', '权重(%)')
            ->setColumns(3)
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('该评估项在总评估中的权重百分比（0-100）')
            ->setFormTypeOption('attr', [
                'min' => 0,
                'max' => 100,
                'step' => 0.01,
                'placeholder' => '例如：25.00',
            ])
        ;

        yield NumberField::new('maxScore', '最大分值')
            ->setColumns(3)
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('该评估项的最大可得分值')
            ->setFormTypeOption('attr', [
                'min' => 0,
                'step' => 0.01,
                'placeholder' => '例如：100.00',
            ])
        ;

        yield NumberField::new('score', '实际得分')
            ->setColumns(3)
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('该评估项的实际得分')
            ->setFormTypeOption('attr', [
                'min' => 0,
                'step' => 0.01,
                'placeholder' => '例如：85.50',
            ])
        ;

        // 计算字段：在详情页显示
        if (Crud::PAGE_DETAIL === $pageName) {
            yield NumberField::new('scorePercentage', '得分率(%)')
                ->setColumns(3)
                ->setNumDecimals(2)
                ->formatValue(function ($value, EvaluationItem $item) {
                    return number_format($item->getScorePercentage(), 2);
                })
                ->setHelp('实际得分占最大分值的百分比')
            ;

            yield NumberField::new('weightedScore', '加权得分')
                ->setColumns(3)
                ->setNumDecimals(2)
                ->formatValue(function ($value, EvaluationItem $item) {
                    return number_format($item->getWeightedScore(), 2);
                })
                ->setHelp('权重 × (实际得分 / 最大分值) 的结果')
            ;
        }

        yield FormField::addPanel('详细信息')->setIcon('fa fa-file-text');

        yield TextareaField::new('description', '指标描述')
            ->setColumns(12)
            ->setNumOfRows(4)
            ->setMaxLength(1000)
            ->setHelp('对该评估指标的详细说明和评分标准')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['placeholder' => '请详细描述该评估指标的内容、评分标准等...'])
        ;

        yield FormField::addPanel('时间信息')->setIcon('fa fa-clock')->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->setColumns(6)
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('评估项记录的创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setColumns(6)
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('评估项记录的最后更新时间')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('itemName', '指标名称'))
            ->add(EntityFilter::new('evaluation', '绩效评估'))
            ->add(ChoiceFilter::new('itemType', '指标类型')->setChoices(array_reduce(
                EvaluationItemType::cases(),
                static fn (array $choices, EvaluationItemType $type): array => $choices + [$type->getLabel() => $type->value],
                []
            )))
            ->add(NumericFilter::new('weight', '权重'))
            ->add(NumericFilter::new('score', '得分'))
            ->add(NumericFilter::new('maxScore', '最大分值'))
            ->add(TextFilter::new('unit', '单位'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * 计算加权得分（重新计算并更新）
     */
    #[AdminAction(routePath: '{entityId}/calculate-weighted-score', routeName: 'calculateWeightedScore')]
    public function calculateWeightedScore(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        $evaluationItem = $entity->getInstance();

        if (!$evaluationItem instanceof EvaluationItem) {
            $this->addFlash('danger', '评估项不存在');

            return $this->redirectToRoute('admin');
        }

        // 重新计算加权得分（虽然是只读属性，但可以触发验证）
        $weightedScore = $evaluationItem->getWeightedScore();
        $scorePercentage = $evaluationItem->getScorePercentage();

        $this->addFlash('success', sprintf(
            '评估项 "%s" 的加权得分为 %.2f，得分率为 %.2f%%',
            $evaluationItem->getItemName(),
            $weightedScore,
            $scorePercentage
        ));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    /**
     * 重写edit方法以安全处理AdminContext
     */
    public function edit(AdminContext $context)
    {
        if (null !== $response = $this->guardEntityRequiredAction($context, Action::EDIT)) {
            return $response;
        }

        return parent::edit($context);
    }

    /**
     * 重写detail方法以安全处理AdminContext
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
     * 重写delete方法以安全处理AdminContext
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
