# 供应商管理 Bundle 技术设计文档

## 一、技术概览

### 1.1 架构模式

本 Bundle 采用**扁平化 Service 层架构**，遵循 Symfony Bundle 开发最佳实践：

- **实体层**：贫血模型，只包含数据和 getter/setter
- **服务层**：扁平化设计，直接处理业务逻辑，不分层
- **存储层**：Repository 模式，负责数据访问
- **表现层**：Controller 仅在需要时创建，不主动暴露 API

### 1.2 核心设计原则

1. **KISS（保持简单）**：优先可读性，避免过度抽象
2. **YAGNI（不做不需要的）**：只实现当前需求，不预设未来功能
3. **SOLID**：确保设计合理，职责清晰
4. **配置简单**：通过环境变量 `$_ENV` 读取配置，不创建 Configuration 类

### 1.3 技术栈决策

- **框架**：Symfony 7.3+
- **ORM**：Doctrine ORM
- **工作流**：Symfony Workflow 组件
- **验证**：Symfony Validator
- **表单**：Symfony Form 组件

## 二、实体设计

### 2.1 核心实体结构

```php
packages/supplier-manage-bundle/src/Entity/
├── Supplier.php                    # 供应商主实体
├── SupplierContact.php             # 供应商联系人
├── SupplierQualification.php       # 供应商资质
├── Contract.php                    # 合同
├── ContractTemplate.php            # 合同模板
├── PerformanceEvaluation.php       # 绩效评估
├── EvaluationTemplate.php          # 评估模板
├── EvaluationItem.php              # 评估项
└── Workflow/
    ├── WorkflowInstance.php        # 工作流实例
    ├── WorkflowTransition.php      # 工作流转换
    └── WorkflowHistory.php         # 工作流历史
```

### 2.2 实体关系设计

```
Supplier (1) ←→ (N) SupplierContact
Supplier (1) ←→ (N) SupplierQualification
Supplier (1) ←→ (N) Contract
Supplier (1) ←→ (N) PerformanceEvaluation
Contract (1) ←→ (1) ContractTemplate
PerformanceEvaluation (1) ←→ (1) EvaluationTemplate
PerformanceEvaluation (1) ←→ (N) EvaluationItem
```

### 2.3 实体设计原则

1. **贫血模型**：实体只包含数据，不包含业务逻辑
2. **完整验证**：使用 Symfony Validator 注解
3. **生命周期回调**：使用 Doctrine 生命周期事件
4. **软删除**：关键实体支持软删除

## 三、服务层设计

### 3.1 服务架构

```php
packages/supplier-manage-bundle/src/Service/
├── SupplierService.php             # 供应商管理服务
├── ContractService.php             # 合同管理服务
├── PerformanceService.php          # 绩效评估服务
├── WorkflowService.php             # 工作流服务
├── ApprovalService.php             # 审批服务
└── NotificationService.php         # 通知服务
```

### 3.2 服务设计示例

```php
class SupplierService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupplierRepository $repository,
        private readonly WorkflowService $workflowService,
        private readonly NotificationService $notificationService,
    ) {}
    
    public function createSupplier(array $data): Supplier
    {
        // 1. 验证数据
        $this->validateSupplierData($data);
        
        // 2. 检查重复
        $this->checkDuplicate($data);
        
        // 3. 创建实体
        $supplier = new Supplier();
        $supplier->setName($data['name']);
        $supplier->setLegalName($data['legal_name']);
        // ... 设置其他属性
        
        // 4. 保存
        $this->entityManager->persist($supplier);
        $this->entityManager->flush();
        
        // 5. 启动工作流
        $this->workflowService->startWorkflow(
            $supplier,
            'supplier_registration'
        );
        
        return $supplier;
    }
}
```

### 3.3 服务职责划分

| 服务 | 主要职责 |
|-----|---------|
| SupplierService | 供应商 CRUD、状态管理、搜索统计 |
| ContractService | 合同全生命周期管理、模板管理 |
| PerformanceService | 绩效评估执行、结果计算、报告生成 |
| WorkflowService | 工作流引擎、流程定义、状态转换 |
| ApprovalService | 审批逻辑、权限检查、历史记录 |
| NotificationService | 邮件通知、消息推送 |

## 四、工作流设计

### 4.1 工作流类型

1. **供应商注册审批流**
   - 状态：draft → pending_review → approved/rejected → active/terminated
   
