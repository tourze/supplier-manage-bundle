# supplier-manage-bundle

[English](README.md) | [中文](README.zh-CN.md)

# 通用供应商管理 Symfony Bundle

一个功能全面的供应商管理 Symfony Bundle，实现供应商从注册到绩效评估的全生命周期管理。

## 📋 功能特性

### 核心功能
- **供应商管理**：供应商信息维护、状态管理、分类管理
- **联系人管理**：供应商联系人的增删改查
- **资质管理**：供应商资质证件的审核与管理
- **合同管理**：供应商合同的创建、审批与跟踪
- **绩效评估**：供应商绩效的量化评估与历史记录
- **工作流集成**：基于 Symfony Workflow 的审批流程

### 技术特性
- **EasyAdmin 集成**：开箱即用的管理后台
- **RESTful API**：完整的 REST API 支持
- **工作流引擎**：基于 Symfony Workflow 的流程管理
- **数据验证**：使用 Symfony Validator 进行数据校验
- **索引优化**：关键字段自动索引，提升查询性能
- **时间戳管理**：自动管理创建和更新时间

## 🚀 安装

使用 Composer 安装：

```bash
composer require tourze/supplier-manage-bundle
```

## ⚙️ 配置

### 1. 注册 Bundle

在 `config/bundles.php` 中注册：

```php
return [
    // ...
    Tourze\SupplierManageBundle\SupplierManageBundle::class => ['all' => true],
];
```

### 2. 数据库配置

确保已正确配置 Doctrine 数据库连接：

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        url: '%env(resolve:DATABASE_URL)%'
    orm:
        auto_generate_proxy_classes: true
        enable_lazy_ghost_objects: true
        report_fields_where_declared: true
        validate_xml_mapping: true
        naming_strategy: doctrine.orm.naming_strategy.underscore_number_aware
        auto_mapping: true
        mappings:
            SupplierManageBundle:
                type: attribute
                dir: '%kernel.project_dir%/packages/supplier-manage-bundle/src/Entity'
                prefix: 'Tourze\SupplierManageBundle\Entity'
                alias: SupplierManageBundle
```

### 3. 创建数据库表

```bash
php bin/console doctrine:schema:create
# 或更新现有架构
php bin/console doctrine:schema:update --force
```

## 📖 使用方法

### 基本使用

#### 创建供应商

```php
use Tourze\SupplierManageBundle\Service\SupplierService;
use Tourze\SupplierManageBundle\Enum\SupplierType;
use Tourze\SupplierManageBundle\Enum\CooperationModel;

class SupplierController
{
    public function __construct(
        private readonly SupplierService $supplierService
    ) {}

    public function createSupplier(): Supplier
    {
        return $this->supplierService->create([
            'name' => '示例供应商',
            'legalName' => '示例供应商有限公司',
            'legalAddress' => '北京市朝阳区xxx街道',
            'registrationNumber' => '91110000000000000X',
            'taxNumber' => '91110000000000000X',
            'supplierType' => SupplierType::GENERAL,
            'cooperationModel' => CooperationModel::LONG_TERM,
            'contactPerson' => '张三',
            'contactPhone' => '13800138000',
            'contactEmail' => 'supplier@example.com',
            'bankName' => '中国银行',
            'bankAccount' => '6210000000000000000'
        ]);
    }
}
```

#### 查询供应商

```php
use Tourze\SupplierManageBundle\Repository\SupplierRepository;

class SupplierController
{
    public function __construct(
        private readonly SupplierRepository $supplierRepository
    ) {}

    public function findSupplier(int $id): ?Supplier
    {
        return $this->supplierRepository->find($id);
    }

    public function searchSuppliers(string $keyword): array
    {
        return $this->supplierRepository->findByName($keyword);
    }
}
```

#### 管理联系人

```php
use Tourze\SupplierManageBundle\Entity\SupplierContact;
use Tourze\SupplierManageBundle\Repository\SupplierContactRepository;

class ContactController
{
    public function createContact(Supplier $supplier): SupplierContact
    {
        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('李四');
        $contact->setPosition('销售经理');
        $contact->setPhone('13900139000');
        $contact->setEmail('lisi@supplier.com');
        $contact->setIsPrimary(true);

        return $contact;
    }
}
```

### 使用 EasyAdmin 后台

本 Bundle 集成了 EasyAdmin，提供开箱即用的管理后台：

1. 确保已安装并配置 EasyAdmin Bundle
2. 访问 `/admin` 路径即可看到供应商管理菜单
3. 支持以下实体的管理：
    - 供应商 (Supplier)
    - 供应商联系人 (SupplierContact)
    - 供应商资质 (SupplierQualification)
    - 合同 (Contract)
    - 绩效评估 (PerformanceEvaluation)
    - 评估项 (EvaluationItem)

### 工作流集成

基于 Symfony Workflow 组件实现供应商状态流转：

```yaml
# config/packages/workflow.yaml
framework:
    workflows:
        supplier_status:
            type: 'state_machine'
            supports:
                - Tourze\SupplierManageBundle\Entity\Supplier
            places:
                - pending     # 待审核
                - approved    # 已通过
                - rejected    # 已拒绝
                - suspended   # 已暂停
                - archived    # 已归档
            transitions:
                apply:
                    from: pending
                    to: approved
                reject:
                    from: pending
                    to: rejected
                suspend:
                    from: approved
                    to: suspended
                reactivate:
                    from: suspended
                    to: approved
                archive:
                    from: [approved, rejected, suspended]
                    to: archived
