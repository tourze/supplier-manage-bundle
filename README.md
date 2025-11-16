# Supplier Management Symfony Bundle

[English](README.md) | [中文](README.zh-CN.md)

# Universal Supplier Management Symfony Bundle

A comprehensive supplier management Symfony Bundle that implements the full lifecycle management of suppliers from registration to performance evaluation.

## 📋 Features

### Core Features
- **Supplier Management**: Supplier information maintenance, status management, and classification
- **Contact Management**: CRUD operations for supplier contacts
- **Qualification Management**: Review and management of supplier qualifications and certifications
- **Contract Management**: Creation, approval, and tracking of supplier contracts
- **Performance Evaluation**: Quantitative evaluation and historical records of supplier performance
- **Workflow Integration**: Approval processes based on Symfony Workflow

### Technical Features
- **EasyAdmin Integration**: Out-of-the-box admin backend
- **RESTful API**: Complete REST API support
- **Workflow Engine**: Process management based on Symfony Workflow
- **Data Validation**: Data validation using Symfony Validator
- **Index Optimization**: Automatic indexing of key fields for improved query performance
- **Timestamp Management**: Automatic management of creation and update times

## 🚀 Installation

Install using Composer:

```bash
composer require tourze/supplier-manage-bundle
```

## ⚙️ Configuration

### 1. Register Bundle

Register in `config/bundles.php`:

```php
return [
    // ...
    Tourze\SupplierManageBundle\SupplierManageBundle::class => ['all' => true],
];
```

### 2. Database Configuration

Ensure Doctrine database connection is properly configured:

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

### 3. Create Database Tables

```bash
php bin/console doctrine:schema:create
# Or update existing schema
php bin/console doctrine:schema:update --force
```

## 📖 Usage

### Basic Usage

#### Create Supplier

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
            'name' => 'Example Supplier',
            'legalName' => 'Example Supplier Co., Ltd.',
            'legalAddress' => 'Beijing Chaoyang District xxx Street',
            'registrationNumber' => '91110000000000000X',
            'taxNumber' => '91110000000000000X',
            'supplierType' => SupplierType::GENERAL,
            'cooperationModel' => CooperationModel::LONG_TERM,
            'contactPerson' => 'John Doe',
            'contactPhone' => '13800138000',
            'contactEmail' => 'supplier@example.com',
            'bankName' => 'Bank of China',
            'bankAccount' => '6210000000000000000'
        ]);
    }
}
```

#### Query Suppliers

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

#### Manage Contacts

```php
use Tourze\SupplierManageBundle\Entity\SupplierContact;
use Tourze\SupplierManageBundle\Repository\SupplierContactRepository;

class ContactController
{
    public function createContact(Supplier $supplier): SupplierContact
    {
        $contact = new SupplierContact();
        $contact->setSupplier($supplier);
        $contact->setName('Jane Smith');
        $contact->setPosition('Sales Manager');
        $contact->setPhone('13900139000');
        $contact->setEmail('jane@supplier.com');
        $contact->setIsPrimary(true);

        return $contact;
    }
}
```

### Using EasyAdmin Backend

This Bundle integrates with EasyAdmin to provide an out-of-the-box admin backend:

1. Ensure EasyAdmin Bundle is installed and configured
2. Visit `/admin` path to see the supplier management menu
3. Supports management of the following entities:
    - Supplier
    - SupplierContact
    - SupplierQualification
    - Contract
    - PerformanceEvaluation
    - EvaluationItem

### Workflow Integration

Supplier status transitions based on Symfony Workflow component:

```yaml
# config/packages/workflow.yaml
framework:
    workflows:
        supplier_status:
            type: 'state_machine'
            supports:
                - Tourze\SupplierManageBundle\Entity\Supplier
            places:
                - pending     # Pending review
                - approved    # Approved
                - rejected    # Rejected
                - suspended   # Suspended
                - archived    # Archived
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

## 🏗️ Entity Models