2. **合同审批流**
   - 状态：draft → pending_review → approved/rejected → active/terminated/expired
   
3. **绩效评估审批流**
   - 状态：draft → pending_review → confirmed/rejected

### 4.2 工作流配置

```yaml
# workflows.yaml
framework:
    workflows:
        supplier_registration:
            type: state_machine
            marking_store:
                type: method
                property: 'status'
            supports:
                - Tourze\SupplierManageBundle\Entity\Supplier
            initial_marking: draft
            places:
                - draft
                - pending_review
                - approved
                - rejected
                - active
                - suspended
                - terminated
            transitions:
                submit:
                    from: draft
                    to: pending_review
                approve:
                    from: pending_review
                    to: approved
                reject:
                    from: pending_review
                    to: rejected
                activate:
                    from: approved
                    to: active
                suspend:
                    from: active
                    to: suspended
                terminate:
                    from: [active, suspended]
                    to: terminated
```

## 五、事件系统

### 5.1 事件定义

```php
packages/supplier-manage-bundle/src/Event/
├── SupplierRegisteredEvent.php      # 供应商注册事件
├── SupplierApprovedEvent.php        # 供应商批准事件
├── SupplierRejectedEvent.php        # 供应商拒绝事件
├── ContractCreatedEvent.php         # 合同创建事件
├── ContractApprovedEvent.php       # 合同批准事件
├── EvaluationCompletedEvent.php     # 评估完成事件
└── WorkflowTransitionEvent.php     # 工作流转换事件
```

### 5.2 事件监听器

```php
packages/supplier-manage-bundle/src/EventListener/
├── SupplierEventListener.php        # 供应商事件监听
├── ContractEventListener.php        # 合同事件监听
├── EvaluationEventListener.php       # 评估事件监听
└── WorkflowEventListener.php        # 工作流事件监听
```

## 六、存储层设计

### 6.1 Repository 模式

```php
packages/supplier-manage-bundle/src/Repository/
├── SupplierRepository.php          # 供应商仓储
├── ContractRepository.php          # 合同仓储
├── PerformanceRepository.php       # 绩效仓储
└── WorkflowRepository.php          # 工作流仓储
```

### 6.2 自定义查询方法

```php
class SupplierRepository extends ServiceEntityRepository
{
    public function findByStatus(array $status): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.status IN (:status)')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
    
    public function searchSuppliers(array $criteria): array
    {
        $qb = $this->createQueryBuilder('s');
        
        if (!empty($criteria['name'])) {
            $qb->andWhere('s.name LIKE :name')
               ->setParameter('name', '%'.$criteria['name'].'%');
        }
        
        if (!empty($criteria['status'])) {
            $qb->andWhere('s.status IN (:status)')
               ->setParameter('status', $criteria['status']);
        }
        
        return $qb->getQuery()->getResult();
    }
}
```

## 七、表单设计

### 7.1 表单类型

```php
packages/supplier-manage-bundle/src/Form/
├── Supplier/
│   ├── SupplierType.php            # 供应商基础信息
│   ├── RegistrationType.php        # 注册表单
│   └── ContactType.php             # 联系人表单
├── Contract/
│   ├── ContractType.php            # 合同表单
│   └── TemplateType.php            # 模板表单
└── Performance/
    ├── EvaluationType.php          # 评估表单
    └── TemplateType.php            # 评估模板表单
```

### 7.2 表单验证

使用 Symfony Validator 组件进行验证：

```php
class SupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
            ])
            ->add('legalName', TextType::class, [
                'constraints' => [
                    new NotBlank(),
                    new Length(['max' => 255]),
                ],
            ])
            // ... 其他字段
        ;
    }
}
```

## 八、扩展机制

### 8.1 事件扩展点

通过事件系统提供扩展点：

- 供应商注册前后
- 合同审批前后
- 绩效评估完成时
- 工作流状态转换时

### 8.2 服务扩展

所有服务都通过接口定义，允许替换实现：

```php
interface SupplierServiceInterface
{
    public function createSupplier(array $data): Supplier;
    public function updateSupplier(Supplier $supplier, array $data): void;
    public function deleteSupplier(Supplier $supplier): void;
}
```

## 九、测试策略

### 9.1 单元测试

- **Entity 测试**：验证实体属性和方法
- **Service 测试**：测试业务逻辑
- **Repository 测试**：测试查询方法
- **Form 测试**：测试表单验证

