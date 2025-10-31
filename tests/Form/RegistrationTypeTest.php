<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests\Form;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Form\RegistrationType;

/**
 * @internal
 */
#[CoversClass(RegistrationType::class)]
class RegistrationTypeTest extends TestCase
{
    public function testBuildForm(): void
    {
        // Given
        $addCount = 0;
        $formType = new RegistrationType();

        $reflection = new \ReflectionClass($formType);
        $method = $reflection->getMethod('buildForm');

        // When & Then: just verify the method can be called without Mock
        $this->assertTrue($method->isPublic());
        $this->assertTrue(2 === $method->getNumberOfParameters());
    }

    public function testConfigureOptions(): void
    {
        // Given
        $formType = new RegistrationType();

        $reflection = new \ReflectionClass($formType);
        $method = $reflection->getMethod('configureOptions');

        // When & Then: just verify the method can be called without Mock
        $this->assertTrue($method->isPublic());
        $this->assertTrue(1 === $method->getNumberOfParameters());
    }

    public function testGetBlockPrefix(): void
    {
        // When & Then
        $formType = new RegistrationType();
        $this->assertEquals('supplier_registration', $formType->getBlockPrefix());
    }
}
