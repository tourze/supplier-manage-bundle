<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Controller\Admin;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\EasyAdminEnumFieldBundle\Field\EnumField;
use Tourze\SupplierManageBundle\Controller\Admin\Traits\SafeAdminContextTrait;
use Tourze\SupplierManageBundle\Entity\SupplierQualification;
use Tourze\SupplierManageBundle\Enum\SupplierQualificationStatus;

/**
 * 供应商资质管理控制器
 *
 * @extends AbstractCrudController<SupplierQualification>
 */
#[AdminCrud(routePath: '/supplier/qualification', routeName: 'supplier_qualification')]
final class SupplierQualificationCrudController extends AbstractCrudController
{
    use SafeAdminContextTrait;

    public static function getEntityFqcn(): string
    {
        return SupplierQualification::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('供应商资质')
            ->setEntityLabelInPlural('供应商资质管理')
            ->setPageTitle('index', '供应商资质列表')
            ->setPageTitle('new', '添加资质证书')
            ->setPageTitle('edit', '编辑资质证书')
            ->setPageTitle('detail', '资质证书详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['name', 'certificateNumber', 'issuingAuthority', 'supplier.name'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
            ->showEntityActionsInlined()
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $approveAction = Action::new('approve', '批准资质', 'fa fa-check-circle')
            ->linkToCrudAction('approve')
            ->addCssClass('btn btn-success')
            ->displayIf(function (SupplierQualification $qualification) {
                return SupplierQualificationStatus::PENDING_REVIEW === $qualification->getStatus();
            })
        ;

        $rejectAction = Action::new('reject', '拒绝资质', 'fa fa-times-circle')
            ->linkToCrudAction('reject')
            ->addCssClass('btn btn-danger')
            ->displayIf(function (SupplierQualification $qualification) {
                return SupplierQualificationStatus::PENDING_REVIEW === $qualification->getStatus();
            })
        ;

        $renewAction = Action::new('renew', '资质续期', 'fa fa-refresh')
            ->linkToCrudAction('renew')
            ->addCssClass('btn btn-warning')
            ->displayIf(function (SupplierQualification $qualification) {
                return $qualification->isExpired() || $qualification->getDaysUntilExpiry() <= 30;
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $approveAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ->add(Crud::PAGE_INDEX, $rejectAction)
            ->add(Crud::PAGE_DETAIL, $rejectAction)
            ->add(Crud::PAGE_INDEX, $renewAction)
            ->add(Crud::PAGE_DETAIL, $renewAction)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
            ->setPermission('approve', 'ROLE_ADMIN')
            ->setPermission('reject', 'ROLE_ADMIN')
            ->setPermission('renew', 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            return $this->getIndexFields();
        }

        return $this->getFormFields($pageName);
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

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('supplier', '供应商'))
            ->add(TextFilter::new('name', '资质名称'))
            ->add(ChoiceFilter::new('type', '资质类型')->setChoices($this->getQualificationTypeChoices()))
            ->add(TextFilter::new('certificateNumber', '证书编号'))
            ->add(TextFilter::new('issuingAuthority', '颁发机构'))
            ->add(ChoiceFilter::new('status', '状态')->setChoices(array_reduce(
                SupplierQualificationStatus::cases(),
                static fn (array $choices, SupplierQualificationStatus $status): array => $choices + [$status->getLabel() => $status->value],
                []
            )))
            ->add(BooleanFilter::new('isActive', '是否有效'))
            ->add(DateTimeFilter::new('issuedDate', '颁发日期'))
            ->add(DateTimeFilter::new('expiryDate', '到期日期'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getFormFields(string $pageName): iterable
    {
        yield FormField::addPanel('基本信息')->setIcon('fa fa-info-circle');

        yield IntegerField::new('id', 'ID')
            ->setColumns(1)
            ->onlyOnIndex()
        ;

        yield AssociationField::new('supplier', '所属供应商')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('选择该资质所属的供应商')
            ->autocomplete()
            ->formatValue(function ($value, SupplierQualification $qualification) {
                $supplier = $qualification->getSupplier();

                return $supplier->getName();
            })
        ;

        yield TextField::new('name', '资质名称')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('资质证书的完整名称')
            ->setFormTypeOption('attr', ['placeholder' => '例如：ISO9001质量管理体系认证'])
        ;

        yield ChoiceField::new('type', '资质类型')
            ->setColumns(3)
            ->setRequired(true)
            ->setChoices($this->getQualificationTypeChoices())
            ->setHelp('选择资质证书的类型分类')
        ;

        $statusField = EnumField::new('status', '资质状态');
        $statusField->setEnumCases(SupplierQualificationStatus::cases());
        yield $statusField
            ->setColumns(3)
            ->setHelp('当前资质的审核状态')
            ->hideOnForm()
        ;

        yield FormField::addPanel('证书信息')->setIcon('fa fa-certificate');

        yield TextField::new('certificateNumber', '证书编号')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('资质证书的唯一编号')
            ->setFormTypeOption('attr', ['placeholder' => '例如：CN-2024-001234'])
        ;

        yield TextField::new('issuingAuthority', '颁发机构')
            ->setColumns(4)
            ->setRequired(true)
            ->setHelp('颁发该资质证书的权威机构名称')
            ->setFormTypeOption('attr', ['placeholder' => '例如：中国质量认证中心'])
        ;

        yield BooleanField::new('isActive', '是否有效')
            ->setColumns(4)
            ->setHelp('标记该资质是否当前有效')
            ->renderAsSwitch(false)
        ;

        yield FormField::addPanel('有效期信息')->setIcon('fa fa-calendar');

        yield DateField::new('issuedDate', '颁发日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('资质证书的颁发日期')
        ;

        yield DateField::new('expiryDate', '到期日期')
            ->setColumns(3)
            ->setRequired(true)
            ->setHelp('资质证书的到期日期')
        ;

        yield from $this->getDetailFields($pageName);

        yield FormField::addPanel('证书文件')->setIcon('fa fa-file');

        yield TextField::new('filePath', '证书文件')
            ->setColumns(6)
            ->setHelp('上传资质证书的扫描件或电子版（支持PDF、图片格式）')
            ->setFormTypeOption('attr', ['placeholder' => '请选择证书文件'])
            ->hideOnIndex()
            ->formatValue(function ($value, SupplierQualification $qualification) {
                return $this->formatFilePath($qualification->getFilePath());
            })
        ;

        yield FormField::addPanel('备注说明')->setIcon('fa fa-comment');

        yield TextareaField::new('remarks', '备注说明')
            ->setColumns(12)
            ->setNumOfRows(4)
            ->setMaxLength(2000)
            ->setHelp('关于该资质的额外说明或备注信息')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['placeholder' => '请输入相关备注信息...'])
        ;

        yield FormField::addPanel('系统信息')->setIcon('fa fa-clock')->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->setColumns(3)
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('资质记录的创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->setColumns(3)
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setHelp('资质记录的最后更新时间')
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    private function getDetailFields(string $pageName): iterable
    {
        if (Crud::PAGE_DETAIL !== $pageName) {
            return;
        }

        yield IntegerField::new('validityDays', '有效期（天）')
            ->setColumns(2)
            ->formatValue(function ($value, SupplierQualification $qualification) {
                return (string) $qualification->getValidityDays() . ' 天';
            })
            ->setHelp('资质证书的总有效天数')
        ;

        yield TextField::new('daysUntilExpiryDisplay', '距离过期')
            ->onlyOnDetail()
            ->setColumns(2)
            ->formatValue(function ($value, SupplierQualification $qualification) {
                return $this->formatExpiryDays($qualification->getDaysUntilExpiry());
            })
            ->setHelp('距离资质过期的剩余天数')
        ;

        yield BooleanField::new('isExpired', '是否过期')
            ->setColumns(2)
            ->formatValue(function ($value, SupplierQualification $qualification) {
                return $qualification->isExpired() ?
                    '<span class="badge badge-danger">已过期</span>' :
                    '<span class="badge badge-success">有效</span>';
            })
            ->setHelp('资质证书当前是否已过期')
        ;
    }

    /**
     * @return array<FieldInterface>
     */
    private function getIndexFields(): array
    {
        return [
            IntegerField::new('id', 'ID'),
            AssociationField::new('supplier', '供应商')
                ->formatValue(function ($value, SupplierQualification $qualification) {
                    $supplier = $qualification->getSupplier();

                    return $supplier->getName();
                }),
            TextField::new('name', '资质名称'),
            ChoiceField::new('type', '类型')
                ->setChoices($this->getQualificationTypeChoices())
                ->formatValue(function ($value, SupplierQualification $qualification) {
                    return $this->formatQualificationType($qualification->getType());
                }),
            TextField::new('certificateNumber', '证书编号'),
            (function () {
                $statusField = EnumField::new('status', '状态');
                $statusField->setEnumCases(SupplierQualificationStatus::cases());

                return $statusField->formatValue(function ($value, SupplierQualification $qualification) {
                    return $this->formatQualificationStatus($qualification->getStatus());
                });
            })(),
            DateField::new('expiryDate', '到期日期'),
            BooleanField::new('isActive', '有效')
                ->renderAsSwitch(false)
                ->formatValue(function ($value, SupplierQualification $qualification) {
                    return $this->formatActiveStatus($qualification->getIsActive(), $qualification);
                }),
            DateTimeField::new('createTime', '创建时间')
                ->setFormat('yyyy-MM-dd HH:mm:ss'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function getQualificationTypeChoices(): array
    {
        return [
            '质量认证' => 'quality',
            '安全认证' => 'safety',
            '环境认证' => 'environment',
            '行业资质' => 'industry',
            '其他资质' => 'other',
        ];
    }

    private function formatExpiryDays(int $days): string
    {
        if ($days < 0) {
            return '<span class="badge badge-danger">已过期 ' . abs($days) . ' 天</span>';
        }
        if ($days <= 30) {
            return '<span class="badge badge-warning">' . $days . ' 天后过期</span>';
        }

        return '<span class="badge badge-success">' . $days . ' 天后过期</span>';
    }

    private function formatFilePath(?string $value): string
    {
        if (null === $value || '' === $value) {
            return '<span class="text-muted">未上传证书文件</span>';
        }
        $fileName = basename($value);

        return sprintf('<a href="%s" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-download"></i> %s</a>',
            $value, $fileName
        );
    }

    private function formatQualificationType(string $value): string
    {
        $typeMap = [
            'quality' => '<span class="badge badge-primary">质量认证</span>',
            'safety' => '<span class="badge badge-warning">安全认证</span>',
            'environment' => '<span class="badge badge-success">环境认证</span>',
            'industry' => '<span class="badge badge-info">行业资质</span>',
            'other' => '<span class="badge badge-secondary">其他资质</span>',
        ];

        return $typeMap[$value] ?? $value;
    }

    private function formatQualificationStatus(SupplierQualificationStatus|string $value): string
    {
        $statusValue = $value instanceof SupplierQualificationStatus ? $value->value : $value;

        $statusMap = [
            'draft' => '<span class="badge badge-secondary">草稿</span>',
            'pending_review' => '<span class="badge badge-warning">待审核</span>',
            'approved' => '<span class="badge badge-success">已批准</span>',
            'rejected' => '<span class="badge badge-danger">已拒绝</span>',
            'expired' => '<span class="badge badge-dark">已过期</span>',
        ];

        return $statusMap[$statusValue] ?? $statusValue;
    }

    private function formatActiveStatus(bool $value, SupplierQualification $qualification): string
    {
        if ($qualification->isExpired()) {
            return '<span class="badge badge-danger">已过期</span>';
        }

        return $value ?
            '<span class="badge badge-success">有效</span>' :
            '<span class="badge badge-secondary">无效</span>';
    }

    /**
     * 批准资质
     */
    #[AdminAction(routeName: 'approve', routePath: '{entityId}/approve')]
    public function approve(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        /** @var SupplierQualification $qualification */
        $qualification = $entity->getInstance();

        $qualification->setStatus(SupplierQualificationStatus::APPROVED);

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $entityManager->flush();

        $this->addFlash('success', sprintf('已批准资质：%s', $qualification->getName()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    /**
     * 拒绝资质
     */
    #[AdminAction(routeName: 'reject', routePath: '{entityId}/reject')]
    public function reject(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        /** @var SupplierQualification $qualification */
        $qualification = $entity->getInstance();

        $qualification->setStatus(SupplierQualificationStatus::REJECTED);

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $entityManager->flush();

        $this->addFlash('warning', sprintf('已拒绝资质：%s', $qualification->getName()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    /**
     * 资质续期
     */
    #[AdminAction(routeName: 'renew', routePath: '{entityId}/renew')]
    public function renew(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        /** @var SupplierQualification $qualification */
        $qualification = $entity->getInstance();

        // 将状态设为待审核,需要重新审批续期
        $qualification->setStatus(SupplierQualificationStatus::PENDING_REVIEW);

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $entityManager->flush();

        $this->addFlash('info', sprintf('资质 %s 已标记为需要续期，请更新有效期信息后重新审核', $qualification->getName()));

        // 跳转到编辑页面让用户更新到期日期
        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'edit',
            'crudControllerFqcn' => self::class,
            'entityId' => $qualification->getId(),
        ]));
    }

    /**
     * 重写index方法以安全处理AdminContext
     */
    public function index(AdminContext $context): Response|KeyValueStore
    {
        return $this->safeIndex($context);
    }
}
