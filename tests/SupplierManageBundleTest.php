<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SupplierManageBundle\SupplierManageBundle;

/**
 * @internal
 */
#[CoversClass(SupplierManageBundle::class)]
#[RunTestsInSeparateProcesses]
final class SupplierManageBundleTest extends AbstractBundleTestCase
{
}
