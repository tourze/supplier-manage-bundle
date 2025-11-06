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
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;
use Tourze\SupplierManageBundle\Controller\Admin\Traits\SafeAdminContextTrait;
use Tourze\SupplierManageBundle\Entity\SupplierContact;

/**
 * 供应商联系人管理控制器
 * @extends AbstractCrudController<SupplierContact>
 */
#[AdminCrud(routePath: '/supplier/contact', routeName: 'supplier_contact')]
final class SupplierContactCrudController extends AbstractCrudController
{
    use SafeAdminContextTrait;

    public static function getEntityFqcn(): string
    {
        return SupplierContact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('供应商联系人')
            ->setEntityLabelInPlural('供应商联系人管理')
            ->setPageTitle('index', '供应商联系人列表')
            ->setPageTitle('new', '添加联系人')
            ->setPageTitle('edit', '编辑联系人')
            ->setPageTitle('detail', '联系人详情')
            ->setDefaultSort(['id' => 'DESC'])
            ->setPaginatorPageSize(30)
            ->setSearchFields(['name', 'email', 'phone', 'position', 'supplier.name'])
            ->addFormTheme('@EasyAdmin/crud/form_theme.html.twig')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        $setPrimaryAction = Action::new('setPrimary', '设为主联系人', 'fa fa-star')
            ->linkToCrudAction('makePrimary')
            ->addCssClass('btn btn-warning')
            ->displayIf(function (SupplierContact $contact) {
                return !$contact->getIsPrimary();
            })
        ;

        $removePrimaryAction = Action::new('removePrimary', '取消主联系人', 'fa fa-star-o')
            ->linkToCrudAction('removePrimary')
            ->addCssClass('btn btn-secondary')
            ->displayIf(function (SupplierContact $contact) {
                return $contact->getIsPrimary();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, $setPrimaryAction)
            ->add(Crud::PAGE_DETAIL, $setPrimaryAction)
            ->add(Crud::PAGE_INDEX, $removePrimaryAction)
            ->add(Crud::PAGE_DETAIL, $removePrimaryAction)
            ->setPermission(Action::NEW, 'ROLE_ADMIN')
            ->setPermission(Action::EDIT, 'ROLE_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_ADMIN')
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addTab('基本信息');

        yield IntegerField::new('id', 'ID')
            ->setColumns(1)
            ->onlyOnIndex()
        ;

        yield AssociationField::new('supplier', '所属供应商')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('选择该联系人所属的供应商')
            ->autocomplete()
            ->formatValue(function ($value, SupplierContact $contact) {
                $supplier = $contact->getSupplier();

                return $supplier->getName();
            })
        ;

        yield TextField::new('name', '联系人姓名')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('联系人的真实姓名')
        ;

        yield TextField::new('position', '职位')
            ->setColumns(6)
            ->setHelp('联系人在公司的职务，如：采购经理、总经理等')
        ;

        yield FormField::addTab('联系方式');

        yield EmailField::new('email', '邮箱地址')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('用于业务联系的邮箱地址')
        ;

        yield TelephoneField::new('phone', '电话号码')
            ->setColumns(6)
            ->setRequired(true)
            ->setHelp('联系电话，支持手机或座机号码')
        ;

        yield FormField::addTab('设置');

        yield BooleanField::new('isPrimary', '主要联系人')
            ->setColumns(6)
            ->setHelp('是否为该供应商的主要联系人，每个供应商只能有一个主要联系人')
            ->renderAsSwitch(false)
        ;

        yield FormField::addTab('系统信息')->hideOnForm();

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setFormat('yyyy-MM-dd HH:mm:ss')
            ->setColumns(6)
        ;

        // 列表页显示字段
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                IntegerField::new('id', 'ID'),
                AssociationField::new('supplier', '供应商')
                    ->formatValue(function ($value, SupplierContact $contact) {
                        $supplier = $contact->getSupplier();

                        return $supplier->getName();
                    }),
                TextField::new('name', '姓名'),
                TextField::new('position', '职位'),
                EmailField::new('email', '邮箱'),
                TelephoneField::new('phone', '电话'),
                BooleanField::new('isPrimary', '主联系人')
                    ->renderAsSwitch(false)
                    ->formatValue(function ($value) {
                        return $value ? '是' : '否';
                    }),
                DateTimeField::new('createTime', '创建时间')
                    ->setFormat('yyyy-MM-dd HH:mm:ss'),
            ];
        }
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('supplier'))
            ->add('name')
            ->add('position')
            ->add('email')
            ->add('phone')
            ->add(BooleanFilter::new('isPrimary'))
            ->add(DateTimeFilter::new('createTime'))
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
     * 设置为主要联系人
     */
    #[AdminAction(routeName: 'make_primary', routePath: '{entityId}/make-primary')]
    public function makePrimary(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        /** @var SupplierContact $contact */
        $contact = $entity->getInstance();

        // 先将该供应商的其他联系人设为非主要联系人
        $supplier = $contact->getSupplier();
        foreach ($supplier->getContacts() as $existingContact) {
            if ($existingContact->getIsPrimary()) {
                $existingContact->setIsPrimary(false);
            }
        }

        // 设置当前联系人为主要联系人
        $contact->setIsPrimary(true);

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $entityManager->flush();

        $this->addFlash('success', sprintf('已将 %s 设置为 %s 的主要联系人', $contact->getName(), $supplier->getName()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
        ]));
    }

    /**
     * 取消主要联系人
     */
    #[AdminAction(routeName: 'remove_primary', routePath: '{entityId}/remove-primary')]
    public function removePrimary(): Response
    {
        $context = $this->getContext();
        if (null === $context) {
            $this->addFlash('danger', '上下文不存在');

            return $this->redirectToRoute('admin');
        }

        $entity = $context->getEntity();
        /** @var SupplierContact $contact */
        $contact = $entity->getInstance();

        $contact->setIsPrimary(false);

        $doctrine = $this->container->get('doctrine');
        assert($doctrine instanceof Registry);
        $entityManager = $doctrine->getManager();
        assert($entityManager instanceof EntityManagerInterface);
        $entityManager->flush();

        $this->addFlash('success', sprintf('已取消 %s 的主要联系人设置', $contact->getName()));

        return $this->redirect($this->generateUrl('admin', [
            'crudAction' => 'index',
            'crudControllerFqcn' => self::class,
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
