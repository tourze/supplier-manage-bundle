<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\SupplierType;

class RegistrationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => '供应商名称',
                'required' => true,
            ])
            ->add('legalName', TextType::class, [
                'label' => '法人名称',
                'required' => true,
            ])
            ->add('legalAddress', TextareaType::class, [
                'label' => '法人地址',
                'required' => true,
            ])
            ->add('registrationNumber', TextType::class, [
                'label' => '注册号',
                'required' => true,
            ])
            ->add('taxNumber', TextType::class, [
                'label' => '税号',
                'required' => true,
            ])
            ->add('supplierType', ChoiceType::class, [
                'label' => '供应商类型',
                'choices' => [
                    '供应商' => SupplierType::SUPPLIER,
                    '商家' => SupplierType::MERCHANT,
                ],
                'required' => true,
            ])
            ->add('cooperationModel', ChoiceType::class, [
                'label' => '合作模式',
                'choices' => [
                    '分销' => CooperationModel::DISTRIBUTION,
                    '代销' => CooperationModel::CONSIGNMENT,
                    '合资' => CooperationModel::JOINT_VENTURE,
                ],
                'required' => false,
            ])
            ->add('businessCategory', TextType::class, [
                'label' => '业务类别',
                'required' => false,
            ])
            ->add('isWarehouse', CheckboxType::class, [
                'label' => '是否为仓库',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Supplier::class,
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'supplier_registration',
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'supplier_registration';
    }
}