### Supplier
- Basic Information: Name, legal entity, registration number, tax number, etc.
- Business Information: Type, cooperation model, status, etc.
- Contact Information: Contact person, phone, email, bank account, etc.

### SupplierContact
- Multiple contacts associated with suppliers
- Support for setting primary contact identifier
- Detailed information including position and contact methods

### SupplierQualification
- Management of various supplier qualifications and certifications
- Support for qualification approval status tracking
- Record validity periods and reminder functionality

### Contract
- Supplier contract information management
- Support for contract status tracking
- Record key information such as contract amount and duration

### PerformanceEvaluation
- Supplier performance evaluation records
- Support for custom evaluation templates
- Quantitative scoring and grade management

## 🔧 Configuration Options

### Environment Variables

```bash
# .env
# Supplier management related configuration
SUPPLIER_DEFAULT_STATUS=pending
SUPPLIER_AUTO_APPROVE=false
SUPPLIER_EVALUATION_CYCLE=90  # days
```

### Advanced Configuration

```yaml
# config/packages/supplier_manage.yaml
supplier_manage:
    # Default configuration
    default_status: 'pending'
    auto_approve: false

    # Evaluation configuration
    evaluation:
        default_cycle: 90  # Evaluation cycle (days)
        max_score: 100
        grade_levels:
            A: 90
            B: 80
            C: 70
            D: 0

    # Notification configuration
    notifications:
        email_enabled: true
        sms_enabled: false
```

## 🧪 Testing

Run the test suite:

```bash
# Run all tests
php bin/phpunit packages/supplier-manage-bundle/tests/

# Run specific tests
php bin/phpunit packages/supplier-manage-bundle/tests/Service/SupplierServiceTest.php

# Generate test coverage report
php bin/phpunit --coverage-html coverage packages/supplier-manage-bundle/tests/
```

## 📚 API Documentation

### REST API Endpoints

| Method | Path | Description |
|--------|------|-------------|
| GET | `/api/suppliers` | Get supplier list |
| POST | `/api/suppliers` | Create new supplier |
| GET | `/api/suppliers/{id}` | Get supplier details |
| PUT | `/api/suppliers/{id}` | Update supplier information |
| DELETE | `/api/suppliers/{id}` | Delete supplier |
| GET | `/api/suppliers/{id}/contacts` | Get supplier contacts |
| POST | `/api/suppliers/{id}/contacts` | Add supplier contact |

### Request Example

```json
// POST /api/suppliers
{
    "name": "Example Supplier",
    "legalName": "Example Supplier Co., Ltd.",
    "legalAddress": "Beijing Chaoyang District xxx Street",
    "registrationNumber": "91110000000000000X",
    "taxNumber": "91110000000000000X",
    "supplierType": "general",
    "cooperationModel": "long_term",
    "contactPerson": "John Doe",
    "contactPhone": "13800138000",
    "contactEmail": "supplier@example.com"
}
```

## 🔄 Changelog

### v1.0.0
- Initial release
- Implemented basic supplier management functionality
- Integrated EasyAdmin backend
- Added RESTful API support
- Added workflow integration

## 🤝 Contributing

Issues and Pull Requests are welcome!

1. Fork this project
2. Create your feature branch: `git checkout -b feature/amazing-feature`
3. Commit your changes: `git commit -m 'Add amazing feature'`
4. Push to the branch: `git push origin feature/amazing-feature`
5. Submit a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🔗 Related Links

- [Symfony Bundle Best Practices](https://symfony.com/doc/current/bundles/best_practices.html)
- [EasyAdmin Bundle Documentation](https://symfony.com/doc/current/bundles/EasyAdminBundle.html)
- [Symfony Workflow Component](https://symfony.com/doc/current/components/workflow.html)

## 🆘 Support

If you encounter problems or need help:

1. Check the [documentation directory](docs/) for detailed functionality
2. Submit an [Issue](https://github.com/tourze/supplier-manage-bundle/issues)
3. Contact the maintainers

---

**Note**: This is an enterprise-level supplier management solution. It is recommended to conduct thorough testing and configuration before using in production environments.