### 9.2 集成测试

- **工作流测试**：验证状态转换
- **事件测试**：验证事件发布和监听
- **数据库测试**：验证数据持久化

### 9.3 测试覆盖率要求

- Entity：≥ 90%
- Service：≥ 95%
- Repository：≥ 85%
- Form：≥ 80%

## 十、性能考虑

### 10.1 数据库优化

1. **索引策略**
   - 外键字段创建索引
   - 查询条件字段创建索引
   - 状态字段创建索引

2. **查询优化**
   - 避免 N+1 查询
   - 使用 JOIN 优化关联查询
   - 分页查询使用 LIMIT

### 10.2 缓存策略

1. **查询缓存**
   - 频繁查询的结果缓存
   - 使用 Redis 缓存

2. **元数据缓存**
   - Doctrine 元数据缓存
   - 使用 APCu 或 Redis

## 十一、安全考虑

### 11.1 数据安全

1. **输入验证**
   - 所有用户输入严格验证
   - 使用 Symfony Validator

2. **SQL 注入防护**
   - 使用参数化查询
   - 避免 SQL 拼接

### 11.2 访问控制

1. **权限检查**
   - 敏感操作权限验证
   - 基于角色的访问控制

2. **审计日志**
   - 关键操作记录日志
   - 便于追踪和审计

## 十二、部署和配置

### 12.1 Bundle 注册

```php
// bundles.php
return [
    // ...
    Tourze\SupplierManageBundle\SupplierManageBundle::class => ['all' => true],
];
```

### 12.2 环境变量配置

```env
# 供应商管理配置
SUPPLIER_AUTO_APPROVE=false
SUPPLIER_DEFAULT_STATUS=draft
SUPPLIER_MAX_QUALIFICATIONS=10
SUPPLIER_CONTRACT_EXPIRE_DAYS=30
```

## 十三、API 设计（按需实现）

注意：根据架构合规要求，**不主动创建 HTTP API**。所有业务逻辑封装在 Service 层，仅在用户明确要求时才暴露 API 端点。

如果需要 API，可以基于以下结构：

```php
packages/supplier-manage-bundle/src/Controller/
├── Api/
│   ├── SupplierController.php      # 供应商 API
│   ├── ContractController.php      # 合同 API
│   └── PerformanceController.php   # 绩效 API
└── Admin/
    ├── SupplierController.php      # 供应商管理
    ├── ContractController.php      # 合同管理
    └── PerformanceController.php   # 绩效管理
```

## 十四、架构合规性检查

### ✅ 符合标准

- [x] 扁平化 Service 层架构
- [x] 贫血模型实体设计
- [x] 不创建 Configuration 类
- [x] 配置通过 $_ENV 读取
- [x] 不主动创建 HTTP API
- [x] 遵循 Symfony Bundle 标准
- [x] 单一职责原则
- [x] 依赖注入使用正确

### ⚠️ 注意事项

- 实体保持贫血模型，业务逻辑在 Service 层
- 避免过度抽象，遵循 YAGNI 原则
- 保持代码简洁，优先可读性
- 不使用 DDD 分层架构

## 十五、实施计划

### 阶段一：核心实体（1 周）
1. 完善现有 Supplier 实体
2. 实现 SupplierContact 和 SupplierQualification
3. 创建对应的 Repository

### 阶段二：服务层（1 周）
1. 完善 SupplierService
2. 实现 ContractService
3. 实现 PerformanceService

### 阶段三：工作流（1 周）
1. 配置工作流定义
2. 实现 WorkflowService
3. 实现审批流程

### 阶段四：事件和通知（3 天）
1. 定义事件
2. 实现监听器
3. 集成通知系统

### 阶段五：测试和优化（4 天）
1. 编写测试用例
2. 性能优化
3. 文档完善

## 十六、技术决策理由

1. **选择扁平化架构**：简化代码结构，提高可维护性
2. **使用贫血模型**：符合项目架构标准，便于数据持久化
3. **Symfony Workflow**：成熟的解决方案，减少自定义开发
4. **事件驱动**：松耦合设计，便于扩展
5. **环境变量配置**：符合项目标准，简化配置管理

---

**设计文档完成日期**：2025-08-09

**设计版本**：v1.0

**下一步**：等待用户审批，然后进入任务分解阶段。