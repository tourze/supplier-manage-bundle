<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
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
use Tourze\SupplierManageBundle\Entity\PerformanceEvaluation;
use Tourze\SupplierManageBundle\Enum\PerformanceEvaluationStatus;
use Tourze\SupplierManageBundle\Enum\PerformanceGrade;

/**
 * 供应商绩效评估管理控制器
 *
 * @extends AbstractCrudController<PerformanceEvaluation>
 */
#[AdminCrud(routePath: '/supplier/performance-evaluation', routeName: 'supplier_performance_evaluation')]
final class PerformanceEvaluationCrudController extends AbstractCrudController
{
    use SafeAdminContextTrait;

    public static function getEntityFqcn(): string
    {
        return PerformanceEvaluation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('绩效评估')
            ->setEntityLabelInPlural('绩效评估管理')
            ->setPageTitle('index', '供应商绩效评估列表')
            ->setPageTitle('new', '创建绩效评估')
            ->setPageTitle('edit', '编辑绩效评估')
            ->setPageTitle('detail', '绩效评估详情')
            ->setDefaultSort(['evaluationDate' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->setSearchFields(['evaluationNumber', 'title', 'evaluator', 'supplier.name'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $calculateGradeAction = Action::new('calculateGrade', '自动计算等级', 'fa fa-calculator')
            ->linkToCrudAction('calculateGrade')
            ->addCssClass('btn btn-info')
            ->displayIf(static function (PerformanceEvaluation $entity) {
                $status = $entity->getStatus();

                return $status->isEditable();
            })
        ;

        $submitForReviewAction = Action::new('submitForReview', '提交审核', 'fa fa-paper-plane')
            ->linkToCrudAction('submitForReview')
            ->addCssClass('btn btn-warning')
            ->displayIf(static function (PerformanceEvaluation $entity) {
                return PerformanceEvaluationStatus::DRAFT === $entity->getStatus();
            })
        ;

        $approveAction = Action::new('approve', '批准评估', 'fa fa-check')
            ->linkToCrudAction('approve')
            ->addCssClass('btn btn-success')
            ->displayIf(static function (PerformanceEvaluation $entity) {
                return PerformanceEvaluationStatus::PENDING_REVIEW === $entity->getStatus();
            })
        ;

        $rejectAction = Action::new('reject', '拒绝评估', 'fa fa-times')
            ->linkToCrudAction('reject')
            ->addCssClass('btn btn-danger')
            ->displayIf(static function (PerformanceEvaluation $entity) {
                return PerformanceEvaluationStatus::PENDING_REVIEW === $entity->getStatus();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_DETAIL, $calculateGradeAction)
            ->add(Crud::PAGE_DETAIL, $submitForReviewAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_INDEX, $calculateGradeAction)
            ->add(Crud::PAGE_INDEX, $submitForReviewAction)
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('evaluationNumber', '评估编号'))
            ->add(TextFilter::new('title', '评估标题'))
            ->add(EntityFilter::new('supplier', '供应商'))
            ->add(TextFilter::new('evaluationPeriod', '评估周期'))
            ->add(TextFilter::new('evaluator', '评估人'))
            ->add(ChoiceFilter::new('grade', '等级')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->getLabel(), PerformanceGrade::cases()),
                    PerformanceGrade::cases()
                )))
            ->add(ChoiceFilter::new('status', '状态')
                ->setChoices(array_combine(
                    array_map(fn ($case) => $case->getLabel(), PerformanceEvaluationStatus::cases()),
                    PerformanceEvaluationStatus::cases()
                )))
            ->add(NumericFilter::new('overallScore', '综合得分'))
            ->add(DateTimeFilter::new('evaluationDate', '评估日期'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 列表页显示字段
        if (Crud::PAGE_INDEX === $pageName) {
            yield IntegerField::new('id', 'ID');
            yield TextField::new('evaluationNumber', '评估编号')
                ->setHelp('唯一的评估编号')
            ;
            yield TextField::new('title', '评估标题')
                ->setHelp('评估的标题或主题')
            ;
            yield AssociationField::new('supplier', '供应商')
                ->setHelp('被评估的供应商')
            ;
            yield TextField::new('evaluationPeriod', '评估周期')
                ->setHelp('评估的时间周期')
            ;
            yield NumberField::new('overallScore', '综合得分')
                ->setNumDecimals(2)
                ->setHelp('评估的综合得分')
                ->formatValue(function ($value, PerformanceEvaluation $entity) {
                    return (string) $entity->getOverallScore() . ' 分';
                })
            ;
            $gradeField = EnumField::new('grade', '等级');
            $gradeField->setEnumCases(PerformanceGrade::cases());
            yield $gradeField
                ->setHelp('根据得分计算的等级')
            ;
            $statusField = EnumField::new('status', '状态');
            $statusField->setEnumCases(PerformanceEvaluationStatus::cases());
            yield $statusField
                ->setHelp('评估的当前状态')
            ;
            yield TextField::new('evaluator', '评估人')
                ->setHelp('负责评估的人员')
            ;
            yield DateField::new('evaluationDate', '评估日期')
                ->setFormat('yyyy-MM-dd')
                ->setHelp('评估实施的日期')
            ;

            return;
        }

        // 表单页面字段配置
        yield FormField::addTab('基本信息');

        yield TextField::new('evaluationNumber', '评估编号')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('唯一的评估编号，用于标识此次评估')
            ->setFormTypeOption('attr', ['placeholder' => '例如：PE202501001'])
        ;

        yield AssociationField::new('supplier', '供应商')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('选择被评估的供应商')
            ->autocomplete()
        ;

        yield TextField::new('title', '评估标题')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('评估的标题或主题描述')
            ->setFormTypeOption('attr', ['placeholder' => '例如：2024年第四季度供应商绩效评估'])
        ;

        yield TextField::new('evaluationPeriod', '评估周期')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('评估覆盖的时间周期')
            ->setFormTypeOption('attr', ['placeholder' => '例如：2024年第四季度'])
        ;

        yield DateField::new('evaluationDate', '评估日期')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('评估实施的具体日期')
            ->setFormTypeOption('attr', ['max' => date('Y-m-d')])
        ;

        yield TextField::new('evaluator', '评估人')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('负责此次评估的人员姓名')
        ;

        yield FormField::addTab('评估结果');

        yield NumberField::new('overallScore', '综合得分')
            ->setColumns(4)
            ->setRequired(true)
            ->setNumDecimals(2)
            ->setHelp('最终的综合评估得分（0-100分）')
            ->setFormTypeOption('attr', [
                'min' => 0,
                'max' => 100,
                'step' => 0.01,
                'placeholder' => '0.00',
            ])
        ;

        $gradeFormField = EnumField::new('grade', '等级');
        $gradeFormField->setEnumCases(PerformanceGrade::cases());
        yield $gradeFormField
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('基于得分的绩效等级（可使用自动计算功能）')
        ;

        $statusFormField = EnumField::new('status', '状态');
        $statusFormField->setEnumCases(PerformanceEvaluationStatus::cases());
        yield $statusFormField
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('评估的当前处理状态')
        ;

        yield FormField::addTab('评估详情');

        yield TextareaField::new('summary', '评估总结')
            ->setColumns(12)
            ->setNumOfRows(5)
            ->setMaxLength(2000)
            ->setHelp('对此次评估的总结和概述（最多2000字符）')
            ->hideOnIndex()
        ;

        yield TextareaField::new('improvementSuggestions', '改进建议')
            ->setColumns(12)
            ->setNumOfRows(5)
            ->setMaxLength(2000)
            ->setHelp('针对供应商表现的改进建议（最多2000字符）')
            ->hideOnIndex()
        ;

        yield FormField::addTab('评估项目')->hideOnForm();

        yield AssociationField::new('evaluationItems', '评估项目')
            ->setColumns(12)
            ->hideOnForm()
            ->setHelp('此次评估包含的具体评估项目和权重分配')
            ->formatValue(function ($value, PerformanceEvaluation $entity) {
                $evaluationItems = $entity->getEvaluationItems();
                if ($evaluationItems->isEmpty()) {
                    return '<span class="badge badge-warning">尚未配置评估项目</span>';
                }

                $totalWeight = $entity->getTotalWeight();
                $actualScore = $entity->calculateActualScore();
                $isValid = $entity->validateWeightsTotal();

                $html = '<div class="evaluation-items-summary">';
                $html .= '<p><strong>评估项目数量：</strong>' . $evaluationItems->count() . ' 项</p>';
                $html .= '<p><strong>权重总和：</strong>' . number_format($totalWeight, 2) . '%';
                if (!$isValid) {
                    $html .= ' <span class="badge badge-danger">权重不合规</span>';
                } else {
                    $html .= ' <span class="badge badge-success">权重正常</span>';
                }
                $html .= '</p>';
                $html .= '<p><strong>计算得分：</strong>' . number_format($actualScore, 2) . ' 分</p>';
                $quantitativeItems = $entity->getQuantitativeItems();
                $qualitativeItems = $entity->getQualitativeItems();
                $html .= '<p><strong>定量评估：</strong>' . $quantitativeItems->count() . ' 项</p>';
                $html .= '<p><strong>定性评估：</strong>' . $qualitativeItems->count() . ' 项</p>';
                $html .= '</div>';

                return $html;
            })
        ;

        yield FormField::addTab('系统信息')->hideOnForm();

        yield IntegerField::new('id', 'ID')
            ->hideOnForm()
        ;

        yield TextField::new('completedStatus', '完成状态')
            ->hideOnForm()
            ->setColumns(3)
            ->formatValue(function ($value, PerformanceEvaluation $entity) {
                if ($entity->isCompleted()) {
                    return '<span class="badge badge-success">已完成</span>';
                }

                return '<span class="badge badge-warning">进行中</span>';
            })
            ->setHelp('评估是否已完成')
            ->setVirtual(true)
        ;

        yield TextField::new('approvalStatus', '审批状态')
            ->hideOnForm()
            ->setColumns(3)
            ->formatValue(function ($value, PerformanceEvaluation $entity) {
                if ($entity->isApproved()) {
                    return '<span class="badge badge-success">已批准</span>';
                }
                if ($entity->isRejected()) {
                    return '<span class="badge badge-danger">已拒绝</span>';
                }

                return '<span class="badge badge-info">待审批</span>';
            })
            ->setHelp('评估的审批状态')
            ->setVirtual(true)
        ;

        yield TextField::new('scoreComparison', '得分对比')
            ->hideOnForm()
            ->setColumns(6)
            ->formatValue(function ($value, PerformanceEvaluation $entity) {
                $manualScore = $entity->getOverallScore();
                $calculatedScore = $entity->calculateActualScore();

                if (abs($manualScore - $calculatedScore) < 0.01) {
                    return '<span class="badge badge-success">得分一致</span> 手动: ' . number_format($manualScore, 2) . ' 计算: ' . number_format($calculatedScore, 2);
                }

                return '<span class="badge badge-warning">得分不一致</span> 手动: ' . number_format($manualScore, 2) . ' 计算: ' . number_format($calculatedScore, 2);
            })
            ->setHelp('手动输入得分与计算得分的对比')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setColumns(3)
            ->setFormat('yyyy-MM-dd HH:mm:ss')
        ;
    }

    /**
     * 自动计算等级
     */
    #[AdminAction(routeName: 'performance_evaluation_calculate_grade', routePath: '{entityId}/calculate-grade')]
    public function calculateGrade(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        $entity = $context->getEntity();
        /** @var PerformanceEvaluation $evaluation */
        $evaluation = $entity->getInstance();

        $status = $evaluation->getStatus();
        if (!$status->isEditable()) {
            $this->addFlash('danger', '当前状态下无法计算等级');

            return $this->redirect($this->generateUrl('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $evaluation->getId(),
            ]));
        }

        // 使用得分计算等级
        $newGrade = $evaluation->calculateGrade();
        $oldGrade = $evaluation->getGrade();

        $evaluation->setGrade($newGrade);
        $entityManager->flush();

        if ($oldGrade !== $newGrade) {
            $this->addFlash('success', sprintf(
                '等级已从 %s 更新为 %s（基于得分 %.2f）',
                $oldGrade->getLabel(),
                $newGrade->getLabel(),
                $evaluation->getOverallScore()
            ));
        } else {
            $this->addFlash('info', sprintf(
                '等级保持为 %s（基于得分 %.2f）',
                $newGrade->getLabel(),
                $evaluation->getOverallScore()
            ));
        }

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $evaluation->getId(),
        ]));
    }

    /**
     * 提交审核
     */
    #[AdminAction(routeName: 'performance_evaluation_submit_for_review', routePath: '{entityId}/submit-for-review')]
    public function submitForReview(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        $entity = $context->getEntity();
        /** @var PerformanceEvaluation $evaluation */
        $evaluation = $entity->getInstance();

        if (PerformanceEvaluationStatus::DRAFT !== $evaluation->getStatus()) {
            $this->addFlash('danger', '只有草稿状态的评估可以提交审核');

            return $this->redirect($this->generateUrl('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $evaluation->getId(),
            ]));
        }

        $evaluation->setStatus(PerformanceEvaluationStatus::PENDING_REVIEW);
        $entityManager->flush();

        $this->addFlash('success', '评估已提交审核，等待管理员审批');

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $evaluation->getId(),
        ]));
    }

    /**
     * 批准评估
     */
    #[AdminAction(routeName: 'performance_evaluation_approve', routePath: '{entityId}/approve')]
    public function approve(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        $entity = $context->getEntity();
        /** @var PerformanceEvaluation $evaluation */
        $evaluation = $entity->getInstance();

        if (PerformanceEvaluationStatus::PENDING_REVIEW !== $evaluation->getStatus()) {
            $this->addFlash('danger', '只有待审核状态的评估可以批准');

            return $this->redirect($this->generateUrl('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $evaluation->getId(),
            ]));
        }

        $evaluation->setStatus(PerformanceEvaluationStatus::CONFIRMED);
        $entityManager->flush();

        $this->addFlash('success', sprintf(
            '评估 "%s" 已批准确认',
            $evaluation->getTitle()
        ));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $evaluation->getId(),
        ]));
    }

    /**
     * 拒绝评估
     */
    #[AdminAction(routeName: 'performance_evaluation_reject', routePath: '{entityId}/reject')]
    public function reject(AdminContext $context, EntityManagerInterface $entityManager): Response
    {
        $entity = $context->getEntity();
        /** @var PerformanceEvaluation $evaluation */
        $evaluation = $entity->getInstance();

        if (PerformanceEvaluationStatus::PENDING_REVIEW !== $evaluation->getStatus()) {
            $this->addFlash('danger', '只有待审核状态的评估可以拒绝');

            return $this->redirect($this->generateUrl('admin', [
                'crudAction' => 'detail',
                'crudControllerFqcn' => self::class,
                'entityId' => $evaluation->getId(),
            ]));
        }

        $evaluation->setStatus(PerformanceEvaluationStatus::REJECTED);
        $entityManager->flush();

        $this->addFlash('warning', sprintf(
            '评估 "%s" 已被拒绝，可重新编辑后再次提交',
            $evaluation->getTitle()
        ));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'detail',
            'crudControllerFqcn' => self::class,
            'entityId' => $evaluation->getId(),
        ]));
    }

    /**
     * 重写edit方法以安全处理AdminContext
     */
    public function edit(AdminContext $context)
    {
        // 在需要实体的动作之前进行安全守卫，避免 AdminContext::getEntity() 返回 null 造成 500
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