```

## 🏗️ 实体模型

### Supplier（供应商）
- 基本信息：名称、法人、注册号、税号等
- 业务信息：类型、合作模式、状态等
- 联系信息：联系人、电话、邮箱、银行账户等

### SupplierContact（供应商联系人）
- 关联供应商的多个联系人
- 支持设置主联系人标识
- 包含职位、联系方式等详细信息

### SupplierQualification（供应商资质）
- 管理供应商的各类资质证明
- 支持资质审核状态跟踪
- 记录有效期和提醒功能

### Contract（合同）
- 供应商合同信息管理
- 支持合同状态跟踪
- 记录合同金额、期限等关键信息

### PerformanceEvaluation（绩效评估）
- 供应商绩效评估记录
- 支持自定义评估模板
- 量化评分和等级管理

## 🔧 配置选项

### 环境变量配置

```bash
# .env
# 供应商管理相关配置
SUPPLIER_DEFAULT_STATUS=pending
SUPPLIER_AUTO_APPROVE=false
SUPPLIER_EVALUATION_CYCLE=90  # 天数
```

### 高级配置

```yaml
# config/packages/supplier_manage.yaml
supplier_manage:
    # 默认配置
    default_status: 'pending'
    auto_approve: false

    # 评估配置
    evaluation:
        default_cycle: 90  # 评估周期（天）
        max_score: 100
        grade_levels:
            A: 90
            B: 80
            C: 70
            D: 0

    # 通知配置
    notifications:
        email_enabled: true
        sms_enabled: false
```

## 🧪 测试

运行测试套件：

```bash
# 运行所有测试
php bin/phpunit packages/supplier-manage-bundle/tests/

# 运行特定测试
php bin/phpunit packages/supplier-manage-bundle/tests/Service/SupplierServiceTest.php

# 生成测试覆盖率报告
php bin/phpunit --coverage-html coverage packages/supplier-manage-bundle/tests/
```

## 📚 API 文档

### REST API 端点

| 方法 | 路径 | 描述 |
|------|------|------|
| GET | `/api/suppliers` | 获取供应商列表 |
| POST | `/api/suppliers` | 创建新供应商 |
| GET | `/api/suppliers/{id}` | 获取供应商详情 |
| PUT | `/api/suppliers/{id}` | 更新供应商信息 |
| DELETE | `/api/suppliers/{id}` | 删除供应商 |
| GET | `/api/suppliers/{id}/contacts` | 获取供应商联系人 |
| POST | `/api/suppliers/{id}/contacts` | 添加供应商联系人 |

### 请求示例

```json
// POST /api/suppliers
{
    "name": "示例供应商",
    "legalName": "示例供应商有限公司",
    "legalAddress": "北京市朝阳区xxx街道",
    "registrationNumber": "91110000000000000X",
    "taxNumber": "91110000000000000X",
    "supplierType": "general",
    "cooperationModel": "long_term",
    "contactPerson": "张三",
    "contactPhone": "13800138000",
    "contactEmail": "supplier@example.com"
}
```

## 🔄 更新日志

### v1.0.0
- 初始版本发布
- 实现基础供应商管理功能
- 集成 EasyAdmin 后台
- 支持 RESTful API
- 添加工作流集成

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

1. Fork 本项目
2. 创建功能分支：`git checkout -b feature/amazing-feature`
3. 提交更改：`git commit -m 'Add amazing feature'`
4. 推送到分支：`git push origin feature/amazing-feature`
5. 提交 Pull Request

## 📄 许可证

本项目采用 MIT 许可证 - 查看 [LICENSE](LICENSE) 文件了解详情。

## 🔗 相关链接

- [Symfony Bundle 最佳实践](https://symfony.com/doc/current/bundles/best_practices.html)
- [EasyAdmin Bundle 文档](https://symfony.com/doc/current/bundles/EasyAdminBundle.html)
- [Symfony Workflow 组件](https://symfony.com/doc/current/components/workflow.html)

## 🆘 支持

如遇问题或需要帮助：

1. 查看 [文档目录](docs/) 了解详细功能
2. 提交 [Issue](https://github.com/tourze/supplier-manage-bundle/issues)
3. 联系维护者

---

**注意**：这是一个企业级供应商管理解决方案，建议在生产环境使用前进行充分的测试和配置。
