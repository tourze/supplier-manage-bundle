<?php

declare(strict_types=1);

namespace Tourze\SupplierManageBundle\Service;

use Tourze\SupplierManageBundle\Entity\Supplier;
use Tourze\SupplierManageBundle\Enum\CooperationModel;
use Tourze\SupplierManageBundle\Enum\SupplierStatus;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Exception\SupplierException;
use Tourze\SupplierManageBundle\Repository\SupplierRepository;

class SupplierService
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository,
    ) {
    }

    /**
     * 创建新供应商
     *
     * @param array{
     *   name?: string,
     *   legalName?: string,
     *   legalAddress?: string,
     *   registrationNumber?: string,
     *   taxNumber?: string,
     *   supplierType?: string,
     *   cooperationModel?: string|null,
     *   businessCategory?: string|null,
     *   isWarehouse?: bool
     * } $data
     */
    public function createSupplier(array $data): Supplier
    {
        $supplier = new Supplier();

        if (isset($data['name'])) {
            $supplier->setName($data['name']);
        }

        if (isset($data['legalName'])) {
            $supplier->setLegalName($data['legalName']);
        }

        if (isset($data['legalAddress'])) {
            $supplier->setLegalAddress($data['legalAddress']);
        }

        if (isset($data['registrationNumber'])) {
            $supplier->setRegistrationNumber($data['registrationNumber']);
        }

        if (isset($data['taxNumber'])) {
            $supplier->setTaxNumber($data['taxNumber']);
        }

        if (isset($data['supplierType'])) {
            $supplier->setSupplierType(SupplierType::from($data['supplierType']));
        }

        if (isset($data['cooperationModel'])) {
            $supplier->setCooperationModel(CooperationModel::from($data['cooperationModel']));
        }

        if (isset($data['businessCategory'])) {
            $supplier->setBusinessCategory($data['businessCategory']);
        }

        if (isset($data['isWarehouse'])) {
            $supplier->setIsWarehouse($data['isWarehouse']);
        }

        return $supplier;
    }

    /**
     * 更新供应商信息
     *
     * @param array{
     *   name?: string,
     *   legalName?: string,
     *   legalAddress?: string,
     *   supplierType?: string,
     *   cooperationModel?: string|null,
     *   businessCategory?: string|null,
     *   isWarehouse?: bool
     * } $data
     */
    public function updateSupplier(Supplier $supplier, array $data): Supplier
    {
        if (isset($data['name'])) {
            $supplier->setName($data['name']);
        }

        if (isset($data['legalName'])) {
            $supplier->setLegalName($data['legalName']);
        }

        if (isset($data['legalAddress'])) {
            $supplier->setLegalAddress($data['legalAddress']);
        }

        if (isset($data['supplierType'])) {
            $supplier->setSupplierType(SupplierType::from($data['supplierType']));
        }

        if (isset($data['cooperationModel'])) {
            $supplier->setCooperationModel(CooperationModel::from($data['cooperationModel']));
        }

        if (isset($data['businessCategory'])) {
            $supplier->setBusinessCategory($data['businessCategory']);
        }

        if (isset($data['isWarehouse'])) {
            $supplier->setIsWarehouse($data['isWarehouse']);
        }

        return $supplier;
    }

    /**
     * 提交审核
     */
    public function submitForReview(Supplier $supplier): void
    {
        if (SupplierStatus::DRAFT !== $supplier->getStatus()) {
            throw new SupplierException('只有草稿状态的供应商可以提交审核');
        }

        $supplier->setStatus(SupplierStatus::PENDING_REVIEW);
    }

    /**
     * 批准供应商
     */
    public function approveSupplier(Supplier $supplier): void
    {
        if (SupplierStatus::PENDING_REVIEW !== $supplier->getStatus()) {
            throw new SupplierException('只有待审核状态的供应商可以批准');
        }

        $supplier->setStatus(SupplierStatus::APPROVED);
    }

    /**
     * 拒绝供应商
     */
    public function rejectSupplier(Supplier $supplier, string $reason): void
    {
        if (SupplierStatus::PENDING_REVIEW !== $supplier->getStatus()) {
            throw new SupplierException('只有待审核状态的供应商可以拒绝');
        }

        $supplier->setStatus(SupplierStatus::REJECTED);
    }

    /**
     * 暂停供应商
     */
    public function suspendSupplier(Supplier $supplier, string $reason): void
    {
        if (SupplierStatus::APPROVED !== $supplier->getStatus()) {
            throw new SupplierException('只有已批准的供应商可以暂停');
        }

        $supplier->setStatus(SupplierStatus::SUSPENDED);
    }

    /**
     * 激活供应商
     */
    public function activateSupplier(Supplier $supplier): void
    {
        if (SupplierStatus::SUSPENDED !== $supplier->getStatus()) {
            throw new SupplierException('只有暂停状态的供应商可以激活');
        }

        $supplier->setStatus(SupplierStatus::APPROVED);
    }

    /**
     * 终止合作
     */
    public function terminateSupplier(Supplier $supplier, string $reason): void
    {
        if (!in_array($supplier->getStatus(), [
            SupplierStatus::APPROVED,
            SupplierStatus::SUSPENDED,
        ], true)) {
            throw new SupplierException('只有已批准或暂停的供应商可以终止合作');
        }

        $supplier->setStatus(SupplierStatus::TERMINATED);
    }

    /**
     * 获取活跃供应商列表
     *
     * @return Supplier[]
     */
    public function getActiveSuppliers(): array
    {
        return $this->supplierRepository->findActiveSuppliers();
    }

    /**
     * 搜索供应商
     *
     * @return Supplier[]
     */
    public function searchSuppliers(string $query, ?string $status = null, ?string $supplierType = null): array
    {
        return $this->supplierRepository->search($query, $status, $supplierType);
    }

    /**
     * 获取供应商统计信息
     *
     * @return array{
     *   draft: int,
     *   pending_review: int,
     *   approved: int,
     *   rejected: int,
     *   suspended: int,
     *   terminated: int,
     *   total: int
     * }
     */
    public function getSupplierStatistics(): array
    {
        $statistics = $this->supplierRepository->countByStatus();

        // 确保所有状态都存在
        $defaultStats = [
            'draft' => 0,
            'pending_review' => 0,
            'approved' => 0,
            'rejected' => 0,
            'suspended' => 0,
            'terminated' => 0,
        ];

        $mergedStats = array_merge($defaultStats, $statistics);

        return [
            'draft' => $mergedStats['draft'],
            'pending_review' => $mergedStats['pending_review'],
            'approved' => $mergedStats['approved'],
            'rejected' => $mergedStats['rejected'],
            'suspended' => $mergedStats['suspended'],
            'terminated' => $mergedStats['terminated'],
            'total' => (int) array_sum($mergedStats),
        ];
    }

    /**
     * 验证供应商数据
     *
     * @param array<string, mixed> $data
     */
    public function validateSupplierData(array $data): bool
    {
        $requiredFields = [
            'name',
            'legalName',
            'legalAddress',
            'registrationNumber',
            'taxNumber',
        ];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || '' === $data[$field]) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查注册号是否重复
     */
    public function checkDuplicateRegistration(string $registrationNumber): bool
    {
        $existing = $this->supplierRepository->findOneBy([
            'registrationNumber' => $registrationNumber,
        ]);

        return null !== $existing;
    }

    /**
     * 检查税号是否重复
     */
    public function checkDuplicateTaxNumber(string $taxNumber): bool
    {
        $existing = $this->supplierRepository->findOneBy([
            'taxNumber' => $taxNumber,
        ]);

        return null !== $existing;
    }
}
